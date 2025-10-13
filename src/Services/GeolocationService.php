<?php

namespace InternetGuru\LaravelCommon\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use InternetGuru\LaravelCommon\Exceptions\GeolocationServiceException;
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
            throw new GeolocationServiceException('Rate limit exceeded for GeoIP lookups.');
        }

        try {
            $location = GeoIP::getLocation($ip);
            RateLimiter::hit('geoip-lookup', 60); // 60 seconds cooldown
        } catch (\Exception $e) {
            throw new GeolocationServiceException('GeoIP lookup failed: ' . $e->getMessage());
        }

        if (! $location) {
            throw new GeolocationServiceException('Could not resolve location from IP: ' . $ip);
        }

        Cache::put($cacheKey, $location);

        return $location;
    }
}
