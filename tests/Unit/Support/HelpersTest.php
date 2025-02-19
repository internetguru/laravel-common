<?php

namespace Tests\Unit\Support;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use InternetGuru\LaravelCommon\Support\Helpers;
use Mockery;
use Tests\TestCase;

class HelpersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Define routes for testing
        Route::get('/', function () {})->name('home');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mock_app_info()
    {
        // Fake the root disk to simulate accessing root files
        Storage::fake('root');
        Storage::disk('root')->put('VERSION', '1.0.0');
        Storage::disk('root')->put('.git/HEAD', 'ref: refs/heads/main');
        Storage::disk('root')->put('.git/refs/heads/main', '1234567890abcdef');
        Config::set('app.name', 'TestApp');
    }

    public function test_get_app_info_array()
    {
        $this->mock_app_info();

        // Expected result
        $expected = [
            'app_name' => 'TestApp',
            'environment' => 'testing',
            'version' => '1.0.0',
            'branch' => 'main',
            'commit' => '1234567',
        ];

        $this->assertEquals($expected, Helpers::getAppInfoArray());
    }

    public function test_get_app_info()
    {
        $this->mock_app_info();

        // Expected result
        $expected = 'TestApp testing 1.0.0 main 1234567';

        $this->assertEquals($expected, Helpers::getAppInfo());
    }

    public function test_get_app_info_with_detached_head()
    {
        $this->mock_app_info();

        // Change the HEAD file to simulate a detached HEAD state
        Storage::disk('root')->put('.git/HEAD', '1234567890abcdef');
        Storage::disk('root')->put('.git/1234567', '1234567890abcdef');

        // Expected result
        $expected = [
            'app_name' => 'TestApp',
            'environment' => 'testing',
            'version' => '1.0.0',
            'branch' => '[detached]',
            'commit' => '1234567',
        ];

        $this->assertEquals($expected, Helpers::getAppInfoArray());
    }
}
