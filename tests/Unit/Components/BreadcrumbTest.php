<?php

namespace Tests\Unit\View\Components;

use Illuminate\Http\Request;
use Illuminate\View\View;
use InternetGuru\LaravelCommon\View\Components\Breadcrumb;
use Mockery;
use Tests\TestCase;

class BreadcrumbTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $request = Request::create('about/contact', 'GET');
        $this->app->instance('request', $request);

        $this->app['router']->get('about', function () {
            return 'about';
        });
        $this->app['router']->get('about/contact', function () {
            return 'contact';
        });
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
}
