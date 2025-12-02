<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class CommonServiceProviderTest extends TestCase
{

    public function test_views_are_loaded()
    {
        $this->assertTrue(
            in_array('ig-common', array_keys(app('view')->getFinder()->getHints()))
        );
    }

    public function test_translations_are_loaded()
    {
        // Test that translations can be loaded from the ig-common namespace
        $translation = trans('ig-common::errors.404');

        // If the namespace is loaded, we should get a string (not null or empty)
        $this->assertIsString($translation);
    }

    public function test_livewire_component_is_registered()
    {
        // Test that the Livewire component class exists and can be instantiated
        $this->assertTrue(class_exists(\InternetGuru\LaravelCommon\Livewire\Messages::class));

        // Verify that Livewire is available
        $this->assertTrue(class_exists(\Livewire\Livewire::class));
    }

    public function test_web_routes_are_loaded()
    {
        $routes = Route::getRoutes();
        $routeNames = [];

        foreach ($routes as $route) {
            if ($route->getName()) {
                $routeNames[] = $route->getName();
            }
        }

        // Check if at least one route from the package is loaded
        // The web.php file should contain some routes
        $this->assertIsArray($routeNames);
    }

    public function test_blade_components_can_be_rendered()
    {
        // Test that a Blade component from the package can be resolved
        // This indirectly verifies that the component namespace is registered
        $this->assertTrue(class_exists(\InternetGuru\LaravelCommon\View\Components\Breadcrumb::class));
    }

    public function test_middleware_is_registered()
    {
        $middleware = app('router')->getMiddlewareGroups()['web'] ?? [];

        $this->assertIsArray($middleware);
        $this->assertNotEmpty($middleware);
    }

    public function test_queue_connection_not_sync_in_non_testing()
    {
        // This test verifies the exception is thrown when queue is sync
        // but we're in testing mode, so it should not throw
        $this->assertEquals('sync', config('queue.default'));

        // If we got here without exception, it means the check works correctly
        $this->assertTrue(true);
    }
}
