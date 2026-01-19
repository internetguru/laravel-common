<?php

namespace InternetGuru\LaravelCommon\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifyCsrfToken;

class VerifyCsrfToken extends BaseVerifyCsrfToken
{
    protected $except = [
        'livewire/*',
    ];

    protected function inExceptArray($request)
    {
        // Exclude private and reserved IP addresses from CSRF verification ~ e.g. server
        if (! filter_var($request->ip(), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }

        return parent::inExceptArray($request);
    }
}
