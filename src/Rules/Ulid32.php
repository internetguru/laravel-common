<?php

namespace InternetGuru\LaravelCommon\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Ulid32 implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Strict Crockford Base32: 26 chars, uppercase, no I/L/O/U
        if (! preg_match('/^[0-9a-hjkmnp-tv-z]{26}$/', $value)) {
            $fail(__('ig-common::messages.validation.ulid32'));
        }
    }
}
