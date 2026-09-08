<?php

use Carbon\Carbon;
use Carbon\Translator;
use Illuminate\Foundation\Precognition;
use Illuminate\Http\Request;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InternetGuru\LaravelCommon\Support\Sanitizer;

function initRequestMacros()
{
    // The sanitized input, without validating anything. For a controller that
    // reads $request->input() rather than the array validate() returns.
    Request::macro('sanitizedData', function (array $rules = [], array $map = []): array {
        return app(Sanitizer::class)->sanitize($this->all(), $rules, $map);
    });

    // Merge the sanitized values back into the request, so everything reading
    // the request afterwards sees them.
    Request::macro('sanitize', function (array $rules = [], array $map = []) {
        $sanitizer = app(Sanitizer::class);
        $data = $this->all();
        $changes = $sanitizer->changes($data, $rules, $map);

        if ($changes === []) {
            return $this;
        }

        $sanitized = $sanitizer->sanitize($data, $rules, $map);

        // A change is keyed by dotted path, which merge() would take literally.
        // Merging the whole top-level branch of each change keeps the nesting
        // and still leaves untouched branches alone.
        $branches = array_unique(array_map(
            static fn (array $change): string => $change['path'][0],
            $changes
        ));

        return $this->merge(array_intersect_key($sanitized, array_flip($branches)));
    });

    // Sanitize before validating, so both the validated array and the request
    // itself carry normalized values.
    //
    // Overrides the macro Laravel registers in
    // Illuminate\Foundation\Providers\FoundationServiceProvider::registerRequestValidation();
    // the body below is that method's, with the sanitize() call added. There is
    // no public accessor for an existing macro, so it is copied rather than
    // wrapped - keep it in sync when upgrading the framework.
    Request::macro('validate', function (array $rules, ...$params) {
        $this->sanitize($rules);

        return tap(validator($this->all(), $rules, ...$params), function ($validator) {
            if ($this->isPrecognitive()) {
                $validator->after(Precognition::afterValidationHook($this))
                    ->setRules(
                        $this->filterPrecognitiveRules($validator->getRulesWithoutPlaceholders())
                    );
            }
        })->validate();
    });

    Request::macro('validateWithBag', function (string $errorBag, array $rules, ...$params) {
        try {
            return $this->validate($rules, ...$params);
        } catch (ValidationException $e) {
            $e->errorBag = $errorBag;

            throw $e;
        }
    });
}

function initStringMacros()
{
    Str::macro('ref', function (int $length = 6) {
        throw_if($length < 2, new InvalidArgumentException('Length must be at least 2'));

        // exclude similar looking characters: i, l, o, 0, 1, u
        $letters = 'abcdefghjkmnpqrstvwxyz';
        $digits = '23456789';
        $pool = $letters . $digits;

        $ref = $letters[random_int(0, strlen($letters) - 1)];

        $digitPosition = random_int(1, $length - 1);

        for ($i = 1; $i < $length; $i++) {
            if ($i === $digitPosition) {
                $ref .= $digits[random_int(0, strlen($digits) - 1)];
            } else {
                $ref .= $pool[random_int(0, strlen($pool) - 1)];
            }
        }

        return $ref;
    });
}

function initNumberMacros()
{
    // Return number formatted to currency with input
    Number::macro('formatCurrencyToInput', function (mixed $number, ?string $in = null, int $precision = 0, string $inputTemplate = '%s'): string {
        $formattedNumber = Number::currencyForHumans($number, $in ?? Number::$currency, $precision);
        $formattedNumber = preg_replace('/\xc2\xa0|[, .]/', '', $formattedNumber);

        // return $input Kč
        // return CZK $input
        $inputWithNumber = sprintf($inputTemplate, $number);

        return preg_replace('/\d+/', $inputWithNumber, $formattedNumber);
    });

    // Return number formatted to currency
    // Or return currency symbol if no number is provided
    Number::macro('currencyForHumans', function (mixed $number = null, ?string $in = null, int $precision = 0): string {
        $formatter = new NumberFormatter(app()->getLocale(), NumberFormatter::CURRENCY);

        if (is_null($number)) {
            $formatter->setTextAttribute(NumberFormatter::CURRENCY_CODE, $in ?? Number::$currency);

            return $formatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
        }

        $number = (float) $number;
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $precision);

        return $formatter->formatCurrency($number, $in ?? Number::$currency);
    });
}

function initCarbonMacros()
{
    // Override English "X from now" to "in X" to match other locales
    Translator::get('en')->setTranslations([
        'from_now' => 'in :time',
    ]);

    Carbon::macro('dateForHumans', fn () => $this->isoFormat('L'));

    Carbon::macro('dateTimeForHumans', fn () => $this->dateForHumans() . ' ' . $this->timeForHumans());

    Carbon::macro('toDisplayTimezone', function () {
        $timezone = session('display_timezone', config('app.timezone'));

        return $this->setTimezone($timezone);
    });

    Carbon::macro('myDiffForHumans', function (): string {
        $diff = $this->diffForHumans();
        $seconds = $this->diffInSeconds(now(), true);

        if ($seconds < 60) {
            return __('ig-common::layouts.just_now');
        }

        // show "in 1 year" for date between 11 months and 15 days and 12 months and 15 days
        $fullDiff = $this->diff(now(), true);
        if (($fullDiff->m == 11 && $fullDiff->d >= 15) || ($fullDiff->m == 12 && $fullDiff->d <= 15)) {
            return __('ig-common::layouts.in_year');
        }

        return $diff;
    });

    Carbon::macro('timeForHumans', function () {
        // drop a zero minute part before the AM/PM marker ("1:00 PM" -> "1 PM") and a leading zero hour
        return preg_replace(['/:00(?=\D)/u', '/^0/'], '', $this->isoFormat('LT'));
    });

    Carbon::macro('randomWorkTime', function (int $from = 9, int $to = 17) {
        return $this->setTime(rand($from, $to - 1), rand(0, 59), rand(0, 59));
    });
}
