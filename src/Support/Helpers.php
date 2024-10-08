<?php

namespace InternetGuru\LaravelCommon\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

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
        while ($skipFirst-- > 0) {
            array_shift($urlParts);
        }
        // Add home
        array_unshift($urlParts, 'home');
        // clean empty parts
        $urlParts = array_filter($urlParts);

        $currentPath = '';
        $segments = [];
        $totalParts = count($urlParts);

        foreach ($urlParts as $index => $segment) {
            $currentPath = $segment === 'home' ? route($homeRoute) : $currentPath . '/' . $segment;
            $translationKey = 'navig.' . $segment;
            $translation = __($translationKey);

            // Check if there is a short translation and use it if not the last item
            if ($index < $totalParts - 1) {
                $shortTranslationKey = $translationKey . '.short';
                $shortTranslation = __($shortTranslationKey);
                if ($shortTranslation !== $shortTranslationKey) {
                    $translation = $shortTranslation;
                }
            }

            $segment = [
                'route' => $currentPath,
                'translation' => $translation,
                'class' => '',
            ];

            if ($translation === $translationKey) {
                $segment['class'] = 'text-danger';
            }

            $segments[] = $segment;
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
