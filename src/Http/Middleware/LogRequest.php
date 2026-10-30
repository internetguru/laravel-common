<?php

namespace InternetGuru\LaravelCommon\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InternetGuru\LaravelCommon\Logging\LivewirePayload;
use Symfony\Component\HttpFoundation\Response;

class LogRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('ig-common.log_requests')
            && ! $request->isMethod('GET')
            && ! LivewirePayload::isRefreshOnly($request)) {
            Log::info('Incoming request');
        }

        return $next($request);
    }
}
