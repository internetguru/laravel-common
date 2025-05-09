<?php

namespace InternetGuru\LaravelCommon\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InternetGuru\LaravelCommon\Contracts\ReCaptchaInterface;
use Lunaweb\RecaptchaV3\RecaptchaV3;

class InjectRecaptchaScript
{
    protected $recaptcha;

    public function __construct(ReCaptchaInterface $recaptcha)
    {
        $this->recaptcha = $recaptcha;
    }

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only inject if ReCaptcha is enabled and the response is HTML
        if (! $this->recaptcha->isEnabled() || ! $this->isHtmlResponse($response)) {
            return $response;
        }

        // Insert ReCaptcha script before the closing </head> tag
        $recaptchaScript = app(RecaptchaV3::class)->initJs();
        $content = $response->getContent();
        $content = str_replace('</head>', $recaptchaScript . '</head>', $content);

        $response->setContent($content);

        return $response;
    }

    protected function isHtmlResponse($response)
    {
        return $response instanceof Response &&
               !$response->isRedirection() &&
               $response->headers->get('Content-Type') &&
               strpos($response->headers->get('Content-Type'), 'text/html') !== false;
    }
}