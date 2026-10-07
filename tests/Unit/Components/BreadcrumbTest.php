<?php

namespace Tests\Unit\View\Components;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use InternetGuru\LaravelCommon\View\Components\Breadcrumb;
use Mockery;
use Tests\TestCase;

class BreadcrumbTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Register routes first
        $this->app['router']->get('/', function () {
            return 'home';
        })->name('home');
        $this->app['router']->get('/about', function () {
            return 'about';
        })->name('about');
        $this->app['router']->get('/about/contact', function () {
            return 'contact';
        })->name('contact');

        // Create request and match it to a route
        $request = Request::create('/about/contact', 'GET');
        $route = $this->app['router']->getRoutes()->match($request);

        // Set the route resolver so route() returns the matched route
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $this->app->instance('request', $request);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_constructor_sets_divider_and_items()
    {
        $divider = '|';
        $breadcrumb = new Breadcrumb($divider);

        $this->assertEquals($divider, $breadcrumb->divider);
        $this->assertEquals(3, count($breadcrumb->items));
    }

    public function test_render_returns_correct_view()
    {
        $divider = '|';
        $breadcrumb = new Breadcrumb($divider);

        $view = $breadcrumb->render();
        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals('ig-common::components.breadcrumb', $view->name());
        $this->assertStringContainsString("--bs-breadcrumb-divider: '|';", $view->render());
    }

    public function test_render_folds_the_levels_into_a_menu()
    {
        $html = (new Breadcrumb)->render()->render();

        $this->assertStringContainsString('aria-labelledby="breadcrumbMenu"', $html);
        $this->assertStringContainsString('<li><a class="dropdown-item" href="/about">', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringNotContainsString('ig-breadcrumb-marked', $html);
    }

    public function test_render_draws_the_mark()
    {
        $html = (new Breadcrumb(mark: '/mark.svg'))->render()->render();

        $this->assertStringContainsString('ig-breadcrumb-marked', $html);
        $this->assertStringContainsString("--breadcrumb-home-mark: url('/mark.svg');", $html);
        $this->assertStringContainsString('<span class="ig-breadcrumb-mark"></span>', $html);
    }

    public function test_items_replace_the_parsed_levels()
    {
        $breadcrumb = new Breadcrumb(items: [['uri' => '/', 'translation' => 'Home']]);

        $this->assertEquals([['uri' => '/', 'translation' => 'Home']], $breadcrumb->items);
    }
}
