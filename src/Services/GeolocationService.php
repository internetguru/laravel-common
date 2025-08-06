<?php

namespace InternetGuru\LaravelCommon\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Torann\GeoIP\Facades\GeoIP;
use Torann\GeoIP\Location;

class GeolocationService
{
    public function getLocation(?string $ip = null): Location
    {
        if (! $ip) {
            $ip = request()->ip();
        }

        $cacheKey = "geoip_$ip";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        if (RateLimiter::tooManyAttempts('geoip-lookup', 5)) {
            Log::warning('Rate limit exceeded for GeoIP lookups.');

            return new Location(config('location'));
        }

        try {
            $location = GeoIP::getLocation($ip);
            RateLimiter::hit('geoip-lookup', 60); // 60 seconds cooldown
        } catch (\Exception $e) {
            Log::error('GeoIP lookup failed: ' . $e->getMessage());

            return new Location(config('location'));
        }

        if (! $location) {
            Log::error('Could not resolve location from IP: ' . $ip);

            return new Location(config('location'));
        }

        Cache::put($cacheKey, $location);

        return $location;
    }
}
