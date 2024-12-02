<?php

namespace InternetGuru\LaravelCommon;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class CommonServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
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
