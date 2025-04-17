<?php

namespace Tests;

use InternetGuru\LaravelCommon\CommonServiceProvider;
use Livewire\LivewireServiceProvider;
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
            LivewireServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Create necessary storage directories for the test environment
        $this->createTestDirectories();

        // Set up the view compiled path
        $app['config']->set('view.compiled', __DIR__ . '/cache');

        // Set up cache configuration
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
    }

    protected function createTestDirectories()
    {
        // Create required storage directories for the test
        $dirs = [
            storage_path('app'),
            storage_path('framework/cache'),
            storage_path('framework/views'),
            storage_path('framework/sessions'),
            storage_path('logs'),
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }
    }
}
