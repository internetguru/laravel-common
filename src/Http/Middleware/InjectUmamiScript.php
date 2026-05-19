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

        if (config('ig-common.umami_identify', false)) {
            $identifyData = $this->buildIdentifyData();
            $identifyJson = json_encode($identifyData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            $script .= '<script>window.addEventListener("load",function(){typeof umami!="undefined"&&umami.identify(' . $identifyJson . ');});</script>';
        }

        $content = $response->getContent();
        $content = str_replace('</head>', $script . '</head>', $content);

        $response->setContent($content);

        return $response;
    }

    protected function buildIdentifyData(): array
    {
        if (! auth()->check()) {
            return ['user_type' => 'guest'];
        }

        $userId = (string) auth()->id();
        $id = config('ig-common.umami_identify_hash', true)
            ? substr(hash('sha256', $userId), 0, 50)
            : substr($userId, 0, 50);

        $data = ['id' => $id, 'user_type' => 'logged'];

        $role = auth()->user()->role ?? null;
        if ($role !== null) {
            $data['user_role'] = (string) $role;
        }

        return $data;
    }

    protected function isHtmlResponse($response): bool
    {
        return $response instanceof Response
            && ! $response->isRedirection()
            && $response->headers->get('Content-Type')
            && str_contains($response->headers->get('Content-Type'), 'text/html');
    }
}
