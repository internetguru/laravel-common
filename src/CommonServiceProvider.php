<?php

namespace InternetGuru\LaravelCommon;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use InternetGuru\LaravelCommon\Support\Translator;

class CommonServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];
            $locale = $app['config']['app.locale'];
            $translator = new Translator($loader, $locale);

            return $translator;
        });
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'package');
        Blade::componentNamespace('InternetGuru\LaravelCommon\View\Components', 'ig');
    }
}
