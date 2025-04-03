<?php

namespace InternetGuru\LaravelCommon\Http\Middleware;

use Closure;

class SetPrevPage
{
    public function handle($request, Closure $next)
    {
        // accept only GET requests
        if (! $request->isMethod('get')) {
            return $next($request);
        }

        // do not accept ajax requests
        if ($request->ajax()) {
            return $next($request);
        }

        // save current and previous page to session
        if ($request->route() !== null) {
            $currentUrl = $request->url();

            // Don't track the same page multiple times in a row
            if (session()->get('currentPage') !== $currentUrl) {
                session()->put('prevPage', session()->get('currentPage'));
                session()->put('currentPage', $currentUrl);
            }
        }

        return $next($request);
    }
}
