<?php

namespace InternetGuru\LaravelCommon\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

class PreventDuplicateSubmissions
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->isMethod('post') || Livewire::isLivewireRequest()) {
            return $next($request);
        }

        $requestKey = $this->requestKey($request);
        $firstSubmittedAt = cache()->get($requestKey);

        if (! is_null($firstSubmittedAt)) {
            // A repeat this soon is the browser sending the form twice, not the person
            // asking for it twice. It goes back the way it came without a word: an error
            // on a double click blames someone for something they did not do.
            if ($firstSubmittedAt + $this->seconds('double_click') >= now()->getTimestamp()) {
                return back();
            }

            return back()->withInput()->withErrors([
                'error' => __('ig-common::messages.duplicate_submission'),
            ]);
        }

        // Someone signed in repeats identical actions as a matter of course - the same
        // amount taken from the same till twice over - so their window is the shorter one.
        $window = $request->user() ? $this->seconds('authenticated') : $this->seconds('guest');

        cache()->put($requestKey, now()->getTimestamp(), now()->addSeconds($window));

        return $next($request);
    }

    /**
     * One submission of one form: who, where, and what they sent.
     */
    private function requestKey(Request $request): string
    {
        // The recaptcha token differs between two otherwise identical submissions.
        $input = $request->except('g-recaptcha-response');

        // UploadedFile is not serializable, so replace it with its metadata
        array_walk_recursive($input, function (&$value) {
            if ($value instanceof UploadedFile) {
                $value = $value->getClientOriginalName() . '|' . $value->getSize();
            }
        });

        return sha1($request->ip() . '|' . $request->path() . '|' . serialize($input));
    }

    private function seconds(string $key): int
    {
        return (int) config("ig-common.duplicate_submissions.$key", match ($key) {
            'double_click' => 3,
            'authenticated' => 10,
            default => 60,
        });
    }
}
