<?php

namespace InternetGuru\LaravelCommon\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/**
 * Checks every image on an HTML page for what it needs to be laid out and read:
 * both dimensions, so the page does not jump as it loads, an alt, and for a
 * local photo laravel-images, which resizes and converts it. Problems
 * are logged once a day per page; in debug mode the images are also outlined.
 */
class ReportImageProblems
{
    public const CRITICAL = 'critical';

    /** A one-pixel PNG the image check page shows in each of its cases. */
    public const SAMPLE_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVR4nGPIKa0FAAKvAV+IUpknAAAAAElFTkSuQmCC';

    public const SEVERE = 'severe';

    public const MEDIUM = 'medium';

    public const LOW = 'low';

    private const LOG_LEVELS = [self::CRITICAL => 'error', self::SEVERE => 'warning', self::MEDIUM => 'notice', self::LOW => 'info'];

    /** Formats laravel-images resizes; vector images and anything else are served as they are. */
    private const RASTER_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];

    private const STYLE = '<style>'
        . 'img[data-ig-image-warning]{outline-offset:-0.2rem}'
        . 'img[data-ig-image-warning=critical]{outline:0.6rem double #dc3545}'
        . 'img[data-ig-image-warning=severe]{outline:0.4rem solid #dc3545}'
        . 'img[data-ig-image-warning=medium]{outline:0.4rem solid #fd7e14}'
        . 'img[data-ig-image-warning=low]{outline:0.2rem dotted #fd7e14}'
        . '</style>';

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! config('ig-common.image_warnings') || ! $this->isHtmlResponse($response)) {
            return $response;
        }

        $mark = (bool) config('app.debug');
        $problems = [];
        $content = preg_replace_callback('/<img\b[^>]*>/i', function (array $match) use ($request, $mark, &$problems) {
            $imageProblems = $this->problemsOf($match[0], $request);
            if ($imageProblems === []) {
                return $match[0];
            }
            $problems[$this->attributes($match[0])['src'] ?? $match[0]] = $imageProblems;

            return $mark ? $this->marked($match[0], $imageProblems) : $match[0];
        }, $response->getContent());

        if ($problems === []) {
            return $response;
        }

        $this->log($request, $problems);
        if ($mark) {
            $response->setContent(str_replace('</head>', self::STYLE . '</head>', $content));
        }

        return $response;
    }

    /**
     * Problem descriptions keyed by severity, most severe first.
     *
     * @return array<string, list<string>>
     */
    protected function problemsOf(string $tag, Request $request): array
    {
        $attributes = $this->attributes($tag);
        $problems = [];
        if ($this->isUnprocessedLocalImage($attributes['src'] ?? '', $request)) {
            $problems[self::CRITICAL][] = 'not served by laravel-images';
        }
        foreach (['width', 'height'] as $dimension) {
            if (! $this->isPresent($attributes, $dimension)) {
                $problems[self::SEVERE][] = "missing {$dimension}";
            }
        }
        if (! array_key_exists('alt', $attributes) && ! $this->isBound($attributes, 'alt')) {
            $problems[self::MEDIUM][] = 'missing alt';
        } elseif (($attributes['alt'] ?? null) === '') {
            $problems[self::LOW][] = 'empty alt';
        }

        return $problems;
    }

    /**
     * A raster image of this site's own that skips laravel-images, so it is never resized, converted
     * or signed. Vite's build output is left alone: it is bundled on purpose.
     */
    protected function isUnprocessedLocalImage(string $src, Request $request): bool
    {
        if (! Route::has('ig-images.show') || $src === '' || preg_match('/^(data|blob):/i', $src)) {
            return false;
        }

        $url = parse_url($src);
        if ($url === false) {
            return false;
        }
        if (isset($url['host'])) {
            $authority = $url['host'] . (isset($url['port']) ? ':' . $url['port'] : '');
            if (strcasecmp($authority, $request->getHttpHost()) !== 0) {
                return false;
            }
        }

        $path = '/' . ltrim($url['path'] ?? '', '/');
        $imageRoute = '/' . trim((string) config('ig-images.route'), '/') . '/';
        if (str_starts_with($path, $imageRoute) || str_starts_with($path, '/build/')) {
            return false;
        }

        return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::RASTER_EXTENSIONS, true);
    }

    /** @return array<string, string> */
    protected function attributes(string $tag): array
    {
        preg_match_all('/([^\s=\/>]+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+)))?/', substr($tag, 4), $matches, PREG_SET_ORDER);
        $attributes = [];
        foreach ($matches as $match) {
            $attributes[strtolower($match[1])] = html_entity_decode(($match[2] ?? '') . ($match[3] ?? '') . ($match[4] ?? ''));
        }

        return $attributes;
    }

    /** @param  array<string, string>  $attributes */
    protected function isPresent(array $attributes, string $name): bool
    {
        return trim($attributes[$name] ?? '') !== '' || $this->isBound($attributes, $name);
    }

    /** An Alpine or Vue binding sets the attribute in the browser. */
    protected function isBound(array $attributes, string $name): bool
    {
        return array_key_exists(":{$name}", $attributes) || array_key_exists("x-bind:{$name}", $attributes);
    }

    /** @param  array<string, list<string>>  $problems */
    protected function marked(string $tag, array $problems): string
    {
        $markup = ' data-ig-image-warning="' . array_key_first($problems) . '"';
        if (! array_key_exists('title', $this->attributes($tag))) {
            $markup .= ' title="' . e('Image: ' . implode(', ', array_merge(...array_values($problems)))) . '"';
        }

        return preg_replace('/^<img\b/i', '<img' . $markup, $tag);
    }

    /** @param  array<string, array<string, list<string>>>  $problems */
    protected function log(Request $request, array $problems): void
    {
        $key = 'ig-image-problems:' . md5($request->path() . serialize($problems));
        if (! Cache::add($key, true, now()->addDay())) {
            return;
        }

        $severities = array_merge(...array_map('array_keys', array_values($problems)));
        $severity = collect(array_keys(self::LOG_LEVELS))->first(fn ($level) => in_array($level, $severities, true));
        $images = array_map(fn ($imageProblems) => implode(', ', array_merge(...array_values($imageProblems))), $problems);

        Log::log(self::LOG_LEVELS[$severity], 'Image problems', ['url' => $request->fullUrl(), 'images' => $images]);
    }

    protected function isHtmlResponse(mixed $response): bool
    {
        return $response instanceof Response
            && ! $response->isRedirection()
            && $response->headers->get('Content-Type')
            && str_contains($response->headers->get('Content-Type'), 'text/html');
    }
}
