<?php

namespace Tests\Unit\Exceptions;

use GuzzleHttp\Exception\ConnectException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;
use InternetGuru\LaravelCommon\Exceptions\DbReadOnlyException;
use InternetGuru\LaravelCommon\Exceptions\Handler;
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
        $this->request = new Request();

        View::addNamespace('ig-common', __DIR__ . '/../../../resources/views');
        View::addNamespace('common', __DIR__ . '/../../../resources/views');

        if (!is_dir(__DIR__ . '/../../stubs/layouts')) {
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
        $e = new AuthenticationException();
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
}
