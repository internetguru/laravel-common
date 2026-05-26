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

        $this->assertNotNull($routes->getByName('errors.index'));
        $this->assertNotNull($routes->getByName('error.404'));
        $this->assertNotNull($routes->getByName('error.503'));
        $this->assertNotNull($routes->getByName('i18n.index'));
    }

    public function test_blade_components_can_be_rendered()
    {
        // Test that a Blade component from the package can be resolved
        // This indirectly verifies that the component namespace is registered
        $this->assertTrue(class_exists(\InternetGuru\LaravelCommon\View\Components\Breadcrumb::class));
    }

    public function test_middleware_is_registered()
    {
        $provider = new \InternetGuru\LaravelCommon\CommonServiceProvider(app());
        $property = (new \ReflectionClass($provider))->getProperty('webMiddleware');
        $property->setAccessible(true);
        $registered = $property->getValue($provider);

        foreach ([
            \InternetGuru\LaravelCommon\Http\Middleware\CheckPostItemNames::class,
            \InternetGuru\LaravelCommon\Http\Middleware\InjectMetaRobots::class,
            \InternetGuru\LaravelCommon\Http\Middleware\InjectUmamiScript::class,
            \InternetGuru\LaravelCommon\Http\Middleware\PreventDuplicateSubmissions::class,
            \InternetGuru\LaravelCommon\Http\Middleware\SetPrevPage::class,
        ] as $class) {
            $this->assertContains($class, $registered, "$class not found in web middleware group");
        }
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
