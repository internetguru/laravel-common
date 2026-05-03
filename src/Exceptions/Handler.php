<?php

namespace InternetGuru\LaravelCommon\Exceptions;

use GuzzleHttp\Exception\ConnectException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        DbReadOnlyException::class,
    ];

    public function register()
    {
        $this->renderable(function (Throwable $e, $request) {

            // handle AuthenticationException
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return;
            }

            // handle ValidationException
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return;
            }

            if ($e instanceof DbReadOnlyException) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => $e->getMessage()], 503);
                }

                return $this->back()->withErrors($e->getMessage());
            }

            // do not process any exception in testing mode
            if (app()->environment('testing')) {
                return;
            }

            $statusCode = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            // Explicitly render Laravel's debug page when in debug mode
            if (app()->hasDebugModeEnabled()) {
                dd($e);
            }

            // connection error from remote server, e.g. dns not resolved or timeout
            if ($e instanceof ConnectException) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => __('ig-common::errors.connection_error')], 500);
                }

                return $this->back()->withErrors(__('ig-common::errors.connection_error'));
            }

            // throttle handling
            if ($statusCode == 429) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => __('ig-common::errors.ratelimit')], 429);
                }

                return $this->back()->withErrors([__('ig-common::errors.ratelimit')]);
            }

            // expired session
            if ($statusCode == 419) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => __('ig-common::errors.session_expired')], 419);
                }

                return $this->back()->withErrors(__('ig-common::errors.session_expired'));
            }

            // global error
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], $statusCode);
            }

            if (! in_array($statusCode, [401, 402, 403, 404, 419, 429, 500, 503])) {
                return response()->view(
                    'ig-common::layouts.base',
                    [
                        'exception' => $e,
                        'view' => 'layouts.empty',
                        'title' => "$statusCode " . __('ig-common::errors.unknown'),
                        'description' => __('ig-common::errors.unknown_message'),
                    ],
                    $statusCode,
                );
            }

            return response()->view(
                'ig-common::layouts.base',
                [
                    'exception' => $e,
                    'view' => 'layouts.empty',
                    'title' => "$statusCode " . __('ig-common::errors.' . $statusCode),
                    'description' => __('ig-common::errors.' . $statusCode . '_message'),
                ],
                $statusCode,
            );
        });
    }

    public function shouldReport(Throwable $e): bool
    {
        // Ignore Livewire locked property update attempts (e.g. from bots)
        if (get_class($e) === 'Livewire\\Features\\SupportLockedProperties\\CannotUpdateLockedPropertyException') {
            return false;
        }

        // Ignore malformed Livewire upload data errors
        if ($e instanceof \TypeError && str_contains($e->getMessage(), 'method_exists(): Argument #1 ($object_or_class) must be of type object|string, int given')) {
            return false;
        }

        // Ignore view errors triggered by malformed Livewire upload data
        if (get_class($e) === 'Spatie\\LaravelIgnition\\Exceptions\\ViewException' && str_contains($e->getMessage(), 'Trying to access array offset on int')) {
            return false;
        }

        return parent::shouldReport($e);
    }

    private function back()
    {
        return session('prevPage')
            ? redirect(session('prevPage'))->withInput()
            : redirect()->back()->withInput();
    }
}
