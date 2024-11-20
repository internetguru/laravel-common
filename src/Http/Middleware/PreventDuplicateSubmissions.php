<?php

namespace InternetGuru\LaravelCommon\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PreventDuplicateSubmissions
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->isMethod('post')) {
            // Generate a unique key based on the request ignoring the g-recaptcha-response field
            $requestKey = sha1($request->ip() . '|' . $request->path() . '|' . serialize($request->except('g-recaptcha-response')));

            // Check if the request has already been processed
            if (cache()->has($requestKey)) {
                // Duplicate request detected
                return back()->withInput()->withErrors([
                    'error' => __('ig-common::messages.duplicate_submission'),
                ]);
            }

            // Store the key in cache with a TTL
            cache()->put($requestKey, true, now()->addMinutes(1));
        }

        return $next($request);
    }
}
