<?php

namespace InternetGuru\LaravelCommon\Exceptions;

use GuzzleHttp\Exception\ConnectException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Client\ConnectionException as HttpClientConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Livewire\Exceptions\ComponentNotFoundException;
use Livewire\Exceptions\LivewireReleaseTokenMismatchException;
use Livewire\Exceptions\MaxNestingDepthExceededException;
use Livewire\Exceptions\MethodNotFoundException;
use Livewire\Exceptions\PayloadTooLargeException;
use Livewire\Exceptions\PublicPropertyNotFoundException;
use Livewire\Exceptions\TooManyCallsException;
use Livewire\Exceptions\TooManyComponentsException;
use Livewire\Features\SupportComputed\CannotCallComputedDirectlyException;
use Livewire\Features\SupportFileUploads\MissingFileUploadsTraitException;
use Livewire\Features\SupportLifecycleHooks\DirectlyCallingLifecycleHooksNotAllowedException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportReactiveProps\CannotMutateReactivePropException;
use Livewire\Mechanisms\HandleComponents\CorruptComponentPayloadException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Livewire's own vocabulary for "the client sent something structurally
     * invalid". None of these can be produced by a browser driving the UI, so
     * on a live site they are scanner traffic rather than application bugs.
     *
     * Livewire's developer-facing exceptions (missing validation rules, missing
     * layout, multiple root elements) are deliberately absent: those are real
     * bugs and must keep reporting.
     *
     * @var array<int, class-string>
     */
    private const MALFORMED_PAYLOAD_EXCEPTIONS = [
        BotPayloadException::class,
        CannotCallComputedDirectlyException::class,
        CannotMutateReactivePropException::class,
        CannotUpdateLockedPropertyException::class,
        ComponentNotFoundException::class,
        CorruptComponentPayloadException::class,
        DirectlyCallingLifecycleHooksNotAllowedException::class,
        LivewireReleaseTokenMismatchException::class,
        MaxNestingDepthExceededException::class,
        MethodNotFoundException::class,
        MissingFileUploadsTraitException::class,
        PayloadTooLargeException::class,
        PublicPropertyNotFoundException::class,
        TooManyCallsException::class,
        TooManyComponentsException::class,
    ];

    protected $dontReport = [
        DbReadOnlyException::class,
    ];

    public function register()
    {
        $this->renderable(function (Throwable $e, $request) {

            // handle AuthenticationException
            if ($e instanceof AuthenticationException) {
                return;
            }

            // handle ValidationException
            if ($e instanceof ValidationException) {
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

    /**
     * Keep malformed Livewire payloads out of the error channel.
     *
     * They are logged at debug rather than dropped, so a false positive - or a
     * genuine Livewire bug that happens to look like one - stays diagnosable
     * without paging anyone.
     */
    public function report(Throwable $e): void
    {
        if ($this->isMalformedLivewirePayload($e)) {
            Log::debug('Discarded malformed Livewire payload: ' . $e->getMessage(), [
                'exception' => $e::class,
                'origin' => $e->getFile() . ':' . $e->getLine(),
                'ip' => request()->ip(),
            ]);

            return;
        }

        parent::report($e);
    }

    /**
     * Classify an exception by where it came from rather than by its message.
     *
     * Matching messages never converges: one injected value reaches a different
     * downstream function on every request, so each fuzzing round mints a new
     * string. These two rules are closed sets instead.
     */
    private function isMalformedLivewirePayload(Throwable $e): bool
    {
        foreach (self::MALFORMED_PAYLOAD_EXCEPTIONS as $class) {
            if ($e instanceof $class) {
                return true;
            }
        }

        // Livewire processes the client payload - expanding form-object updates,
        // resolving synthesizers, hydrating properties - before any application
        // code runs. A raw PHP Error raised in there is the payload being
        // malformed, not a bug in this application: our own bugs surface in
        // app/ and resources/views/, which RejectMalformedPayload shields by
        // refusing the bad value at the point of entry.
        if ($e instanceof \Error && str_contains($e->getFile(), '/livewire/livewire/src/')) {
            return true;
        }

        // Livewire and Ignition both wrap the original cause.
        return $e->getPrevious() !== null && $this->isMalformedLivewirePayload($e->getPrevious());
    }

    private function back()
    {
        return session('prevPage')
            ? redirect(session('prevPage'))->withInput()
            : redirect()->back()->withInput();
    }
}
