<?php

namespace InternetGuru\LaravelCommon\Exceptions;

use GuzzleHttp\Exception\ConnectException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Client\ConnectionException as HttpClientConnectionException;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
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

            // a Livewire component request failing should render the same styled
            // error page as a normal request, not a JSON payload or a redirect -
            // the frontend swaps it in in place of Livewire's default error overlay
            $isLivewireRequest = $request->hasHeader('X-Livewire');

            if ($e instanceof DbReadOnlyException) {
                if ($isLivewireRequest) {
                    return $this->flashErrorAndReload($e->getMessage(), 503);
                }

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
            if ($e instanceof ConnectException || $e instanceof HttpClientConnectionException) {
                if ($isLivewireRequest) {
                    return $this->errorPage($e, 500, __('ig-common::errors.connection_error'));
                }

                if ($request->expectsJson()) {
                    return response()->json(['message' => __('ig-common::errors.connection_error')], 500);
                }

                return $this->back()->withErrors(__('ig-common::errors.connection_error'));
            }

            // throttle handling
            if ($statusCode == 429) {
                if ($isLivewireRequest) {
                    return $this->flashErrorAndReload(__('ig-common::errors.ratelimit'), 429);
                }

                if ($request->expectsJson()) {
                    return response()->json(['message' => __('ig-common::errors.ratelimit')], 429);
                }

                return $this->back()->withErrors([__('ig-common::errors.ratelimit')]);
            }

            // expired session
            if ($statusCode == 419) {
                if ($isLivewireRequest) {
                    return $this->flashErrorAndReload(__('ig-common::errors.session_expired'), 419);
                }

                if ($request->expectsJson()) {
                    return response()->json(['message' => __('ig-common::errors.session_expired')], 419);
                }

                return $this->back()->withErrors(__('ig-common::errors.session_expired'));
            }

            // global error
            if ($request->expectsJson() && ! $isLivewireRequest) {
                return response()->json(['message' => $e->getMessage()], $statusCode);
            }

            return $this->errorPage($e, $statusCode);
        });
    }

    /**
     * Flash the error for a transient, retryable failure on a Livewire request
     * and return a bare response: the frontend reloads the page, which re-runs
     * the middleware stack (fresh csrf token, auth redirect when needed) and
     * shows the flashed message via the messages component.
     */
    private function flashErrorAndReload(string $message, int $statusCode)
    {
        session()->flash('errors', (new ViewErrorBag)->put('default', new MessageBag([$message])));

        return response()->json(['message' => $message], $statusCode);
    }

    /**
     * Render the shared styled error page for a given exception/status code.
     */
    private function errorPage(Throwable $e, int $statusCode, ?string $descriptionOverride = null)
    {
        if (! in_array($statusCode, [401, 402, 403, 404, 419, 429, 500, 503])) {
            return response()->view(
                'ig-common::layouts.base',
                [
                    'exception' => $e,
                    'view' => 'layouts.empty',
                    'title' => "$statusCode " . __('ig-common::errors.unknown'),
                    'description' => $descriptionOverride ?? __('ig-common::errors.unknown_message'),
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
                'description' => $descriptionOverride ?? __('ig-common::errors.' . $statusCode . '_message'),
                'refresh' => $statusCode === 503 ? 30 : null,
            ],
            $statusCode,
        );
    }

    protected function shouldntReport(Throwable $e): bool
    {
        if ($this->isSuppressedLivewireBotException($e)) {
            return true;
        }

        return parent::shouldntReport($e);
    }

    private function isSuppressedLivewireBotException(Throwable $e): bool
    {
        $class = \get_class($e);

        // Bots attempting to tamper with locked Livewire properties
        if ($class === 'Livewire\\Features\\SupportLockedProperties\\CannotUpdateLockedPropertyException') {
            return true;
        }

        // Bots sending file upload requests to components without WithFileUploads trait
        if ($class === 'Livewire\\Features\\SupportFileUploads\\MissingFileUploadsTraitException') {
            return true;
        }

        if ($e instanceof \TypeError) {
            // Bots sending malformed Livewire upload data (non-object where object expected)
            if (str_contains($e->getMessage(), 'method_exists(): Argument #1 ($object_or_class) must be of type object|string, int given')) {
                return true;
            }

            // Bots injecting array payloads into typed Livewire component properties
            if (str_contains($e->getMessage(), 'Cannot assign array to property')) {
                return true;
            }
        }

        // View errors triggered by malformed Livewire upload data
        if ($class === 'Spatie\\LaravelIgnition\\Exceptions\\ViewException' && str_contains($e->getMessage(), 'Trying to access array offset on int')) {
            return true;
        }

        // Bots sending malformed Livewire upload data that can't be re-serialized for the session
        if (str_contains($e->getMessage(), "Serialization of 'Illuminate\\Http\\UploadedFile' is not allowed")) {
            return true;
        }

        // Bots requesting non-existent Livewire components
        if ($class === 'Livewire\\Exceptions\\ComponentNotFoundException') {
            return true;
        }

        // Bots tampering with Livewire component payloads
        if ($class === 'Livewire\\Mechanisms\\HandleComponents\\CorruptComponentPayloadException') {
            return true;
        }

        // Also check the cause in case Livewire wraps the exception
        if ($e->getPrevious() !== null) {
            return $this->isSuppressedLivewireBotException($e->getPrevious());
        }

        return false;
    }

    private function back()
    {
        return session('prevPage')
            ? redirect(session('prevPage'))->withInput()
            : redirect()->back()->withInput();
    }
}
