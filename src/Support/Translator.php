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

        if (strpos($key, '.') === false) {
            return $line;
        }

        $this->checkMissingTranslation($key, $line);
        $this->checkMissingVariables($key, $line, $replace);

        if (App::hasDebugModeEnabled()) {
            $locales = $this->getAvailableLocales();

            foreach ($locales as $locale) {
                $tmpLine = parent::get($key, $replace, $locale, $fallback);

                $this->checkMissingTranslation($key, $tmpLine, $locale);
                $this->checkMissingVariables($key, $tmpLine, $replace, $locale);
            }
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

    private function checkMissingTranslation($key, $line, $locale = null)
    {
        if ($line !== $key) {
            return;
        }
        $message = $locale
            ? "Missing translation for locale '{$locale}': {$key}"
            : "Missing translation: {$key}";
        $this->log($message);
    }

    private function checkMissingVariables($key, $line, $replace, $locale = null)
    {
        if (! is_string($line)) {
            return;
        }
        preg_match_all('/(?<!:):[a-zA-Z_]\w*/', $line, $matches);
        $placeholders = $matches[0] ?? [];

        $providedVariables = array_map(fn ($k) => ':' . $k, array_keys($replace));
        $missingVariables = array_diff($placeholders, $providedVariables);

        if ($missingVariables) {
            $message = $locale
                ? "Missing variables for '{$key}' in locale '{$locale}': " . implode(', ', $missingVariables)
                : "Missing variables for '{$key}': " . implode(', ', $missingVariables);
            $this->log($message);
        }
    }

    private function log(string $message)
    {
        if (App::environment() == 'production') {
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

        return $locales;
    }
}
