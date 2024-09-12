<?php

namespace InternetGuru\LaravelCommon\Casts;

use Carbon\CarbonInterval;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class CarbonIntervalCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @see https://carbon.nesbot.com/docs/#doc-method-CarbonInterval-fromString
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (! $value) {
            return null;
        }

        return CarbonInterval::fromString($value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @see https://carbon.nesbot.com/docs/#doc-method-CarbonInterval-forHumans
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (! $value) {
            return null;
        }

        return $value->forHumans();
    }
}
