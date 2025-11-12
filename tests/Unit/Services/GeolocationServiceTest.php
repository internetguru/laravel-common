<?php

namespace Tests\Unit\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use InternetGuru\LaravelCommon\Exceptions\GeolocationServiceException;
use InternetGuru\LaravelCommon\Services\GeolocationService;
use Mockery;
use Tests\TestCase;
use Torann\GeoIP\Facades\GeoIP;
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
}
