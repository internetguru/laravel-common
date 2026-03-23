<?php

namespace InternetGuru\LaravelCommon\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectToLangDomain
{
    public function handle(Request $request, Closure $next)
    {
        $langDomains = config('ig-common.lang_domains', []);

        if (empty($langDomains)) {
            return $next($request);
        }

        $currentHost = $request->getHost();
        $locale = app()->getLocale();
        $domainToLang = array_flip($langDomains);

        if (isset($domainToLang[$currentHost])) {
            $expectedLang = $domainToLang[$currentHost];

            if ($locale !== $expectedLang) {
                if (isset($langDomains[$locale])) {
                    $url = $request->getScheme() . '://' . $langDomains[$locale] . $request->getRequestUri();

                    return redirect()->away($url, 302);
                }

                $mainDomain = config('app.www');
                if ($mainDomain && $currentHost !== $mainDomain) {
                    $url = $request->getScheme() . '://' . $mainDomain . $request->getRequestUri();

                    return redirect()->away($url, 302);
                }
            }

            app()->setLocale($expectedLang);
            session(['locale' => $expectedLang]);

            return $next($request);
        }

        if (isset($langDomains[$locale])) {
            $url = $request->getScheme() . '://' . $langDomains[$locale] . $request->getRequestUri();

            return redirect()->away($url, 302);
        }

        return $next($request);
    }
}
