<?php

namespace Tests\Unit\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RequestMacrosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => false]);
    }

    private function request(array $input): Request
    {
        return Request::create('/', 'POST', $input);
    }

    public function test_the_macros_are_registered()
    {
        $this->assertTrue(Request::hasMacro('sanitizedData'));
        $this->assertTrue(Request::hasMacro('sanitize'));
    }

    public function test_validate_returns_the_sanitized_value()
    {
        $request = $this->request(['email' => '  Fóo@Example.COM  ']);

        $validated = $request->validate(['email' => 'required|email']);

        $this->assertSame('foo@example.com', $validated['email']);
    }

    public function test_validate_also_writes_the_sanitized_value_back_to_the_request()
    {
        $request = $this->request(['email' => '  Fóo@Example.COM  ']);

        $request->validate(['email' => 'required|email']);

        $this->assertSame('foo@example.com', $request->input('email'));
    }

    public function test_validate_writes_back_a_nested_value_without_flattening_it()
    {
        $request = $this->request(['contacts' => [['email' => ' A@B.COM ']]]);

        $request->validate(['contacts.*.email' => 'required|email']);

        $this->assertSame('a@b.com', $request->input('contacts.0.email'));
    }

    public function test_validate_still_fails_on_invalid_input()
    {
        $request = $this->request(['email' => 'not-an-email']);

        $this->expectException(ValidationException::class);

        $request->validate(['email' => 'required|email']);
    }

    public function test_excepted_fields_survive_validate_untouched()
    {
        $request = $this->request(['email' => ' A@B.COM ', 'password' => '  s p a c e d  ']);

        $request->validate(['email' => 'required|email']);

        $this->assertSame('  s p a c e d  ', $request->input('password'));
    }

    public function test_framework_fields_a_form_always_posts_are_left_alone()
    {
        config(['app.debug' => true]);

        // Would throw UnmappedInputException for _token otherwise, on every
        // form submission in the application.
        $request = $this->request([
            'email' => ' A@B.COM ',
            '_token' => ' Ab3 Cd4 ',
            'g-recaptcha-response' => ' TOKEN.value ',
        ]);

        $request->validate(['email' => 'required|email']);

        $this->assertSame(' Ab3 Cd4 ', $request->input('_token'));
        $this->assertSame(' TOKEN.value ', $request->input('g-recaptcha-response'));
    }

    public function test_validate_with_bag_tags_the_error_bag()
    {
        $request = $this->request(['email' => 'not-an-email']);

        try {
            $request->validateWithBag('checkout', ['email' => 'required|email']);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame('checkout', $e->errorBag);
        }
    }

    public function test_sanitized_data_returns_without_validating()
    {
        $request = $this->request(['email' => '  Fóo@Example.COM  ']);

        $data = $request->sanitizedData();

        $this->assertSame('foo@example.com', $data['email']);
        // Untouched: sanitizedData() reports, it does not write back.
        $this->assertSame('  Fóo@Example.COM  ', $request->input('email'));
    }

    public function test_sanitize_merges_the_values_into_the_request()
    {
        $request = $this->request(['email' => '  Fóo@Example.COM  ']);

        $request->sanitize();

        $this->assertSame('foo@example.com', $request->input('email'));
    }

    public function test_nothing_is_changed_when_the_feature_is_disabled()
    {
        config(['ig-common.sanitize.enabled' => false]);

        $request = $this->request(['email' => '  Fóo@Example.COM  ']);
        $request->validate(['email' => 'required']);

        $this->assertSame('  Fóo@Example.COM  ', $request->input('email'));
    }
}
