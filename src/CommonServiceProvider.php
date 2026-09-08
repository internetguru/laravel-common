<?php

namespace InternetGuru\LaravelCommon;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Validator as ValidatorInstance;
use InternetGuru\LaravelCommon\Exceptions\Handler;
use InternetGuru\LaravelCommon\Http\Middleware\CheckPostItemNames;
use InternetGuru\LaravelCommon\Http\Middleware\InjectMetaRobots;
use InternetGuru\LaravelCommon\Http\Middleware\InjectUmamiScript;
use InternetGuru\LaravelCommon\Http\Middleware\PreventDuplicateSubmissions;
use InternetGuru\LaravelCommon\Http\Middleware\SetPrevPage;
use InternetGuru\LaravelCommon\Listeners\LogSentNotification;
use InternetGuru\LaravelCommon\Livewire\Messages;
use InternetGuru\LaravelCommon\Livewire\RejectMalformedPayload;
use InternetGuru\LaravelCommon\Logging\DeduplicateRepeatedRecords;
use InternetGuru\LaravelCommon\Middleware\TimezoneMiddleware;
use InternetGuru\LaravelCommon\Middleware\VerifyCsrfToken;
use InternetGuru\LaravelCommon\Rules\Ulid32;
use InternetGuru\LaravelCommon\Support\Sanitizer;
use InternetGuru\LaravelFeedback\FeedbackServiceProvider;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Livewire;

class CommonServiceProvider extends ServiceProvider
{
    protected array $webMiddleware = [
        CheckPostItemNames::class,
        InjectMetaRobots::class,
        InjectUmamiScript::class,
        PreventDuplicateSubmissions::class,
        SetPrevPage::class,
        TimezoneMiddleware::class,
        VerifyCsrfToken::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ig-common.php', 'ig-common');

        $this->app->extend(ExceptionHandler::class, fn ($handler, $app) => new Handler($app));

        $this->registerLogDeduplication();

        // Scoped, not singleton: the sanitizer remembers which keys it has
        // already reported this request, and scoped bindings are flushed
        // between requests under Octane.
        $this->app->scoped(Sanitizer::class);

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
        $router = $this->app['router'];

        foreach ($this->webMiddleware as $middleware) {
            $router->pushMiddlewareToGroup('web', $middleware);
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
