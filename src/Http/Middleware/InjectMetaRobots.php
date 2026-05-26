<?php

namespace InternetGuru\LaravelCommon\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InjectMetaRobots
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $robots = config('ig-common.meta_robots');

        if (empty($robots) || ! $this->isHtmlResponse($response)) {
            return $response;
        }

        $meta = "\n" . '<meta name="robots" content="' . e($robots) . '"/>' . "\n";

        $content = $response->getContent();
        $content = str_replace('</head>', $meta . '</head>', $content);

        $response->setContent($content);

        return $response;
    }

    protected function isHtmlResponse(mixed $response): bool
    {
        return $response instanceof Response
            && ! $response->isRedirection()
            && $response->headers->get('Content-Type')
            && str_contains($response->headers->get('Content-Type'), 'text/html');
    }
}
