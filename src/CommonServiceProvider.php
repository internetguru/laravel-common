<?php

namespace InternetGuru\LaravelCommon;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use InternetGuru\LaravelCommon\Contracts\ReCaptchaInterface;
use InternetGuru\LaravelCommon\Middleware\InjectRecaptchaScript;
use InternetGuru\LaravelCommon\Exceptions\Handler;
use InternetGuru\LaravelCommon\Livewire\Messages;
use InternetGuru\LaravelCommon\Services\ReCaptcha;
use Livewire\Livewire;
use Illuminate\Support\Facades\Event;
use Illuminate\Notifications\Events\NotificationSent;
use InternetGuru\LaravelCommon\Listeners\LogSentNotification;


class CommonServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register custom exception handler
        $this->app->extend(ExceptionHandler::class, function ($handler, $app) {
            $customHandler = new Handler($app);
            return $customHandler;
        });

        // Register default ReCaptcha service
        $this->app->bind(ReCaptchaInterface::class, ReCaptcha::class);

    }

    public function boot()
    {
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

        // Register middleware to inject ReCaptcha script
        $this->app['router']->pushMiddlewareToGroup('web', InjectRecaptchaScript::class);

        // throw if queue connection is sync and if not testing
        if ($this->app['config']->get('queue.default') === 'sync' && ! app()->runningUnitTests()) {
            throw new \Exception('Queue connection is set to sync. Please change it to a different connection.');
        }

        $this->registerMacros();
    }

    private function registerMacros()
    {
        initNumberMacros();
        initCarbonMacros();
    }
}
