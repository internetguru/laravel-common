<?php

namespace InternetGuru\LaravelCommon\Support;

use Illuminate\Support\Facades\Storage;

class Helpers
{
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
            $commit = trim(Storage::disk('root')->get('.git/'.substr($branch, 5)));
        }

        $info['branch'] = basename($branch);
        $info['commit'] = substr($commit, 0, 7);

        return $info;
    }

    public static function getAppInfo(): string
    {
        return implode(' ', self::getAppInfoArray());
    }
}
