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

        // Register middleware to inject ReCaptcha script
        $this->app['router']->pushMiddlewareToGroup('web', InjectRecaptchaScript::class);

        $this->registerMacros();
    }

    private function registerMacros()
    {
        initNumberMacros();
        initCarbonMacros();
    }
}
