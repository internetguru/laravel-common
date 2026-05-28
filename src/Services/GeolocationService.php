<?php

namespace InternetGuru\LaravelCommon\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use InternetGuru\LaravelCommon\Exceptions\GeolocationServiceException;
use Torann\GeoIP\Facades\GeoIP;
use Torann\GeoIP\Location;

class GeolocationService
{
    protected function isBot(): bool
    {
        $userAgent = request()->userAgent() ?? '';

        return (bool) preg_match(
            '/(bot|crawler|spider|facebookexternalhit|Twitterbot|Googlebot|Bingbot|YandexBot|DuckDuckBot|Slurp|Baiduspider|LinkedInBot|WhatsApp|Discordbot|Slackbot|TelegramBot)/i',
            $userAgent
        );
    }

    protected function fetchLocation(string $ip): Location
    {
        if (config('geoip.service') !== 'ipapi') {
            return GeoIP::getLocation($ip);
        }

        $config = config('geoip.services.ipapi', []);
        $key = $config['key'] ?? null;
        $lang = $config['lang'] ?? 'en';
        $secure = $config['secure'] ?? false;

        $baseUrl = $key
            ? ($secure ? 'https' : 'http') . '://pro.ip-api.com/json/'
            : 'http://ip-api.com/json/';

        $query = ['fields' => 49663, 'lang' => $lang];

        if ($key) {
            $query['key'] = $key;
        }

        $response = Http::timeout(5)->get("{$baseUrl}{$ip}", $query);

        if ($response->failed()) {
            throw new \Exception('HTTP request failed with status ' . $response->status());
        }

        $data = $response->json();

        if (($data['status'] ?? null) !== 'success') {
            throw new \Exception('Request failed (' . ($data['message'] ?? 'unknown') . ')');
        }

        return new Location([
            'ip' => $ip,
            'iso_code' => $data['countryCode'] ?? null,
            'country' => $data['country'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['region'] ?? null,
            'state_name' => $data['regionName'] ?? null,
            'postal_code' => $data['zip'] ?? null,
            'lat' => $data['lat'] ?? null,
            'lon' => $data['lon'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'continent' => $data['continentCode'] ?? null,
            'currency' => $data['currency'] ?? null,
            'default' => false,
        ]);
    }

    public function getLocation(?string $ip = null): Location
    {
        if ($this->isBot()) {
            throw new GeolocationServiceException('Bot detected, skipping GeoIP lookup.');
        }

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

        $previousLimit = (int) ini_get('max_execution_time');
        set_time_limit(0);

        try {
            $location = $this->fetchLocation($ip);
            RateLimiter::hit('geoip-lookup', 60); // 60 seconds cooldown
        } catch (ConnectionException $e) {
            Log::warning('GeoIP lookup timed out.', ['ip' => $ip, 'message' => $e->getMessage()]);
            throw new GeolocationServiceException('GeoIP lookup timed out: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new GeolocationServiceException('GeoIP lookup failed: ' . $e->getMessage());
        } finally {
            set_time_limit($previousLimit);
        }

        if (! $location) {
            throw new GeolocationServiceException('Could not resolve location from IP: ' . $ip);
        }

        Cache::put($cacheKey, $location);

        return $location;
    }
}
