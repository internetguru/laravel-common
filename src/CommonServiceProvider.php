<?php

namespace InternetGuru\LaravelCommon;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use InternetGuru\LaravelCommon\Exceptions\Handler;

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
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'ig-common');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'ig-common');
        Blade::componentNamespace('InternetGuru\LaravelCommon\View\Components', 'ig');
        $this->registerMacros();
    }

    private function registerMacros()
    {
        initNumberMacros();
        initCarbonMacros();
    }
}
