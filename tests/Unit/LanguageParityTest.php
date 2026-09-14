<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * A locale that silently lacks a key ships as an empty string in production,
 * so every shipped locale must define exactly the keys English defines.
 */
class LanguageParityTest extends TestCase
{
    public function test_every_locale_defines_the_same_keys_as_english()
    {
        $reference = $this->keysByFile('en');
        $this->assertNotEmpty($reference);

        foreach ($this->locales() as $locale) {
            if ($locale === 'en') {
                continue;
            }

            $keys = $this->keysByFile($locale);

            $this->assertSame(
                array_keys($reference),
                array_keys($keys),
                "Locale [{$locale}] does not carry the same translation files as [en]."
            );

            foreach ($reference as $file => $referenceKeys) {
                $this->assertSame(
                    [],
                    array_values(array_diff($referenceKeys, $keys[$file])),
                    "Locale [{$locale}] is missing keys in [{$file}.php]."
                );
                $this->assertSame(
                    [],
                    array_values(array_diff($keys[$file], $referenceKeys)),
                    "Locale [{$locale}] defines keys absent from [en] in [{$file}.php]."
                );
            }
        }
    }

    /**
     * @return array<int, string>
     */
    protected function locales(): array
    {
        return array_map('basename', glob($this->langPath() . '/*', GLOB_ONLYDIR) ?: []);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function keysByFile(string $locale): array
    {
        $keys = [];

        foreach (glob($this->langPath() . "/{$locale}/*.php") ?: [] as $path) {
            // The i18n demo pages need a key each locale deliberately lacks,
            // so `missing-*` is absent from every locale but the one it names.
            $keys[basename($path, '.php')] = array_values(array_filter(
                array_keys(require $path),
                fn (string $key): bool => ! str_starts_with($key, 'missing-'),
            ));
        }

        ksort($keys);

        return $keys;
    }

    protected function langPath(): string
    {
        return dirname(__DIR__, 2) . '/lang';
    }
}
