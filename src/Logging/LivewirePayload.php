<?php

namespace InternetGuru\LaravelCommon\Logging;

use Illuminate\Http\Request;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;

/**
 * Reads a Livewire update request for logging: tells refreshes (polls,
 * refreshes on focus) from real work and strips the bulky snapshots.
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
     * Replace each snapshot with the component's name and drop refresh calls;
     * components left with nothing to do are removed.
     *
     * @param  array<int|string, mixed>  $components
     * @return array<int|string, array<string, mixed>>
     */
    public static function forLog(array $components): array
    {
        $logged = [];
        foreach ($components as $key => $component) {
            if (! is_array($component)) {
                continue;
            }
            $name = self::componentName($component['snapshot'] ?? null);
            unset($component['snapshot']);
            if (is_array($component['calls'] ?? null)) {
                $component['calls'] = self::withoutRefreshes($component['calls']);
            }
            if (empty($component['updates']) && empty($component['calls'])) {
                continue;
            }
            $logged[$key] = ($name === null ? [] : ['name' => $name]) + $component;
        }

        return $logged;
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

    private static function componentName(mixed $snapshot): ?string
    {
        if (is_string($snapshot)) {
            $snapshot = json_decode($snapshot, true);
        }
        $name = is_array($snapshot) ? ($snapshot['memo']['name'] ?? null) : null;

        return is_string($name) ? $name : null;
    }
}
