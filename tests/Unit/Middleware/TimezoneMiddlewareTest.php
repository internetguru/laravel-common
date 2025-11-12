<?php

namespace Tests\Unit\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use InternetGuru\LaravelCommon\Exceptions\GeolocationServiceException;
use InternetGuru\LaravelCommon\Middleware\TimezoneMiddleware;
use InternetGuru\LaravelCommon\Services\GeolocationService;
use Mockery;
use Tests\TestCase;
use Torann\GeoIP\Location;

class TimezoneMiddlewareTest extends TestCase
{
    private TimezoneMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new TimezoneMiddleware();
        Session::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_skips_if_timezone_already_in_session()
    {
        session(['display_timezone' => 'America/New_York']);

        $request = Request::create('/', 'GET');
        $next = function ($req) {
            return response('OK');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals('America/New_York', session('display_timezone'));
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_sets_timezone_from_geolocation_service()
    {
        Config::set('geoip.default_location.timezone', 'UTC');

        $mockLocation = new Location([
            'ip' => '8.8.8.8',
            'timezone' => 'America/Los_Angeles',
        ]);

        $geoService = Mockery::mock(GeolocationService::class);
        $geoService->shouldReceive('getLocation')
            ->once()
            ->andReturn($mockLocation);

        $this->app->instance(GeolocationService::class, $geoService);

        $request = Request::create('/', 'GET');
        $request->server->set('REMOTE_ADDR', '8.8.8.8');

        $next = function ($req) {
            return response('OK');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals('America/Los_Angeles', session('display_timezone'));
    }

    public function test_uses_settings_ip_in_local_environment()
    {
        Config::set('app.env', 'local');
        Config::set('app.settings_ip', '8.8.8.8');
        Config::set('geoip.default_location.timezone', 'UTC');

        $mockLocation = new Location([
            'ip' => '8.8.8.8',
            'timezone' => 'America/New_York',
        ]);

        $geoService = Mockery::mock(GeolocationService::class);
        $geoService->shouldReceive('getLocation')
            ->once()
            ->with('8.8.8.8')
            ->andReturn($mockLocation);

        $this->app->instance(GeolocationService::class, $geoService);

        $request = Request::create('/', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $next = function ($req) {
            return response('OK');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals('America/New_York', session('display_timezone'));
    }

    public function test_uses_default_timezone_on_geolocation_exception()
    {
        Config::set('geoip.default_location.timezone', 'UTC');

        $geoService = Mockery::mock(GeolocationService::class);
        $geoService->shouldReceive('getLocation')
            ->once()
            ->andThrow(new GeolocationServiceException('Service unavailable'));

        $this->app->instance(GeolocationService::class, $geoService);

        $request = Request::create('/', 'GET');
        $next = function ($req) {
            return response('OK');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals('UTC', session('display_timezone'));
    }

    public function test_uses_default_timezone_when_location_has_no_timezone()
    {
        Config::set('geoip.default_location.timezone', 'Europe/Prague');

        $mockLocation = new Location([
            'ip' => '8.8.8.8',
            // No timezone property
        ]);

        $geoService = Mockery::mock(GeolocationService::class);
        $geoService->shouldReceive('getLocation')
            ->once()
            ->andReturn($mockLocation);

        $this->app->instance(GeolocationService::class, $geoService);

        $request = Request::create('/', 'GET');
        $next = function ($req) {
            return response('OK');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals('Europe/Prague', session('display_timezone'));
    }
}
