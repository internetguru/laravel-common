<?php

namespace InternetGuru\LaravelCommon;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use InternetGuru\LaravelCommon\Exceptions\Handler;
use InternetGuru\LaravelCommon\Http\Middleware\CheckPostItemNames;
use InternetGuru\LaravelCommon\Http\Middleware\InjectMetaRobots;
use InternetGuru\LaravelCommon\Http\Middleware\InjectUmamiScript;
use InternetGuru\LaravelCommon\Http\Middleware\PreventDuplicateSubmissions;
use InternetGuru\LaravelCommon\Http\Middleware\SetPrevPage;
use InternetGuru\LaravelCommon\Listeners\LogSentNotification;
use InternetGuru\LaravelCommon\Livewire\Messages;
use InternetGuru\LaravelCommon\Livewire\RejectMalformedPayload;
use InternetGuru\LaravelCommon\Middleware\TimezoneMiddleware;
use InternetGuru\LaravelCommon\Middleware\VerifyCsrfToken;
use InternetGuru\LaravelCommon\Rules\Ulid32;
use InternetGuru\LaravelFeedback\FeedbackServiceProvider;
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
        $this->registerMacros();
        $this->registerFeedbackFields();
        $this->ensureQueueIsNotSync();
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

    private function registerMacros(): void
    {
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
