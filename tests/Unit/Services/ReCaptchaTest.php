<?php

namespace Tests\Unit\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use InternetGuru\LaravelCommon\Services\ReCaptcha;
use Tests\TestCase;

class ReCaptchaTest extends TestCase
{
    private ReCaptcha $recaptcha;

    protected function setUp(): void
    {
        parent::setUp();
        $this->recaptcha = new ReCaptcha();
    }

    public function test_is_disabled_in_local_environment()
    {
        app()['env'] = 'local';

        $this->assertFalse($this->recaptcha->isEnabled());

        app()['env'] = 'testing';
    }

    public function test_is_disabled_in_testing_environment()
    {
        app()['env'] = 'testing';

        $this->assertFalse($this->recaptcha->isEnabled());
    }

    public function test_is_disabled_in_demo_mode()
    {
        Config::set('app.demo', true);
        Config::set('app.env', 'production');
        app()['env'] = 'production';

        $this->assertFalse($this->recaptcha->isEnabled());

        Config::set('app.demo', false);
        app()['env'] = 'testing';
    }

    public function test_is_disabled_when_user_is_authenticated()
    {
        Config::set('app.env', 'production');
        Config::set('app.demo', false);
        app()['env'] = 'production';

        // Mock authenticated user
        $this->actingAs(new \Illuminate\Foundation\Auth\User());

        $this->assertFalse($this->recaptcha->isEnabled());

        app()['env'] = 'testing';
    }

    public function test_is_enabled_in_production_for_guests()
    {
        Config::set('app.env', 'production');
        Config::set('app.demo', false);
        app()['env'] = 'production';

        // Make sure no user is authenticated
        auth()->logout();

        $this->assertTrue($this->recaptcha->isEnabled());

        app()['env'] = 'testing';
    }

    public function test_validate_skips_when_disabled()
    {
        // In testing environment, recaptcha is disabled
        $request = Request::create('/', 'POST', []);

        // Should not throw exception
        $this->recaptcha->validate($request);

        $this->assertTrue(true);
    }

    public function test_validate_requires_recaptcha_response_when_enabled()
    {
        Config::set('app.env', 'production');
        Config::set('app.demo', false);
        app()['env'] = 'production';

        // Mock guest user
        auth()->logout();

        $request = Request::create('/', 'POST', []);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->recaptcha->validate($request);

        app()['env'] = 'testing';
    }
}
