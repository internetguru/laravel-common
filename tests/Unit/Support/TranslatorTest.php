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
}
