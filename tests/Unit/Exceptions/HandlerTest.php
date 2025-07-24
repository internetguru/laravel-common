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
        
        // Add the package views path with both namespaces
        View::addNamespace('ig-common', __DIR__ . '/../../../resources/views');
        View::addNamespace('common', __DIR__ . '/../../../resources/views');

        // Create necessary stubs directory
        if (!is_dir(__DIR__ . '/../../stubs/layouts')) {
            mkdir(__DIR__ . '/../../stubs/layouts', 0777, true);
        }

        // Create empty stub since it's included in base.blade.php
        file_put_contents(__DIR__ . '/../../stubs/layouts/empty.blade.php', '');

        // Mock translations
        app('translator')->addNamespace('ig-common', __DIR__ . '/../../../lang');

        View::getFinder()->flush();
    }

    public function testDbReadOnlyExceptionWithJsonResponse()
    {
        $e = new DbReadOnlyException('Database is read-only');
        $this->request->headers->set('Accept', 'application/json');
        
        $response = $this->handler->render($this->request, $e);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(503, $response->getStatusCode());
        $this->assertEquals(['message' => 'Database is read-only'], $response->getData(true));
    }

    public function testAuthenticationException()
    {
        $e = new AuthenticationException();
        $this->request->headers->set('Accept', 'application/json');
        
        $response = $this->handler->render($this->request, $e);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(['message' => 'Unauthenticated.'], $response->getData(true));
    }

    public function testValidationException()
    {
        $e = ValidationException::withMessages(['field' => ['Invalid input']]);
        $this->request->headers->set('Accept', 'application/json');
        
        $response = $this->handler->render($this->request, $e);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());
        $expectedData = [
            'message' => 'Invalid input',
            'errors' => [
                'field' => ['Invalid input']
            ]
        ];
        $this->assertEquals($expectedData, $response->getData(true));
    }

    public function testConnectExceptionWithJsonResponse()
    {
        $request = new \GuzzleHttp\Psr7\Request('GET', 'http://example.com');
        $e = new ConnectException('Could not connect', $request);
        $this->request->headers->set('Accept', 'application/json');
        
        $response = $this->handler->render($this->request, $e);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertArrayHasKey('message', $response->getData(true));
    }

    public function testThrottleExceptionWithJsonResponse()
    {
        $e = new HttpException(429);
        $this->request->headers->set('Accept', 'application/json');
        
        $response = $this->handler->render($this->request, $e);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(429, $response->getStatusCode());
        $this->assertArrayHasKey('message', $response->getData(true));
    }

    public function testSessionExpiredWithJsonResponse()
    {
        $e = new HttpException(419);
        $this->request->headers->set('Accept', 'application/json');
        
        $response = $this->handler->render($this->request, $e);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(419, $response->getStatusCode());
        $this->assertArrayHasKey('message', $response->getData(true));
    }

    public function testUnknownHttpExceptionWithHtmlResponse()
    {
        app()['env'] = 'production';
        
        $e = new HttpException(418);
        
        $response = $this->handler->render($this->request, $e);
        
        $this->assertEquals(418, $response->getStatusCode());
        // Just verify status code is in the response since translations might not be available
        $this->assertStringContainsString('418', $response->getContent());
        
        app()['env'] = 'testing';
    }

    public function testKnownHttpExceptionWithHtmlResponse()
    {
        app()['env'] = 'production';
        
        $e = new HttpException(404);
        
        $response = $this->handler->render($this->request, $e);
        
        $this->assertEquals(404, $response->getStatusCode());
        // Just verify status code is in the response since translations might not be available
        $this->assertStringContainsString('404', $response->getContent());
        
        app()['env'] = 'testing';
    }

    public function testBackWithPreviousPage()
    {
        session(['prevPage' => '/previous']);
        
        $e = new DbReadOnlyException('Database is read-only');
        $response = $this->handler->render($this->request, $e);
        
        $this->assertStringEndsWith('/previous', $response->getTargetUrl());
    }

    public function testBackWithoutPreviousPage()
    {
        session()->forget('prevPage');
        
        $e = new DbReadOnlyException('Database is read-only');
        $response = $this->handler->render($this->request, $e);
        
        $this->assertTrue($response->getTargetUrl() !== '');
    }
}
