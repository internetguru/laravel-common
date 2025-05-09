<?php

namespace InternetGuru\LaravelCommon\Contracts;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

interface ReCaptchaInterface
{
    /**
     * ReCAPTCHA score threshold
     */
    public const RECAPTCHA_SCORE_THRESHOLD = 0.7;

    /**
     * Check if ReCAPTCHA is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Validate ReCAPTCHA token
     *
     * @param Request $request
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(Request $request): void;
}
