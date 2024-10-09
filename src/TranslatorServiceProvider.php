<?php

namespace InternetGuru\LaravelCommon;

use Illuminate\Support\ServiceProvider;
use InternetGuru\LaravelCommon\Support\Translator;

class TranslatorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];
            $locale = $app['config']['app.locale'];
            $fallback = $app['config']['app.fallback_locale'];

            $translator = new Translator($loader, $locale);
            $translator->setFallback($fallback);

            return $translator;
        });
    }
}
