<?php

namespace Tests\Unit\Middleware;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InternetGuru\LaravelCommon\Http\Middleware\InjectUmamiScript;
use Tests\TestCase;

class InjectUmamiScriptTest extends TestCase
{
    public function test_keeps_the_view_the_response_was_made_from()
    {
        config(['ig-common.umami_website_id' => 'site-id', 'ig-common.umami_src' => 'https://umami.example/script.js']);
        $view = new class implements Renderable
        {
            public function render(): string
            {
                return '<html><head></head><body></body></html>';
            }
        };

        $response = (new InjectUmamiScript)->handle(Request::create('/'), fn () => new Response($view, 200, ['Content-Type' => 'text/html']));

        $this->assertStringContainsString('data-website-id="site-id"', $response->getContent());
        $this->assertSame($view, $response->original);
    }
}
