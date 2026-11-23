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

    public function test_create_title()
    {
        // Define routes for testing
        Route::get('/about', function () {})->name('about');
        Route::get('/contact', function () {})->name('contact');

        // Mock translations
        app('translator')->addLines([
            'navig.home' => 'Home',
            'navig.about' => 'About',
        ], 'en', 'ig-common');

        $this->get('/');
        $title = Helpers::createTitle();
        $this->assertStringContainsString('Home', $title);

        $this->get('/about');
        $title = Helpers::createTitle();
        $this->assertStringContainsString('About', $title);
    }

    public function test_get_email_client_link_with_mailpit_local()
    {
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', 'mailpit');
        Config::set('app.env', 'local');
        Config::set('app.url', 'http://localhost');

        $link = Helpers::getEmailClientLink();

        $this->assertStringContainsString('localhost:8025', $link);
        $this->assertStringContainsString('href=', $link);
    }

    public function test_get_email_client_link_with_mailpit_on_its_own_port()
    {
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', 'mailpit');
        Config::set('app.env', 'local');
        Config::set('app.url', 'http://192.168.1.10:8001');
        Config::set('ig-common.mailpit_port', 8101);

        $link = Helpers::getEmailClientLink();

        $this->assertStringContainsString('href="http://192.168.1.10:8101"', $link);
    }

    public function test_get_email_client_link_with_mailpit_production()
    {
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', 'mailpit');
        Config::set('app.env', 'production');
        Config::set('app.url', 'https://example.com');

        $link = Helpers::getEmailClientLink();

        $this->assertStringContainsString('mail.example.com', $link);
        $this->assertStringContainsString('href=', $link);
    }

    public function test_get_email_client_link_without_mailpit()
    {
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', 'smtp.gmail.com');

        $link = Helpers::getEmailClientLink();

        $this->assertEquals('', $link);
    }

    public function test_is_bot_recognizes_crawlers_and_link_previews_but_not_browsers()
    {
        $this->assertTrue(Helpers::isBot('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'));
        $this->assertTrue(Helpers::isBot('facebookexternalhit/1.1'));
        $this->assertTrue(Helpers::isBot('WhatsApp/2.23.20.0'));
        $this->assertFalse(Helpers::isBot('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36'));
        $this->assertFalse(Helpers::isBot(''));
    }

    public function test_is_bot_reads_the_current_request_by_default()
    {
        $this->app['request']->headers->set('User-Agent', 'Mozilla/5.0 (compatible; Bingbot/2.0)');

        $this->assertTrue(Helpers::isBot());
    }
}
