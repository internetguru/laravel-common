<?php

use Carbon\Carbon;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

function initStringMacros()
{
    Str::macro('ref', function (int $length = 6) {
        throw_if($length < 1, new InvalidArgumentException('Length must be at least 1'));

        $letters = 'abcdefghjkmnpqrstuvwxyz';
        $pool = $letters . '23456789';

        $ref = $letters[random_int(0, strlen($letters) - 1)];

        for ($i = 1; $i < $length; $i++) {
            $ref .= $pool[random_int(0, strlen($pool) - 1)];
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
    Carbon::macro('dateForHumans', fn () => $this->isoFormat('L'));

    Carbon::macro('dateTimeForHumans', fn () => $this->isoFormat('L LT'));

    Carbon::macro('myDiffForHumans', function (): string {
        $diff = $this->diffForHumans();
        $seconds = $this->diffInSeconds(now(), true);

        if ($seconds < 60) {
            return __('ig-common::layouts.just_now');
        }

        // show 1 year for date between 11 months and 15 days and 12 months and 15 days
        $fullDiff = $this->diff(now(), true);
        if (($fullDiff->m == 11 && $fullDiff->d >= 15) || ($fullDiff->m == 12 && $fullDiff->d <= 15)) {
            return '1 ' . __('ig-common::layouts.year');
        }

        return $diff;
    });

    Carbon::macro('timeForHumans', function () {
        return preg_replace(['/:00 /', '/^0/'], '', $this->isoFormat('LT'));
    });

    Carbon::macro('randomWorkTime', function (int $from = 9, int $to = 17) {
        return $this->setTime(rand($from, $to - 1), rand(0, 59), rand(0, 59));
    });
}
