<?php

use Carbon\Carbon;
use Illuminate\Support\Number;

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

    Carbon::macro('randomWorkTime', function (int $from = 9, int $to = 17) {
        return $this->setTime(rand($from, $to - 1), rand(0, 59), rand(0, 59));
    });
}
