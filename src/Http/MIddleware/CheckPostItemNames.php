<?php

namespace InternetGuru\LaravelCommon\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CheckPostItemNames
{
    public function handle($request, Closure $next)
    {
        if (empty($request->post())) {
            return $next($request);
        }

        $invalidKeys = [];

        foreach ($request->post() as $key => $value) {
            if (strpos($key, '.') !== false) {
                $invalidKeys[] = $key;
            }
        }

        if (! empty($invalidKeys)) {
            $message = 'Invalid POST parameter names containing dots: ' . implode(', ', $invalidKeys);

            if (app()->isProduction()) {
                Log::warning($message);
            } else {
                throw new HttpException(400, $message);
            }
        }

        return $next($request);
    }
}
