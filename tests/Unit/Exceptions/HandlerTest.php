<?php

namespace Tests\Unit\Exceptions;

use Carbon\Carbon;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;
use InternetGuru\LaravelCommon\Exceptions\BotPayloadException;
use InternetGuru\LaravelCommon\Exceptions\DbReadOnlyException;
use InternetGuru\LaravelCommon\Exceptions\Handler;
use Livewire\Exceptions\ComponentNotFoundException;
use Livewire\Exceptions\MissingRulesException;
use Livewire\Features\SupportFileUploads\MissingFileUploadsTraitException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleComponents\CorruptComponentPayloadException;
use Livewire\Mechanisms\HandleComponents\Synthesizers\CarbonSynth;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\LaravelIgnition\Exceptions\ViewException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class HandlerTest extends TestCase
{
    private Handler $handler;

    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = app(Handler::class);
        $this->request = new Request;

        View::addNamespace('ig-common', __DIR__ . '/../../../resources/views');
        View::addNamespace('common', __DIR__ . '/../../../resources/views');

        if (! is_dir(__DIR__ . '/../../stubs/layouts')) {
            mkdir(__DIR__ . '/../../stubs/layouts', 0777, true);
        }

        file_put_contents(__DIR__ . '/../../stubs/layouts/empty.blade.php', '');

        app('translator')->addNamespace('ig-common', __DIR__ . '/../../../lang');

        View::getFinder()->flush();
    }

    public function test_db_read_only_exception_with_json_response()
    {
        $e = new DbReadOnlyException('Database is read-only');
        $this->request->headers->set('Accept', 'application/json');

        $response = $this->handler->render($this->request, $e);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(503, $response->getStatusCode());
        $this->assertEquals(['message' => 'Database is read-only'], $response->getData(true));
    }

    public function test_authentication_exception()
    {
        $e = new AuthenticationException;
        $this->request->headers->set('Accept', 'application/json');

        $response = $this->handler->render($this->request, $e);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(['message' => 'Unauthenticated.'], $response->getData(true));
    }

    public function test_validation_exception()
    {
        $e = ValidationException::withMessages(['field' => ['Invalid input']]);
        $this->request->headers->set('Accept', 'application/json');

        $response = $this->handler->render($this->request, $e);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals([
            'message' => 'Invalid input',
            'errors' => ['field' => ['Invalid input']],
        ], $response->getData(true));
    }

    public function test_connect_exception_with_json_response()
    {
        $guzzleRequest = new \GuzzleHttp\Psr7\Request('GET', 'http://example.com');
        $e = new ConnectException('Could not connect', $guzzleRequest);
        $this->request->headers->set('Accept', 'application/json');

        $response = $this->handler->render($this->request, $e);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertArrayHasKey('message', $response->getData(true));
    }

    public function test_throttle_exception_with_json_response()
    {
        $e = new HttpException(429);
        $this->request->headers->set('Accept', 'application/json');

        $response = $this->handler->render($this->request, $e);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(429, $response->getStatusCode());
        $this->assertArrayHasKey('message', $response->getData(true));
    }

    public function test_session_expired_with_json_response()
    {
        $e = new HttpException(419);
        $this->request->headers->set('Accept', 'application/json');

        $response = $this->handler->render($this->request, $e);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(419, $response->getStatusCode());
        $this->assertArrayHasKey('message', $response->getData(true));
    }

    public function test_generic_exception_with_json_response()
    {
        $e = new HttpException(500, 'Internal Server Error');
        $this->request->headers->set('Accept', 'application/json');

        $response = $this->handler->render($this->request, $e);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals(['message' => 'Internal Server Error'], $response->getData(true));
    }

    public function test_known_http_exception_with_html_response()
    {
        app()['env'] = 'production';

        $e = new HttpException(404);
        $response = $this->handler->render($this->request, $e);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('404', $response->getContent());

        app()['env'] = 'testing';
    }

    public function test_http_exception_renders_the_error_page_while_testing_even_in_debug_mode()
    {
        config(['app.debug' => true]);

        $response = $this->handler->render($this->request, new HttpException(403));

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('403 ' . __('ig-common::errors.403'), $response->getContent());
    }

    public function test_other_exceptions_are_left_to_laravel_while_testing()
    {
        $response = $this->handler->render($this->request, new RuntimeException('Boom'));

        $this->assertStringNotContainsString(__('ig-common::errors.500'), $response->getContent());
    }

    public function test_unknown_http_exception_with_html_response()
    {
        app()['env'] = 'production';

        $e = new HttpException(418);
        $response = $this->handler->render($this->request, $e);

        $this->assertEquals(418, $response->getStatusCode());
        $this->assertStringContainsString('418', $response->getContent());

        app()['env'] = 'testing';
    }

    public function test_multiple_http_status_codes_html_response()
    {
        app()['env'] = 'production';

        foreach ([401, 402, 403, 500, 503] as $code) {
            $e = new HttpException($code);
            $response = $this->handler->render($this->request, $e);

            $this->assertEquals($code, $response->getStatusCode());
            $this->assertStringContainsString((string) $code, $response->getContent());
        }

        app()['env'] = 'testing';
    }

    public function test_503_html_response_contains_auto_refresh_script()
    {
        app()['env'] = 'production';

        $e = new HttpException(503);
        $response = $this->handler->render($this->request, $e);

        $this->assertEquals(503, $response->getStatusCode());
        $this->assertStringContainsString('setTimeout(()=>location.reload(),30000)', $response->getContent());

        app()['env'] = 'testing';
    }

    public function test_non_503_html_response_has_no_refresh_script()
    {
        app()['env'] = 'production';

        foreach ([401, 404, 500] as $code) {
            $e = new HttpException($code);
            $response = $this->handler->render($this->request, $e);
            $this->assertStringNotContainsString('setTimeout(()=>location.reload()', $response->getContent());
        }

        app()['env'] = 'testing';
    }

    public function test_back_with_previous_page()
    {
        session(['prevPage' => '/previous']);

        $e = new DbReadOnlyException('Database is read-only');
        $response = $this->handler->render($this->request, $e);

        $this->assertStringEndsWith('/previous', $response->getTargetUrl());
    }

    public function test_back_without_previous_page()
    {
        session()->forget('prevPage');

        $e = new DbReadOnlyException('Database is read-only');
        $response = $this->handler->render($this->request, $e);

        $this->assertNotEmpty($response->getTargetUrl());
    }

    public function test_connect_exception_without_json_redirects_back()
    {
        app()['env'] = 'production';

        $guzzleRequest = new \GuzzleHttp\Psr7\Request('GET', 'http://example.com');
        $e = new ConnectException('Could not connect', $guzzleRequest);
        session(['prevPage' => '/previous']);

        $response = $this->handler->render($this->request, $e);

        $this->assertTrue(method_exists($response, 'getTargetUrl'));

        app()['env'] = 'testing';
    }

    public function test_throttle_exception_without_json_redirects_back()
    {
        app()['env'] = 'production';

        $e = new HttpException(429);
        session(['prevPage' => '/previous']);

        $response = $this->handler->render($this->request, $e);

        $this->assertTrue(method_exists($response, 'getTargetUrl'));

        app()['env'] = 'testing';
    }

    public function test_session_expired_without_json_redirects_back()
    {
        app()['env'] = 'production';

        $e = new HttpException(419);
        session(['prevPage' => '/previous']);

        $response = $this->handler->render($this->request, $e);

        $this->assertTrue(method_exists($response, 'getTargetUrl'));

        app()['env'] = 'testing';
    }

    /**
     * @return array<string, array{0: \Throwable}>
     */
    public static function malformedPayloadProvider(): array
    {
        return [
            'own guard' => [new BotPayloadException('[currentYear] holds a integer, received array')],
            'tampered snapshot' => [new CorruptComponentPayloadException('order-create')],
            'unknown component' => [new ComponentNotFoundException('no-such-component')],
            'locked property' => [new CannotUpdateLockedPropertyException('location')],
            'upload without trait' => [new MissingFileUploadsTraitException(new NamedComponentStub)],
        ];
    }

    #[DataProvider('malformedPayloadProvider')]
    public function test_malformed_livewire_payloads_are_demoted_to_debug(\Throwable $e)
    {
        Log::spy();

        $this->handler->report($e);

        Log::shouldHaveReceived('debug')->once();
        Log::shouldNotHaveReceived('error');
    }

    /**
     * A typed property with no default stayed uninitialized after hydration
     * (Livewire below 4.4.7 skipped null snapshot values for typed properties),
     * and expandConsolidatedFormObjectUpdates then read it before any update
     * hook could run. Such errors are classified by where they were raised, so
     * the test raises one there instead of depending on that Livewire bug.
     */
    public function test_a_raw_error_from_livewire_internals_is_demoted_to_debug()
    {
        $e = new \Error('Typed property UninitializedTypedPropertyStub::$anonymous must not be accessed before initialization');
        (new \ReflectionProperty(\Error::class, 'file'))->setValue($e, (new \ReflectionClass(Livewire::class))->getFileName());

        Log::spy();

        $this->handler->report($e);

        Log::shouldHaveReceived('debug')->once();
        Log::shouldNotHaveReceived('error');
    }

    /**
     * A scanner sent `[1, [...]]` as the update of a Carbon property; Livewire
     * hydrated it before any update hook ran, and Carbon rejected the array.
     */
    public function test_an_error_raised_by_a_library_livewire_called_is_demoted_to_debug()
    {
        try {
            CarbonSynth::hydrateFromType(Carbon::class, [1, []]);
            $this->fail('Carbon accepted an array.');
        } catch (\TypeError $e) {
        }

        Log::spy();

        $this->handler->report($e);

        Log::shouldHaveReceived('debug')->once();
        Log::shouldNotHaveReceived('error');
    }

    public function test_a_wrapped_malformed_payload_is_recognised_through_the_cause_chain()
    {
        Log::spy();

        $this->handler->report(new ViewException('...', 0, 1, 'view.blade.php', 12, new CorruptComponentPayloadException('feedback')));

        Log::shouldHaveReceived('debug')->once();
        Log::shouldNotHaveReceived('error');
    }

    public function test_application_exceptions_still_report()
    {
        Log::spy();

        $this->handler->report(new RuntimeException('the reservation could not be saved'));

        Log::shouldNotHaveReceived('debug');
        Log::shouldHaveReceived('error')->once();
    }

    public function test_livewire_developer_errors_still_report()
    {
        Log::spy();

        $this->handler->report(new MissingRulesException(new \stdClass));

        Log::shouldNotHaveReceived('debug');
        Log::shouldHaveReceived('error')->once();
    }

    public function test_an_error_raised_in_application_code_still_reports()
    {
        Log::spy();

        $this->handler->report(new \TypeError('htmlspecialchars(): Argument #1 ($string) must be of type string, array given'));

        Log::shouldNotHaveReceived('debug');
        Log::shouldHaveReceived('error')->once();
    }
}

/**
 * MissingFileUploadsTraitException builds its message from the component name.
 */
class NamedComponentStub
{
    public function getName(): string
    {
        return 'order-create';
    }
}
