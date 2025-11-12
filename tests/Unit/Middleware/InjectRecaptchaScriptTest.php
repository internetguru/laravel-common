<?php

namespace Tests\Unit\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InternetGuru\LaravelCommon\Contracts\ReCaptchaInterface;
use InternetGuru\LaravelCommon\Middleware\InjectRecaptchaScript;
use Lunaweb\RecaptchaV3\RecaptchaV3;
use Mockery;
use Tests\TestCase;

class InjectRecaptchaScriptTest extends TestCase
{
    private InjectRecaptchaScript $middleware;
    private $recaptchaMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recaptchaMock = Mockery::mock(ReCaptchaInterface::class);
        $this->middleware = new InjectRecaptchaScript($this->recaptchaMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_does_not_inject_when_recaptcha_is_disabled()
    {
        $this->recaptchaMock->shouldReceive('isEnabled')
            ->once()
            ->andReturn(false);

        $request = Request::create('/', 'GET');
        $response = new Response('<html><head></head><body>Test</body></html>');
        $response->header('Content-Type', 'text/html');

        $next = function ($req) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($request, $next);

        $this->assertEquals($response->getContent(), $result->getContent());
        $this->assertStringNotContainsString('recaptcha', $result->getContent());
    }

    public function test_does_not_inject_for_non_html_response()
    {
        $this->recaptchaMock->shouldReceive('isEnabled')
            ->once()
            ->andReturn(true);

        $request = Request::create('/', 'GET');
        $response = new Response('{"key": "value"}');
        $response->header('Content-Type', 'application/json');

        $next = function ($req) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($request, $next);

        $this->assertEquals($response->getContent(), $result->getContent());
    }

    public function test_does_not_inject_for_redirect_response()
    {
        $this->recaptchaMock->shouldReceive('isEnabled')
            ->once()
            ->andReturn(true);

        $request = Request::create('/', 'GET');
        $response = redirect('/login');

        $next = function ($req) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($request, $next);

        $this->assertTrue($result->isRedirection());
    }

    public function test_injects_recaptcha_script_before_closing_head_tag()
    {
        $this->recaptchaMock->shouldReceive('isEnabled')
            ->once()
            ->andReturn(true);

        $recaptchaV3Mock = Mockery::mock(RecaptchaV3::class);
        $recaptchaV3Mock->shouldReceive('initJs')
            ->once()
            ->andReturn('<script>recaptcha script</script>');

        $this->app->instance(RecaptchaV3::class, $recaptchaV3Mock);

        $request = Request::create('/', 'GET');
        $response = new Response('<html><head><title>Test</title></head><body>Content</body></html>');
        $response->header('Content-Type', 'text/html');

        $next = function ($req) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($request, $next);

        $this->assertStringContainsString('<script>recaptcha script</script></head>', $result->getContent());
        $this->assertStringContainsString('<title>Test</title>', $result->getContent());
    }

    public function test_does_not_inject_when_no_head_tag_present()
    {
        $this->recaptchaMock->shouldReceive('isEnabled')
            ->once()
            ->andReturn(true);

        $recaptchaV3Mock = Mockery::mock(RecaptchaV3::class);
        $recaptchaV3Mock->shouldReceive('initJs')
            ->once()
            ->andReturn('<script>recaptcha script</script>');

        $this->app->instance(RecaptchaV3::class, $recaptchaV3Mock);

        $request = Request::create('/', 'GET');
        $htmlWithoutHead = '<html><body>Content</body></html>';
        $response = new Response($htmlWithoutHead);
        $response->header('Content-Type', 'text/html');

        $next = function ($req) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($request, $next);

        // Should not contain the script since there's no </head> tag to replace
        $this->assertEquals($htmlWithoutHead, $result->getContent());
    }

    public function test_does_not_inject_when_response_has_no_content_type()
    {
        $this->recaptchaMock->shouldReceive('isEnabled')
            ->once()
            ->andReturn(true);

        $request = Request::create('/', 'GET');
        $response = new Response('<html><head></head><body>Test</body></html>');
        // No Content-Type header set

        $next = function ($req) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($request, $next);

        $this->assertEquals($response->getContent(), $result->getContent());
    }
}
