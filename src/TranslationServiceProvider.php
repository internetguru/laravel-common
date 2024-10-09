<?php

namespace InternetGuru\LaravelCommon;

use Illuminate\Translation\TranslationServiceProvider as BaseTranslationServiceProvider;
use InternetGuru\LaravelCommon\Support\Translator;

/**
 * @see https://github.com/laravel/framework/blob/11.x/src/Illuminate/Translation/TranslationServiceProvider.php
 */
class TranslationServiceProvider extends BaseTranslationServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerLoader();

        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
            $locale = $app->getLocale();

            $trans = new Translator($loader, $locale);

            $trans->setFallback($app->getFallbackLocale());

            return $trans;
        });
    }
}
