<?php

namespace Tests\Unit\Support;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Translation\ArrayLoader;
use InternetGuru\LaravelCommon\Exceptions\TranslatorException;
use InternetGuru\LaravelCommon\Support\Translator;
use Tests\TestCase;

class TranslatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock the File facade
        File::shouldReceive('exists')->andReturn(true);
        File::shouldReceive('directories')->andReturn([
            resource_path('lang/en'),
            resource_path('lang/es'),
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        \Mockery::close();
    }

    public function test_get_returns_correct_translation()
    {
        App::shouldReceive('environment')->andReturn('production');
        App::shouldReceive('hasDebugModeEnabled')->andReturn(false);

        $loader = new ArrayLoader;
        $loader->addMessages('en', '*', [
            'greeting.hello' => 'Hello',
        ]);

        $translator = new Translator($loader, 'en');

        $this->assertEquals('Hello', $translator->get('greeting.hello'));
    }

    public function test_get_logs_missing_translation()
    {
        App::shouldReceive('environment')->andReturn('production');
        App::shouldReceive('hasDebugModeEnabled')->andReturn(false);

        Log::shouldReceive('warning')
            ->twice();

        $loader = new ArrayLoader;

        $translator = new Translator($loader, 'en');

        $translator->get('greeting.missing');
    }

    // public function test_get_logs_missing_variables()
    // {
    //     App::shouldReceive('environment')->andReturn('production');
    //     App::shouldReceive('hasDebugModeEnabled')->andReturn(false);

    //     Log::shouldReceive('warning')
    //         ->once()
    //         ->with("Missing variables for 'greeting.hello': :name");

    //     $loader = new ArrayLoader;
    //     $loader->addMessages('en', '*', [
    //         'greeting.hello' => 'Hello, :name!',
    //     ]);

    //     $translator = new Translator($loader, 'en');

    //     $translator->get('greeting.hello', ['foo' => 'bar']);
    // }

    public function test_get_check_multiple_locales_in_debug_mode()
    {
        App::shouldReceive('environment')->andReturn('production');
        App::shouldReceive('hasDebugModeEnabled')->andReturn(true);

        $this->expectException(TranslatorException::class);

        $loader = new ArrayLoader;
        $loader->addMessages('en', '*', [
            'greeting.hello' => 'Hello',
        ]);
        // es translation is missing

        $translator = new Translator($loader, 'en');
        $translator->get('greeting.hello');
    }

    public function test_get_throws_exception_for_missing_translation_in_non_production()
    {
        $this->expectException(TranslatorException::class);

        App::shouldReceive('environment')->andReturn('local');
        App::shouldReceive('hasDebugModeEnabled')->andReturn(true);

        $loader = new ArrayLoader;

        $translator = new Translator($loader, 'en');

        $translator->get('greeting.missing');
    }

    public function test_has_returns_true_for_existing_translation()
    {
        App::shouldReceive('environment')->andReturn('production');
        App::shouldReceive('hasDebugModeEnabled')->andReturn(false);

        $loader = new ArrayLoader;
        $loader->addMessages('en', '*', [
            'greeting.hello' => 'Hello',
        ]);

        $translator = new Translator($loader, 'en');

        $this->assertTrue($translator->has('greeting.hello'));
    }

    public function test_has_returns_false_for_missing_translation()
    {
        App::shouldReceive('environment')->andReturn('production');
        App::shouldReceive('hasDebugModeEnabled')->andReturn(false);

        $loader = new ArrayLoader;

        $translator = new Translator($loader, 'en');

        $this->assertFalse($translator->has('greeting.missing'));
    }

    public function test_choice_with_single_count()
    {
        App::shouldReceive('environment')->andReturn('production');
        App::shouldReceive('hasDebugModeEnabled')->andReturn(false);

        Log::shouldReceive('warning')->times(2);

        $loader = new ArrayLoader;
        $loader->addMessages('en', '*', [
            'apples' => '{0} No apples|{1} One apple|[2,*] :count apples',
        ]);

        $translator = new Translator($loader, 'en');

        $this->assertEquals('One apple', $translator->choice('apples', 1));
    }

    public function test_choice_with_multiple_count()
    {
        App::shouldReceive('environment')->andReturn('production');
        App::shouldReceive('hasDebugModeEnabled')->andReturn(false);

        Log::shouldReceive('warning')->times(2);

        $loader = new ArrayLoader;
        $loader->addMessages('en', '*', [
            'apples' => '{0} No apples|{1} One apple|[2,*] :count apples',
        ]);

        $translator = new Translator($loader, 'en');

        $this->assertEquals('5 apples', $translator->choice('apples', 5));
    }

    public function test_choice_with_countable_array()
    {
        App::shouldReceive('environment')->andReturn('production');
        App::shouldReceive('hasDebugModeEnabled')->andReturn(false);

        Log::shouldReceive('warning')->times(2);

        $loader = new ArrayLoader;
        $loader->addMessages('en', '*', [
            'items' => '{0} No items|{1} One item|[2,*] :count items',
        ]);

        $translator = new Translator($loader, 'en');

        $this->assertEquals('3 items', $translator->choice('items', [1, 2, 3]));
    }

    public function test_get_skips_validation_keys()
    {
        App::shouldReceive('environment')->andReturn('production');
        App::shouldReceive('hasDebugModeEnabled')->andReturn(false);

        // Should not log warnings for validation keys
        Log::shouldReceive('warning')->never();

        $loader = new ArrayLoader;

        $translator = new Translator($loader, 'en');

        // Should return the key itself without logging
        $result = $translator->get('validation.required');
        $this->assertEquals('validation.required', $result);
    }

    public function test_has_with_translator_exception()
    {
        App::shouldReceive('environment')->andReturn('local');
        App::shouldReceive('hasDebugModeEnabled')->andReturn(true);

        $loader = new ArrayLoader;
        $loader->addMessages('en', '*', [
            'greeting.hello' => 'Hello',
        ]);
        // es translation is missing, so it will throw exception in get()

        $translator = new Translator($loader, 'en');

        // has() should catch the exception and return false
        $this->assertFalse($translator->has('greeting.missing'));
    }
}
