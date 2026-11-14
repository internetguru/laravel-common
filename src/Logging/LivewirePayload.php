<?php

namespace InternetGuru\LaravelCommon\Logging;

use Illuminate\Http\Request;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;

/**
 * Reads a Livewire update request for logging: tells refreshes (polls,
 * refreshes on focus) from real work.
 */
class LivewirePayload
{
    public static function isUpdate(Request $request): bool
    {
        return $request->path() === ltrim(EndpointResolver::updatePath(), '/');
    }

    /**
     * True when every component only asks to be refreshed, without updates or other calls.
     */
    public static function isRefreshOnly(Request $request): bool
    {
        $components = $request->input('components');
        if (! $request->hasHeader('X-Livewire') || ! is_array($components) || $components === []) {
            return false;
        }

        foreach ($components as $component) {
            $calls = is_array($component) ? ($component['calls'] ?? null) : null;
            if (! empty($component['updates']) || ! is_array($calls) || $calls === []) {
                return false;
            }
            if (self::withoutRefreshes($calls) !== []) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int|string, mixed>  $calls
     * @return list<mixed>
     */
    private static function withoutRefreshes(array $calls): array
    {
        return array_values(array_filter(
            $calls,
            fn ($call) => ! is_array($call) || ($call['method'] ?? null) !== '$refresh'
        ));
    }
}
