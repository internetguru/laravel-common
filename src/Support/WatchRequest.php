<?php

namespace InternetGuru\LaravelCommon\Support;

use Illuminate\Http\Request;

/**
 * A Livewire request that only checks whether watched data changed. Its failure is
 * handled by the browser's watch clock, never by flashing an error for the next page.
 */
class WatchRequest
{
    public const METHODS = ['watchRefresh', 'watchCheck'];

    public static function is(Request $request): bool
    {
        $components = $request->input('components');
        if (! is_array($components) || $components === []) {
            return false;
        }

        $methods = [];
        foreach ($components as $component) {
            foreach ((array) ($component['calls'] ?? []) as $call) {
                $methods[] = is_array($call) ? ($call['method'] ?? null) : null;
            }
        }
        $methods = array_values(array_diff($methods, ['$commit']));

        return $methods !== [] && array_diff($methods, self::METHODS) === [];
    }
}
