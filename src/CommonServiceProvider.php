<?php

namespace InternetGuru\LaravelCommon;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use InternetGuru\LaravelCommon\Exceptions\Handler;
use InternetGuru\LaravelCommon\Http\Middleware\CheckPostItemNames;
use InternetGuru\LaravelCommon\Http\Middleware\PreventDuplicateSubmissions;
use InternetGuru\LaravelCommon\Http\Middleware\SetPrevPage;
use InternetGuru\LaravelCommon\Listeners\LogSentNotification;
use InternetGuru\LaravelCommon\Livewire\Messages;
use InternetGuru\LaravelCommon\Middleware\TimezoneMiddleware;
use InternetGuru\LaravelCommon\Middleware\VerifyCsrfToken;
use InternetGuru\LaravelCommon\Rules\Ulid32;
use Livewire\Livewire;

class CommonServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register custom exception handler
        $this->app->extend(ExceptionHandler::class, function ($handler, $app) {
            $customHandler = new Handler($app);

            return $customHandler;
        });
    }

    public function boot()
    {
        $this->registerMiddleware();

        Route::middleware('web')->group(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'ig-common');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'ig-common');
        Blade::componentNamespace('InternetGuru\LaravelCommon\View\Components', 'ig');
        Livewire::component('ig-messages', Messages::class);

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

        Event::listen(
            NotificationSent::class,
            [LogSentNotification::class, 'handle']
        );

        // export ulid32 validation rule
        \Illuminate\Support\Facades\Validator::extend('ulid32', fn ($a, $v) => Ulid32::isValid($v), __('ig-common::messages.validation.ulid32'));

        // throw if queue connection is sync and if not testing
        if ($this->app['config']->get('queue.default') === 'sync' && ! app()->runningUnitTests()) {
            throw new \Exception('Queue connection is set to sync. Please change it to a different connection.');
        }

        $this->registerMacros();
    }

    private function registerMiddleware()
    {
        $router = $this->app['router'];
        $router->pushMiddlewareToGroup('web', CheckPostItemNames::class);
        $router->pushMiddlewareToGroup('web', PreventDuplicateSubmissions::class);
        $router->pushMiddlewareToGroup('web', SetPrevPage::class);
        $router->pushMiddlewareToGroup('web', TimezoneMiddleware::class);
        $router->pushMiddlewareToGroup('web', VerifyCsrfToken::class);
    }

    private function registerMacros()
    {
        initStringMacros();
        initNumberMacros();
        initCarbonMacros();
    }
}
