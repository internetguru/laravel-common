<?php

namespace InternetGuru\LaravelCommon\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Handler extends ExceptionHandler
{
    public function register()
    {
        $this->renderable(function (Throwable $e, $request) {
            if ($e instanceof HttpExceptionInterface) {
                $statusCode = $e->getStatusCode();

                if (! in_array($statusCode, [401, 402, 403, 404, 419, 429, 500, 503])) {
                    return response()->view('ig-common::layouts.base', [
                        'exception' => $e,
                        'view' => 'layouts.empty',
                        'title' => "$statusCode " . __('ig-common::errors.unknown'),
                        'description' => __('ig-common::errors.unknown_message'),
                    ], $statusCode);
                }

                return response()->view('ig-common::layouts.base', [
                    'exception' => $e,
                    'view' => 'layouts.empty',
                    'title' => "$statusCode " . __('ig-common::errors.' . $statusCode),
                    'description' => __('ig-common::errors.' . $statusCode . '_message'),
                ], $statusCode);
            }

            return null;
        });
    }
}
