<?php

namespace Tests\Unit\Support;

use Illuminate\Support\Facades\Route;
use InternetGuru\LaravelCommon\Support\Helpers;
use Tests\TestCase;

class ParseUrlPathTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Define routes for testing
        Route::get('/', function () {})->name('home');
        Route::get('/about', function () {})->name('about');
        Route::get('/products', function () {})->name('products.index');
        Route::get('/products/{category}', function () {})->name('products.category');
        Route::get('/products/{category}/{item}', function () {})->name('products.item');
    }

    public function testParseUrlPathRoot()
    {
        $this->get('/');

        $result = Helpers::parseUrlPath();

        $expected = [
            [
                'uri' => '/',
                'translation' => __('navig.home'),
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testParseUrlPathSimple()
    {
        $this->get('/about');

        $result = Helpers::parseUrlPath();

        $expected = [
            [
                'uri' => '/',
                'translation' => __('navig.home'),
            ],
            [
                'uri' => '/about',
                'translation' => __('navig.about'),
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testParseUrlPathNested()
    {
        $this->get('/products/electronics/laptops');

        $result = Helpers::parseUrlPath();

        $expected = [
            [
                'uri' => '/',
                'translation' => __('navig.home'),
            ],
            [
                'uri' => '/products',
                'translation' => __('navig.products.index'),
            ],
            [
                'uri' => '/products/electronics',
                'translation' => __('navig.products.category'),
            ],
            [
                'uri' => '/products/electronics/laptops',
                'translation' => __('navig.products.item'),
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testParseUrlPathWithSkip()
    {
        $this->get('/admin/dashboard');

        $result = Helpers::parseUrlPath('home', skipFirst: 1);

        $expected = [
            [
                'uri' => '/',
                'translation' => __('navig.home'),
            ],
            [
                'uri' => '',
                'translation' => __('navig.dashboard'),
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testParseUrlPathNonExistentRoute()
    {
        $this->get('/non-existent');

        $result = Helpers::parseUrlPath();

        $expected = [
            [
                'uri' => '/',
                'translation' => __('navig.home'),
            ],
            [
                'uri' => '',
                'translation' => __('navig.non-existent'),
            ],
        ];

        $this->assertEquals($expected, $result);
    }
}
