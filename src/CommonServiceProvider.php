<?php

namespace InternetGuru\LaravelCommon;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Validator as ValidatorInstance;
use InternetGuru\LaravelCommon\Exceptions\Handler;
use InternetGuru\LaravelCommon\Http\Middleware\CheckPostItemNames;
use InternetGuru\LaravelCommon\Http\Middleware\InjectMetaRobots;
use InternetGuru\LaravelCommon\Http\Middleware\InjectUmamiScript;
use InternetGuru\LaravelCommon\Http\Middleware\LogRequest;
use InternetGuru\LaravelCommon\Http\Middleware\PreventDuplicateSubmissions;
use InternetGuru\LaravelCommon\Http\Middleware\ReportImageProblems;
use InternetGuru\LaravelCommon\Http\Middleware\SetPrevPage;
use InternetGuru\LaravelCommon\Listeners\LogSentNotification;
use InternetGuru\LaravelCommon\Livewire\Messages;
use InternetGuru\LaravelCommon\Livewire\PageWatch;
use InternetGuru\LaravelCommon\Livewire\RejectMalformedPayload;
use InternetGuru\LaravelCommon\Logging\DeduplicateRepeatedRecords;
use InternetGuru\LaravelCommon\Middleware\TimezoneMiddleware;
use InternetGuru\LaravelCommon\Middleware\VerifyCsrfToken;
use InternetGuru\LaravelCommon\Rules\Ulid32;
use InternetGuru\LaravelCommon\Support\LabelGroups;
use InternetGuru\LaravelCommon\Support\Sanitizer;
use InternetGuru\LaravelCommon\Support\WatchedPage;
use InternetGuru\LaravelFeedback\FeedbackServiceProvider;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Livewire;
use Monolog\Handler\NullHandler;

class CommonServiceProvider extends ServiceProvider
{
    protected array $webMiddleware = [
        CheckPostItemNames::class,
        InjectMetaRobots::class,
        InjectUmamiScript::class,
        LogRequest::class,
        PreventDuplicateSubmissions::class,
        ReportImageProblems::class,
        SetPrevPage::class,
        TimezoneMiddleware::class,
        VerifyCsrfToken::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ig-common.php', 'ig-common');

        $this->app->extend(ExceptionHandler::class, fn ($handler, $app) => new Handler($app));

        $this->registerDeprecationsChannel();
        $this->silenceLogsInUnitTests();
        $this->registerLogDeduplication();

        $this->applySqliteDefaults();

        // Scoped, not singleton: the sanitizer remembers which keys it has
        // already reported this request, and scoped bindings are flushed
        // between requests under Octane.
        $this->app->scoped(Sanitizer::class);
        $this->app->scoped(LabelGroups::class);
        $this->app->scoped(WatchedPage::class);

        // Livewire attaches listeners for every registered component hook in
        // ComponentHookRegistry::boot(), so a hook registered after Livewire's
        // provider has booted is silently ignored. Booting callbacks run before
        // any provider boots, which makes this independent of discovery order.
        $this->app->booting(fn () => Livewire::componentHook(RejectMalformedPayload::class));
    }

    public function boot(): void
    {
        $this->registerMiddleware();
        $this->registerRoutes();
        $this->registerViews();
        $this->registerTranslations();
        $this->registerPublishing();
        $this->registerEvents();
        $this->registerValidationRules();
        $this->registerSanitizer();
        $this->registerMacros();
        $this->registerFeedbackFields();
        $this->ensureQueueIsNotSync();
    }

    /**
     * Define the "deprecations" log channel up front, the way Laravel's
     * HandleExceptions does on the first deprecation.
     *
     * Laravel adds it to the base application's config, but under Octane the
     * LogManager reads the request's config clone, which then lacks it: every
     * new worker logged "Log [deprecations] is not defined" through the
     * emergency logger. Defining it here puts it into every clone.
     */
    private function registerDeprecationsChannel(): void
    {
        $config = $this->app['config'];

        if ($config->get('logging.channels.deprecations')) {
            return;
        }

        $this->ensureNullChannel();

        $options = $config->get('logging.deprecations');
        $channel = is_array($options) ? ($options['channel'] ?? 'null') : ($options ?? 'null');

        $config->set('logging.channels.deprecations', $config->get("logging.channels.$channel"));
    }

