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
use InternetGuru\LaravelCommon\Http\Middleware\InjectUmamiScript;
use InternetGuru\LaravelCommon\Http\Middleware\PreventDuplicateSubmissions;
use InternetGuru\LaravelCommon\Http\Middleware\SetPrevPage;
use InternetGuru\LaravelCommon\Listeners\LogSentNotification;
use InternetGuru\LaravelCommon\Livewire\Messages;
use InternetGuru\LaravelCommon\Middleware\RedirectToLangDomain;
use InternetGuru\LaravelCommon\Middleware\TimezoneMiddleware;
use InternetGuru\LaravelCommon\Middleware\VerifyCsrfToken;
use InternetGuru\LaravelCommon\Rules\Ulid32;
use Livewire\Livewire;

class CommonServiceProvider extends ServiceProvider
{
    protected array $webMiddleware = [
        CheckPostItemNames::class,
        InjectUmamiScript::class,
        PreventDuplicateSubmissions::class,
        RedirectToLangDomain::class,
        SetPrevPage::class,
        TimezoneMiddleware::class,
        VerifyCsrfToken::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ig-common.php', 'ig-common');

        $this->app->extend(ExceptionHandler::class, fn ($handler, $app) => new Handler($app));
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
        $this->ensureQueueIsNotSync();
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
