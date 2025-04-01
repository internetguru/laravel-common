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

                // Check if our package's error view exists
                if (view()->exists("ig-common::errors.{$statusCode}")) {
                    return response()->view("ig-common::errors.{$statusCode}", [
                        'exception' => $e
                    ], $statusCode);
                }
            }

            return null;
        });
    }
}
