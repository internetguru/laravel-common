<?php

namespace InternetGuru\LaravelCommon\Support;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Translation\Translator as BaseTranslator;
use InternetGuru\LaravelCommon\Exceptions\TranslatorException;

class Translator extends BaseTranslator
{
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        $line = parent::get($key, $replace, $locale, $fallback);

        $locales = $this->getAvailableLocales();
        foreach ($locales as $locale) {
            $tmpLine = parent::get($key, $replace, $locale, false);

            $this->checkMissingTranslation($key, $tmpLine, $locale);
            $this->checkMissingVariables($key, $tmpLine, $replace, $locale);
        }

        return $line;
    }

    public function has($key, $locale = null, $fallback = true)
    {
        try {
            return parent::has($key, $locale, $fallback);
        } catch (TranslatorException $e) {
            return false;
        }
    }

    /**
     * Rewrite of the original method to allow for logging missing translations
     */
    public function choice($key, $number, array $replace = [], $locale = null)
    {
        // Use parent::get to ignore missing translation errors at this point
        $line = $this->get(
            $key, $replace, $locale = $this->localeForChoice($key, $locale)
        );

        // If the given "number" is actually an array or countable we will simply count the
        // number of elements in an instance. This allows developers to pass an array of
        // items without having to count it on their end first which gives bad syntax.
        if (is_countable($number)) {
            $number = count($number);
        }

        if (! isset($replace['count'])) {
            $replace['count'] = $number;
        }

        return $this->makeReplacements(
            $this->getSelector()->choose($line, $number, $locale), $replace
        );
    }

    private function checkMissingTranslation($key, $line, $locale = null)
    {
        if ($line && $line !== $key) {
            return;
        }
        // do not check $key starting with validation.
        if (strpos($key, 'validation.') === 0) {
            return;
        }

        $message = $locale
            ? "Missing or empty translation for locale '{$locale}': {$key}"
            : "Missing or empty translation: {$key}";
        $this->log($message);
    }

    private function checkMissingVariables($key, $line, $replace, $locale = null)
    {
        if (! is_string($line)) {
            return;
        }
        // do not check $key starting with validation.
        if (strpos($key, 'validation.') === 0) {
            return;
        }
        preg_match_all('/(?<!:):[a-zA-Z_]\w*/', $line, $matches);
        $placeholders = $matches[0] ?? [];

        $providedVariables = array_map(fn ($k) => ':' . $k, array_keys($replace));
        // allow to use $provided varaibles multiple times
        $missingVariables = [];
        foreach ($placeholders as $placeholder) {
            $key = array_search($placeholder, $providedVariables);
            if ($key === false) {
                $missingVariables[] = $placeholder;
            }
        }

        if ($missingVariables) {
            $message = $locale
                ? "Missing variables for '{$key}' in locale '{$locale}': " . implode(', ', $missingVariables)
                : "Missing variables for '{$key}': " . implode(', ', $missingVariables);
            $this->log($message);
        }
    }

    private function log(string $message)
    {
        if (! App::hasDebugModeEnabled()) {
            Log::warning($message);
            return;
        }
        throw new TranslatorException($message);
    }

    private function getAvailableLocales(): array
    {
        $locales = [];
        $langPath = base_path('lang');

        if (File::exists($langPath)) {
            $directories = File::directories($langPath);
            foreach ($directories as $directory) {
                $locales[] = basename($directory);
            }
        }

        // exclude vendor translations
        $locales = array_filter($locales, fn ($locale) => strpos($locale, 'vendor') === false);

        return $locales;
    }
}
