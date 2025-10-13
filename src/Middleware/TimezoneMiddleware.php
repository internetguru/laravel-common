<?php

namespace InternetGuru\LaravelCommon\Middleware;

use Closure;
use Illuminate\Http\Request;
use InternetGuru\LaravelCommon\Exceptions\GeolocationServiceException;
use InternetGuru\LaravelCommon\Services\GeolocationService;

class TimezoneMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (session()->has('display_timezone')) {
            return session('display_timezone');
        }

        $settingsIp = config('app.settings_ip');
        $ip = $request->ip();
        // For local development, use a fixed IP if provided in settings
        // For production use the actual client IP
        if ($settingsIp && config('app.env') === 'local') {
            $ip = $settingsIp;
        }

        try {
            $geoService = app(GeolocationService::class);
            $timezone = $geoService->getLocation($ip)->timezone ?? config('geoip.default_location.timezone');
        } catch (GeolocationServiceException $ex) {
            $timezone = config('geoip.default_location.timezone');
        }

        session(['display_timezone' => $timezone]);

        return $next($request);
    }
}
