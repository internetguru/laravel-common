<?php

namespace InternetGuru\LaravelCommon\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

class PreventDuplicateSubmissions
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->isMethod('post') && ! Livewire::isLivewireRequest()) {
            // Generate a unique key based on the request ignoring the g-recaptcha-response field
            $input = $request->except('g-recaptcha-response');

            // UploadedFile is not serializable, so replace it with its metadata
            array_walk_recursive($input, function (&$value) {
                if ($value instanceof UploadedFile) {
                    $value = $value->getClientOriginalName() . '|' . $value->getSize();
                }
            });

            $requestKey = sha1($request->ip() . '|' . $request->path() . '|' . serialize($input));

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
