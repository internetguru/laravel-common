<?php

namespace InternetGuru\LaravelCommon\Services;

use Illuminate\Http\Request;
use InternetGuru\LaravelCommon\Contracts\ReCaptchaInterface;

class ReCaptcha implements ReCaptchaInterface
{
    public function isEnabled(): bool
    {
        if (app()->environment('local')) {
            return false;
        }
        if (app()->environment('testing')) {
            return false;
        }
        if (config('app.demo', false)) {
            return false;
        }
        if (auth()->check()) {
            return false;
        }

        return true;
    }

    public function validate(Request $request): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $request->validate([
            'g-recaptcha-response' => 'required|recaptchav3:store,' . self::RECAPTCHA_SCORE_THRESHOLD,
        ], [
            'g-recaptcha-response.required' => __('ig-common::messages.recaptcha'),
            'g-recaptcha-response.recaptchav3' => __('ig-common::messages.recaptcha'),
        ]);
    }
}
