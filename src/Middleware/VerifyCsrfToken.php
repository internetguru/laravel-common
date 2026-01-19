<?php

namespace InternetGuru\LaravelCommon\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifyCsrfToken;
use InternetGuru\LaravelCommon\Support\Helpers;

class VerifyCsrfToken extends BaseVerifyCsrfToken
{
    protected $except = [
        'livewire/*',
    ];

    protected function inExceptArray($request)
    {
        if (Helpers::verifyRequestSignature($request)) {
            return true;
        }

        return parent::inExceptArray($request);
    }
}