    // The default channel writes to storage/logs, which tests share with the running application
    private function silenceLogsInUnitTests(): void
    {
        if (! $this->app->runningUnitTests()) {
            return;
        }

        $this->ensureNullChannel();
        $this->app['config']->set('logging.default', 'null');
    }

    private function ensureNullChannel(): void
    {
        if (! $this->app['config']->get('logging.channels.null')) {
            $this->app['config']->set('logging.channels.null', [
                'driver' => 'monolog',
                'handler' => NullHandler::class,
            ]);
        }
    }

    /**
     * Tap every configured log channel so repeats of the same record collapse
     * into one entry per window.
     *
     * Done in register(), before anything can resolve a channel: LogManager
     * memoises each channel the first time it is asked for, and reads its taps
     * only at that moment.
     */
    private function registerLogDeduplication(): void
    {
        $config = $this->app['config'];

        if (! $config->get('ig-common.log_deduplication.enabled')) {
            return;
        }

        foreach ($config->get('logging.channels', []) as $name => $channel) {
            $taps = $channel['tap'] ?? [];

            if (in_array(DeduplicateRepeatedRecords::class, $taps, true)) {
                continue;
            }

            $taps[] = DeduplicateRepeatedRecords::class;
            $config->set("logging.channels.$name.tap", $taps);
        }
    }

    /**
     * Give every sqlite connection the busy timeout, WAL journal and IMMEDIATE
     * transactions it leaves unset, so a queue worker and a web request writing
     * at once wait for the lock instead of failing with 'database is locked'.
     *
     * Done in register(), before anything can open a connection: the pragmas
     * are applied when a connection is created.
     *
     * Skipped in unit tests: they write from one process, and a schema dump
     * that switches the journal mode cannot be loaded beside an open WAL connection.
     */
    private function applySqliteDefaults(): void
    {
        $config = $this->app['config'];
        $defaults = $config->get('ig-common.sqlite_defaults');

        if (! is_array($defaults) || $this->app->runningUnitTests()) {
            return;
        }

        foreach ($config->get('database.connections', []) as $name => $connection) {
            if (($connection['driver'] ?? null) !== 'sqlite') {
                continue;
            }

            foreach ($defaults as $key => $value) {
                $current = $connection[$key] ?? null;
                $unset = $current === null
                    || ($key === 'transaction_mode' && strtoupper($current) === 'DEFERRED');

                if ($unset) {
                    $config->set("database.connections.$name.$key", $value);
                }
            }
        }
    }

    /**
     * Register the feedback field definitions used by the default footer complaints form.
     * Skipped without the optional internetguru/laravel-feedback package;
     * definitions already provided by the application are left untouched.
     */
    private function registerFeedbackFields(): void
    {
        if (! class_exists(FeedbackServiceProvider::class)) {
            return;
        }

        $defaults = [
            'location' => [
                'type' => 'select',
                'validation' => 'string|max:255',
                'label_translation_key' => 'ig-common::layouts.complaints.location',
            ],
            'occurred_at' => [
                'type' => 'datetime-local',
                'validation' => 'date',
                'label_translation_key' => 'ig-common::layouts.complaints.occurred_at',
            ],
        ];

        $config = $this->app['config'];

        foreach ($defaults as $name => $definition) {
            if (! $config->has("ig-feedback.names.$name")) {
                $config->set("ig-feedback.names.$name", $definition);
            }
        }
    }

    private function registerMiddleware(): void
    {
        // Through the kernel: it overwrites the router's groups whenever it is resolved after boot, as in tests.
        $kernel = $this->app->make(HttpKernelContract::class);
        if (! $kernel instanceof HttpKernel) {
            return;
        }

        foreach ($this->webMiddleware as $middleware) {
            $kernel->appendMiddlewareToGroup('web', $middleware);
        }
    }

    private function registerRoutes(): void
    {
        Route::middleware('web')->group(__DIR__ . '/../routes/web.php');
    }

