<?php

namespace InternetGuru\LaravelCommon\Support;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Helpers
{
    /**
     * Get the app info as an array.
     *
     * Returns an array with the following keys:
     *  - app_name: Config app.name
     *  - environment: Config app.env
     *  - version: VERSION file content
     *  - branch: Git branch or [detached]
     *  - commit: Git commit
     */
    public static function getAppInfoArray(): array
    {
        $info['app_name'] = config('app.name');
        $info['environment'] = config('app.env');

        // Using Storage to access root files
        $info['version'] = trim(Storage::disk('root')->get('VERSION'));

        $branch = '[detached]';
        $commit = trim(Storage::disk('root')->get('.git/HEAD'));
        if (substr($commit, 0, 10) == 'ref: refs/') {
            $branch = $commit;
            $commit = trim(Storage::disk('root')->get('.git/' . substr($branch, 5)));
        }

        $info['branch'] = basename($branch);
        $info['commit'] = substr($commit, 0, 7);

        return $info;
    }

    public static function getAppInfo(): string
    {
        return implode(' ', self::getAppInfoArray());
    }

    /**
     * Parse the URL path and return an array of segments.
     * If there is a short translation, it will be used instead of the full translation.
     * If translation is not found, the segment will be marked with a text-danger class.
     *
     * Returns an array of segments with the following keys:
     *  - route: The route to the segment
     *  - translation: The translation of the segment
     *  - class: Additional classes to add to the segment
     */
    public static function parseUrlPath(string $homeRoute = 'home', int $skipFirst = 0): array
    {
        $url = request()->path();
        $urlParts = explode('/', trim($url, '/'));

        // Skip first N segments
        for ($i = 0; $i < $skipFirst; $i++) {
            array_shift($urlParts);
        }
        // clean empty parts
        $urlParts = array_filter($urlParts);
        // Add root segment
        array_unshift($urlParts, '');

        // If error page, return error
        if (isset(app('view')->getSections()['code']) && isset(app('view')->getSections()['message'])) {
            $urlParts = ['', 'error'];
        }

        $currentPath = '';
        $segments = [];
        $totalParts = count($urlParts);
        $parameters = [];

        foreach ($urlParts as $index => $segment) {
            $currentPath = $currentPath == '/' ? $currentPath . $segment : $currentPath . '/' . $segment;
            try {
                $route = Route::getRoutes()->match(request()->create($currentPath));
                $routeName = $route->getName();
                $parameters = $route->parameters();
                $uri = $currentPath;
                foreach ($route->middleware() as $item) {
                    if ($item == 'auth') {
                        if (! auth()->check()) {
                            // If user is not authenticated, return the route name and empty URI
                            $uri = '';
                        }
                    } elseif (strpos($item, 'can:') === 0) {
                        [$permission, $model] = explode(',', substr($item, 4));
                        $modelInstance = array_key_exists($model, $parameters) ? $parameters[$model] : app($model);
                        if (! Gate::allows($permission, $modelInstance)) {
                            // If user does not have permission, return the route name and empty URI
                            $uri = '';
                        }
                    }
                }
            } catch (Exception $e) {
                $routeName = $segment;
                $uri = '';
            }
            $transKey = "ig-common::navig.$routeName";
            if (! Lang::has($transKey)) {
                $transKey = "navig.$routeName";
            }
            $parameters = $parameters['data'] ?? $parameters;
            $translation = trans_choice($transKey, $totalParts - $index - $skipFirst, $parameters);
            $segments[] = [
                'uri' => $uri,
                'translation' => $translation,
            ];
        }

        return $segments;
    }

    public static function createTitle(string $separator = ' – ', $homeRoute = 'home'): string
    {
        return implode(
            $separator,
            Arr::pluck(array_reverse(self::parseUrlPath($homeRoute)), 'translation')
        );
    }

    public static function getEmailClientLink(): string
    {
        if (config('mail.mailers.' . config('mail.default') . '.host') != 'mailpit') {
            return '';
        }
        // Generate link to Mailpit inbox
        $link = config('app.url');
        if (config('app.env') == 'local') {
            $port = parse_url($link, PHP_URL_PORT);
            $port = $port + 10000;
            $link = parse_url($link, PHP_URL_SCHEME) . '://' . parse_url($link, PHP_URL_HOST) . ":$port";
        } else {
            $link = preg_replace('/^(https?:\/\/)/', '$1mail.', config('app.url'));
        }

        return " <a href=\"$link\">" . __('ig-common::messages.inbox') . '</a>';
    }
}
