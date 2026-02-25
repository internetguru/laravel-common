<?php

namespace InternetGuru\LaravelCommon\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InjectUmamiScript
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $websiteId = config('ig-common.umami_website_id');

        if (empty($websiteId) || ! $this->isHtmlResponse($response)) {
            return $response;
        }

        $src = config('ig-common.umami_src');
        $script = '<script defer src="' . e($src) . '" data-website-id="' . e($websiteId) . '"></script>';

        $content = $response->getContent();
        $content = str_replace('</head>', $script . '</head>', $content);

        $response->setContent($content);

        return $response;
    }

    protected function isHtmlResponse($response): bool
    {
        return $response instanceof Response
            && ! $response->isRedirection()
            && $response->headers->get('Content-Type')
            && str_contains($response->headers->get('Content-Type'), 'text/html');
    }
}
