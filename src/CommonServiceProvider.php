<?php

namespace InternetGuru\LaravelCommon;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

use function InternetGuru\LaravelCommon\Support\initCarbonMacros;
use function InternetGuru\LaravelCommon\Support\initNumberMacros;

class CommonServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'common');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'common');
        Blade::componentNamespace('InternetGuru\LaravelCommon\View\Components', 'ig');
        $this->registerMacros();
    }

    private function registerMacros()
    {
        initNumberMacros();
        initCarbonMacros();
    }
}
