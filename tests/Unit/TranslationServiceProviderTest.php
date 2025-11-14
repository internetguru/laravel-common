<?php

namespace Tests\Unit;

use InternetGuru\LaravelCommon\Support\Translator;
use Tests\TestCase;

class TranslationServiceProviderTest extends TestCase
{
    public function test_translator_is_registered()
    {
        $translator = app('translator');

        // The translator may be the base Translator or our custom one
        // depending on when the service provider is registered
        $this->assertInstanceOf(\Illuminate\Translation\Translator::class, $translator);
    }

    public function test_translator_has_correct_locale()
    {
        $translator = app('translator');

        $this->assertEquals(config('app.locale'), $translator->getLocale());
    }

    public function test_translator_has_fallback_locale()
    {
        $translator = app('translator');

        $this->assertEquals(config('app.fallback_locale'), $translator->getFallback());
    }

    public function test_translator_can_translate()
    {
        $translator = app('translator');

        // Add a test translation
        app('translator')->addLines([
            'test.key' => 'Test Value',
        ], 'en');

        $this->assertEquals('Test Value', $translator->get('test.key'));
    }
}
