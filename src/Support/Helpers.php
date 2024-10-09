<?php

namespace InternetGuru\LaravelCommon\Support;

use Illuminate\Support\Arr;
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

        try {
            Route::getRoutes()->match(request())->getName();
        } catch (NotFoundHttpException $e) {
            // target route does not exist
            $urlParts = [''];
        }

        $currentPath = '';
        $segments = [];
        $totalParts = count($urlParts);

        foreach ($urlParts as $index => $segment) {
            $currentPath = $currentPath == '/' ? $currentPath . $segment : $currentPath . '/' . $segment;
            try {
                $routeName = Route::getRoutes()->match(request()->create($currentPath))->getName();
                $uri = $currentPath;
            } catch (NotFoundHttpException $e) {
                $routeName = $segment;
                $uri = '';
            }
            $translation = trans_choice("navig.$routeName", $totalParts - $index - $skipFirst);
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
}
