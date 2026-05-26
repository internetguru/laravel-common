<?php

namespace Tests\Unit\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InternetGuru\LaravelCommon\Http\Middleware\InjectMetaRobots;
use Tests\TestCase;

class InjectMetaRobotsTest extends TestCase
{
    private InjectMetaRobots $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new InjectMetaRobots();
    }

    private function htmlResponse(string $html): Response
    {
        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }

    public function test_does_nothing_when_meta_robots_not_configured()
    {
        config(['ig-common.meta_robots' => null]);

        $request = Request::create('/');
        $next = fn($_) => $this->htmlResponse('<html><head></head><body></body></html>');

        $response = $this->middleware->handle($request, $next);

        $this->assertStringNotContainsString('meta name="robots"', $response->getContent());
    }

    public function test_injects_meta_tag_before_closing_head()
    {
        config(['ig-common.meta_robots' => 'noindex']);

        $request = Request::create('/');
        $next = fn($_) => $this->htmlResponse('<html><head><title>Test</title></head><body></body></html>');

        $response = $this->middleware->handle($request, $next);
        $content = $response->getContent();

        $this->assertStringContainsString('<meta name="robots" content="noindex"/>', $content);
        $this->assertLessThan(strpos($content, '</head>'), strpos($content, 'meta name="robots"'));
    }

    public function test_injects_multiple_directives()
    {
        config(['ig-common.meta_robots' => 'noindex,nofollow']);

        $request = Request::create('/');
        $next = fn($_) => $this->htmlResponse('<html><head></head><body></body></html>');

        $response = $this->middleware->handle($request, $next);

        $this->assertStringContainsString('content="noindex,nofollow"', $response->getContent());
    }

    public function test_escapes_robots_value()
    {
        config(['ig-common.meta_robots' => '<script>xss</script>']);

        $request = Request::create('/');
        $next = fn($_) => $this->htmlResponse('<html><head></head><body></body></html>');

        $response = $this->middleware->handle($request, $next);

        $this->assertStringNotContainsString('<script>xss</script>', $response->getContent());
        $this->assertStringContainsString('&lt;script&gt;', $response->getContent());
    }

    public function test_does_nothing_for_json_response()
    {
        config(['ig-common.meta_robots' => 'noindex']);

        $request = Request::create('/');
        $next = fn($_) => response()->json(['key' => 'value']);

        $response = $this->middleware->handle($request, $next);

        $this->assertStringNotContainsString('meta name="robots"', $response->getContent());
    }

    public function test_does_nothing_for_redirect_response()
    {
        config(['ig-common.meta_robots' => 'noindex']);

        $request = Request::create('/');
        $next = fn($_) => redirect('/other');

        $response = $this->middleware->handle($request, $next);

        $this->assertTrue($response->isRedirection());
    }

    public function test_passes_response_through_unchanged_when_disabled()
    {
        config(['ig-common.meta_robots' => null]);

        $html = '<html><head><title>Test</title></head><body>Hello</body></html>';
        $request = Request::create('/');
        $next = fn($_) => new Response($html);

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals($html, $response->getContent());
    }
}
