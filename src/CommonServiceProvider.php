<?php

namespace InternetGuru\LaravelCommon;

use Illuminate\Support\ServiceProvider;

class CommonServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register the Casts
        $this->app->bind('carbon-interval', function () {
            return new Casts\CarbonIntervalCast();
        });
    }

    public function boot()
    {
        // Boot any package services here
    }
}
