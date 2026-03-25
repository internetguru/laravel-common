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
                    $uri = $request->getRequestUri();
                    $separator = str_contains($uri, '?') ? '&' : '?';
                    $url = $request->getScheme() . '://' . $langDomains[$locale] . $uri . $separator . 'lang=' . $locale;

                    return redirect()->away($url, 302);
                }

                $mainDomain = config('app.www');
                if ($mainDomain && $currentHost !== $mainDomain) {
                    $uri = $request->getRequestUri();
                    $separator = str_contains($uri, '?') ? '&' : '?';
                    $url = $request->getScheme() . '://' . $mainDomain . $uri . $separator . 'lang=' . $locale;

                    return redirect()->away($url, 302);
                }
            }

            app()->setLocale($expectedLang);
            session(['locale' => $expectedLang]);

            return $next($request);
        }

        if (isset($langDomains[$locale])) {
            $uri = $request->getRequestUri();
            $separator = str_contains($uri, '?') ? '&' : '?';
            $url = $request->getScheme() . '://' . $langDomains[$locale] . $uri . $separator . 'lang=' . $locale;

            return redirect()->away($url, 302);
        }

        return $next($request);
    }
}
