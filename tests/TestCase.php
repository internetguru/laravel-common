<?php

namespace Tests;

use InternetGuru\LaravelCommon\CommonServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            CommonServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Set up environment configuration if necessary
    }
}
