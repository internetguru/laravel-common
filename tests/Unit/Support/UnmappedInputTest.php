<?php

namespace Tests\Unit\Support;

use Illuminate\Support\Facades\Log;
use InternetGuru\LaravelCommon\Exceptions\UnmappedInputException;
use InternetGuru\LaravelCommon\Support\Sanitizer;
use Tests\TestCase;

class UnmappedInputTest extends TestCase
{
    public function test_an_unmapped_string_throws_while_debugging()
    {
        config(['app.debug' => true]);

        $this->expectException(UnmappedInputException::class);
        $this->expectExceptionMessage('Input [nickname_of_pet] has no sanitization pipeline');

        (new Sanitizer)->sanitize(['nickname_of_pet' => 'x']);
    }

    public function test_the_throw_can_be_turned_off_while_still_debugging()
    {
        config(['app.debug' => true, 'ig-common.sanitize.unmapped.throw_when_debug' => false]);
        Log::spy();

        $result = (new Sanitizer)->sanitize(['nickname_of_pet' => '  a   b  ']);

        $this->assertSame('a b', $result['nickname_of_pet']);
    }

    public function test_outside_debug_the_fallback_pipeline_runs_and_the_key_is_logged()
    {
        config(['app.debug' => false]);
        Log::spy();

        $result = (new Sanitizer)->sanitize(['nickname_of_pet' => "  a  \n  b  "]);

        // The 'strict' fallback squishes, unlike the 'text' pipeline.
        $this->assertSame('a b', $result['nickname_of_pet']);

        Log::shouldHaveReceived('log')
            ->once()
            ->withArgs(fn ($level, $message) => $level === 'warning'
                && str_contains($message, 'nickname_of_pet'));
    }

    public function test_the_same_key_is_logged_once_per_request()
    {
        config(['app.debug' => false]);
        Log::spy();

        $sanitizer = new Sanitizer;
        $sanitizer->sanitize(['nickname_of_pet' => ' a ']);
        $sanitizer->sanitize(['nickname_of_pet' => ' b ']);

        Log::shouldHaveReceived('log')->once();
    }

    public function test_a_new_request_reports_the_key_again()
    {
        config(['app.debug' => false]);
        Log::spy();

        (new Sanitizer)->sanitize(['nickname_of_pet' => ' a ']);
        (new Sanitizer)->sanitize(['nickname_of_pet' => ' b ']);

        Log::shouldHaveReceived('log')->twice();
    }

    public function test_excepted_keys_and_non_strings_are_never_reported()
    {
        config(['app.debug' => true]);

        $result = (new Sanitizer)->sanitize([
            'password' => '  secret  ',
            'undeclared_count' => 7,
            'undeclared_list' => [],
        ]);

        $this->assertSame('  secret  ', $result['password']);
        $this->assertSame(7, $result['undeclared_count']);
    }

    public function test_nothing_is_reported_when_the_feature_is_disabled()
    {
        config(['app.debug' => true, 'ig-common.sanitize.enabled' => false]);

        $result = (new Sanitizer)->sanitize(['nickname_of_pet' => ' a ']);

        $this->assertSame(' a ', $result['nickname_of_pet']);
    }
}
