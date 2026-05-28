<?php

namespace Tests\Unit\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use InternetGuru\LaravelCommon\Exceptions\GeolocationServiceException;
use InternetGuru\LaravelCommon\Services\GeolocationService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Torann\GeoIP\Location;

class GeolocationServiceTest extends TestCase
{
    private GeolocationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GeolocationService();

        // Clear cache and rate limiter before each test
        Cache::flush();
        RateLimiter::clear('geoip-lookup');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_location_returns_cached_location()
    {
        $ip = '8.8.8.8';
        $mockLocation = new Location([
            'ip' => $ip,
            'country' => 'US',
            'city' => 'Mountain View',
        ]);

        Cache::put("geoip_$ip", $mockLocation);

        $location = $this->service->getLocation($ip);

        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals($ip, $location->ip);
    }

    public function test_get_location_throws_exception_when_rate_limited()
    {
        $ip = '8.8.8.8';

        // Simulate rate limit exceeded
        for ($i = 0; $i < 6; $i++) {
            RateLimiter::hit('geoip-lookup', 60);
        }

        $this->expectException(GeolocationServiceException::class);
        $this->expectExceptionMessage('Rate limit exceeded for GeoIP lookups.');

        $this->service->getLocation($ip);
    }

    public function test_get_location_throws_exception_for_bot_user_agents()
    {
        $bots = [
            'facebookexternalhit/1.1 Facebot Twitterbot/1.0',
            'Mozilla/5.0 (compatible; Googlebot/2.1)',
            'Mozilla/5.0 (compatible; Bingbot/2.0)',
            'Twitterbot/1.0',
            'LinkedInBot/1.0',
        ];

        foreach ($bots as $userAgent) {
            $this->app['request']->headers->set('User-Agent', $userAgent);

            $this->expectException(GeolocationServiceException::class);
            $this->expectExceptionMessage('Bot detected, skipping GeoIP lookup.');

            $this->service->getLocation('8.8.8.8');
        }
    }

    public function test_get_location_does_not_skip_regular_browsers()
    {
        $ip = '8.8.8.8';
        $mockLocation = new Location(['ip' => $ip, 'country' => 'US', 'city' => 'Mountain View']);

        Cache::put("geoip_$ip", $mockLocation);

        $this->app['request']->headers->set('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');

        $location = $this->service->getLocation($ip);

        $this->assertEquals($ip, $location->ip);
    }

    public function test_get_location_converts_http_timeout_to_catchable_exception()
    {
        $this->app['config']->set('geoip.service', 'ipapi');
        $this->app['config']->set('geoip.services.ipapi', ['lang' => 'en']);

        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));
        Log::shouldReceive('warning')->once()->with('GeoIP lookup timed out.', Mockery::any());

        $this->expectException(GeolocationServiceException::class);
        $this->expectExceptionMessage('GeoIP lookup timed out:');

        $this->service->getLocation('8.8.8.8');
    }

    public function test_get_location_restores_time_limit_after_timeout()
    {
        $this->app['config']->set('geoip.service', 'ipapi');
        $this->app['config']->set('geoip.services.ipapi', ['lang' => 'en']);

        $originalLimit = (int) ini_get('max_execution_time');

        Http::fake(fn () => throw new ConnectionException('timeout'));
        Log::shouldReceive('warning')->once();

        try {
            $this->service->getLocation('8.8.8.8');
        } catch (GeolocationServiceException) {
        }

        $this->assertEquals($originalLimit, (int) ini_get('max_execution_time'));
    }
}