    private function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'ig-common');
        Blade::componentNamespace('InternetGuru\LaravelCommon\View\Components', 'ig');
        Livewire::component('ig-messages', Messages::class);
        Livewire::component('ig-page-watch', PageWatch::class);
    }

    private function registerTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'ig-common');
    }

    private function registerPublishing(): void
    {
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'ig-common:migrations');

        $this->publishes([
            __DIR__ . '/../config/ig-common.php' => config_path('ig-common.php'),
        ], 'ig-common:config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/ig-common'),
        ], 'ig-common:views');

        $this->publishes([
            __DIR__ . '/../lang' => base_path('lang/vendor/ig-common'),
        ], 'ig-common:lang');
    }

    private function registerEvents(): void
    {
        Event::listen(NotificationSent::class, [LogSentNotification::class, 'handle']);

        Number::useLocale($this->app->getLocale());
        Event::listen(LocaleUpdated::class, fn (LocaleUpdated $event) => Number::useLocale($event->locale));
    }

    private function registerValidationRules(): void
    {
        Validator::extend('ulid32', fn ($a, $v) => Ulid32::isValid($v), __('ig-common::messages.validation.ulid32'));
    }

    /**
     * Normalize every value on its way into a validator.
     *
     * Both $request->validate() and a Livewire component's $this->validate()
     * reach Illuminate\Validation\Factory::resolve(), which hands construction
     * to this closure - so one registration covers HTTP, Livewire and bare
     * validator() calls alike, with no per-call-site opt-in.
     */
    private function registerSanitizer(): void
    {
        Validator::resolver(function ($translator, $data, $rules, $messages, $attributes) {
            $component = Livewire::current();
            $component = $component instanceof Component ? $component : null;

            $changes = app(Sanitizer::class)->changes(
                $data,
                $rules,
                [],
                $component ? $this->lockedProperties($component) : []
            );

            // Before $data is updated, while it still holds what the component
            // was given: writeBackToComponent() compares against it.
            $this->writeBackToComponent($component, $changes, $data);

            $sanitizer = app(Sanitizer::class);

            foreach ($changes as $change) {
                $sanitizer->pathSet($data, $change['path'], $change['value']);
            }

            return new ValidatorInstance($translator, $data, $rules, $messages, $attributes);
        });
    }

    /**
     * Carry the sanitized values back onto the Livewire component being
     * validated.
     *
     * A component reads $this->email after validating, not the array validate()
     * returns, so without this the normalized value would be thrown away. Only
     * paths the component actually holds, still carrying the value that was
     * sanitized, are written - a validator run on some unrelated array inside a
     * component must not touch its properties.
     *
     * @param  array<int, array{path: array<int, string>, key: string, value: mixed}>  $changes
     * @param  array<string, mixed>  $original
     */
    private function writeBackToComponent(?Component $component, array $changes, array $original): void
    {
        if ($changes === [] || $component === null) {
            return;
        }

        $sanitizer = app(Sanitizer::class);
        $properties = $component->all();
        $missing = new \stdClass;

        foreach ($changes as $change) {
            $held = $sanitizer->pathGet($properties, $change['path'], $missing);

            if ($held === $missing || $held !== $sanitizer->pathGet($original, $change['path'])) {
                continue;
            }

            $sanitizer->pathSet($component, $change['path'], $change['value']);
        }
    }

    /**
     * The component's locked property names.
     *
     * A #[Locked] property cannot be written by the client, so it holds what
     * the server put there - configuration, not input. Sanitizing it would
     * rewrite the application's own values, and reporting it would bury the
     * fields that really do come from a form: laravel-model-browser alone
     * carries sixteen of them against four writable ones.
     *
     * @return array<int, string>
     */
    private function lockedProperties(Component $component): array
    {
        $locked = [];

        foreach ((new \ReflectionObject($component))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getAttributes(Locked::class) !== []) {
                $locked[] = $property->getName();
            }
        }

        return $locked;
    }

    private function registerMacros(): void
    {
        initRequestMacros();
        initStringMacros();
        initNumberMacros();
        initCarbonMacros();
    }

    private function ensureQueueIsNotSync(): void
    {
        if ($this->app['config']->get('queue.default') === 'sync' && ! $this->app->runningUnitTests()) {
            throw new \Exception('Queue connection is set to sync. Please change it to a different connection.');
        }
    }
}
