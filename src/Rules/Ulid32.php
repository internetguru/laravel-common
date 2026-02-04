<?php

namespace InternetGuru\LaravelCommon\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Ulid32 implements ValidationRule
{
    public static function isValid(mixed $value): bool
    {
        // Strict Crockford Base32: 26 chars, no I/L/O/U
        return (bool) preg_match('/^[0-9a-hjkmnp-tv-z]{26}$/', $value);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isValid($value)) {
            $fail(__('ig-common::messages.validation.ulid32'));
        }
    }
}
