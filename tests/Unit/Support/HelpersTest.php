<?php

namespace Tests\Unit\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use InternetGuru\LaravelCommon\Support\Helpers;
use Mockery;
use Tests\TestCase;

class HelpersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app['router']->get('/', function () {
            return 'Home';
        })->name('home');
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

    private function mockRequestPath($path)
    {
        // Create a real request instance with the desired path
        $request = Request::create($path, 'GET');
        // Bind the request instance to the application
        $this->app->instance('request', $request);
    }

    private function mockTranslations($translations)
    {
        foreach ($translations as $key => $value) {
            Lang::shouldReceive('get')
                ->with($key, [], null)
                ->andReturn($value);
        }
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

    public function test_parse_url_path_no_short()
    {
        // Mock the request path
        $this->mockRequestPath('en/about/contact');

        // Mock translations
        $this->mockTranslations([
            'navig.home' => 'Home',
            'navig.home.short' => 'navig.home.short',
            'navig.about' => 'About',
            'navig.about.short' => 'navig.about.short',
            'navig.contact' => 'Contact',
            'navig.contact.short' => 'navig.contact.short',
        ]);

        // Expected result
        $expected = [
            [
                'route' => route('home'),
                'translation' => __('navig.home'),
                'class' => '',
            ],
            [
                'route' => route('home') . '/about',
                'translation' => __('navig.about'),
                'class' => '',
            ],
            [
                'route' => route('home') . '/about/contact',
                'translation' => __('navig.contact'),
                'class' => '',
            ],
        ];

        $this->assertEquals($expected, Helpers::parseUrlPath(skipFirst: 1));
    }

    public function test_parse_url_path_with_short()
    {
        // Mock the request path
        $this->mockRequestPath('/about/contact');

        // Mock translations
        $this->mockTranslations([
            'navig.home' => 'Home Long',
            'navig.home.short' => 'H',
            'navig.about' => 'About',
            'navig.about.short' => 'navig.about.short',
            'navig.contact' => 'Contact',
            'navig.contact.short' => 'C', // Should not be used because its last
        ]);

        // Expected result
        $expected = [
            [
                'route' => route('home'),
                'translation' => __('navig.home.short'),
                'class' => '',
            ],
            [
                'route' => route('home') . '/about',
                'translation' => __('navig.about'),
                'class' => '',
            ],
            [
                'route' => route('home') . '/about/contact',
                'translation' => __('navig.contact'),
                'class' => '',
            ],
        ];

        $this->assertEquals($expected, Helpers::parseUrlPath());
    }

    public function test_parse_url_path_with_missing_translation()
    {
        // Mock the request path
        $this->mockRequestPath('about/unknown');

        $this->mockTranslations([
            'navig.home' => 'Home',
            'navig.home.short' => 'navig.home.short',
            'navig.about' => 'About',
            'navig.about.short' => 'navig.about.short',
            'navig.unknown' => 'navig.unknown',
            'navig.unknown.short' => 'navig.unknown.short',
        ]);

        // Expected result
        $expected = [
            [
                'route' => route('home'),
                'translation' => __('navig.home'),
                'class' => '',
            ],
            [
                'route' => route('home') . '/about',
                'translation' => __('navig.about'),
                'class' => '',
            ],
            [
                'route' => route('home') . '/about/unknown',
                'translation' => 'navig.unknown',
                'class' => 'text-danger',
            ],
        ];

        $this->assertEquals($expected, Helpers::parseUrlPath());
    }

    public function test_create_title()
    {
        // Mock the request path
        $this->mockRequestPath('about/contact');

        // Expected result
        $expected = __('navig.contact') . ' – ' . __('navig.about') . ' – ' . __('navig.home');

        $this->assertEquals($expected, Helpers::createTitle());
    }
}
