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
use PHPUnit\Framework\Attributes\Group;
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

    public function test_get_location_parses_ipapi_response_including_currency()
    {
        $ip = '8.8.8.8';
        $this->app['config']->set('geoip.service', 'ipapi');
        $this->app['config']->set('geoip.services.ipapi', ['lang' => 'en']);

        Http::fake([
            "http://ip-api.com/json/{$ip}*" => Http::response([
                'status' => 'success',
                'continent' => 'North America',
                'continentCode' => 'NA',
                'country' => 'United States',
                'countryCode' => 'US',
                'region' => 'VA',
                'regionName' => 'Virginia',
                'city' => 'Ashburn',
                'zip' => '20149',
                'lat' => 39.03,
                'lon' => -77.5,
                'timezone' => 'America/New_York',
                'currency' => 'USD',
            ]),
        ]);

        $location = $this->service->getLocation($ip);

        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('USD', $location->currency);
        $this->assertEquals('US', $location->iso_code);
        $this->assertEquals('Ashburn', $location->city);
        $this->assertEquals('20149', $location->postal_code);
        $this->assertEquals(39.03, $location->lat);
        $this->assertEquals(-77.5, $location->lon);
        $this->assertEquals('America/New_York', $location->timezone);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'currency')
                && ! str_contains($request->url(), 'fields=49663');
        });
    }

    /**
     * Hits the real ip-api.com service (no HTTP fake) to catch upstream API
     * changes, e.g. response fields being renamed or dropped.
     */
    #[Group('integration')]
    public function test_get_location_against_real_ipapi_service()
    {
        $ip = '8.8.8.8';
        $this->app['config']->set('geoip.service', 'ipapi');
        $this->app['config']->set('geoip.services.ipapi', ['lang' => 'en']);

        try {
            $location = $this->service->getLocation($ip);
        } catch (GeolocationServiceException $e) {
            $this->markTestSkipped('Real ip-api.com service unreachable: ' . $e->getMessage());
        }

        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals($ip, $location->ip);
        $this->assertEquals('US', $location->iso_code);
        $this->assertNotEmpty($location->country);
        $this->assertNotEmpty($location->city);
        $this->assertNotEmpty($location->lat);
        $this->assertNotEmpty($location->lon);
        $this->assertNotEmpty($location->timezone);
        $this->assertNotEmpty($location->currency);
        $this->assertEquals('USD', $location->currency);
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
