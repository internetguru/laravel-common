<?php

namespace Tests\Unit\Middleware;

use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use InternetGuru\LaravelCommon\Http\Middleware\PreventDuplicateSubmissions;
use Tests\TestCase;

class PreventDuplicateSubmissionsTest extends TestCase
{
    private PreventDuplicateSubmissions $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new PreventDuplicateSubmissions;
        Cache::flush();
    }

    private function next(): \Closure
    {
        return fn () => response('OK');
    }

    /**
     * Bind the request into the container, mirroring what the HTTP kernel does
     * before running middleware — `Livewire::isLivewireRequest()` resolves the
     * request via the `request()` helper, not the $request handed to handle().
     */
    private function handle(Request $request)
    {
        $this->app->instance('request', $request);

        return $this->middleware->handle($request, $this->next());
    }

    public function test_blocks_an_identical_repeat_post_within_the_ttl()
    {
        $request = Request::create('/contact', 'POST', ['name' => 'Jane']);

        $first = $this->handle($request);
        $this->travel(5)->seconds();
        $second = $this->handle($request);

        $this->assertEquals('OK', $first->getContent());
        $this->assertTrue($second->isRedirect());
        $this->assertArrayHasKey('error', $second->getSession()->get('errors')->getBag('default')->toArray());
    }

    public function test_drops_a_double_click_without_an_error()
    {
        $request = Request::create('/contact', 'POST', ['name' => 'Jane']);

        $this->handle($request);
        $second = $this->handle($request);

        $this->assertTrue($second->isRedirect());
        $this->assertNull($second->getSession()->get('errors'));
    }

    public function test_lets_someone_signed_in_repeat_a_submission_sooner_than_a_guest()
    {
        $request = Request::create('/contact', 'POST', ['name' => 'Jane']);
        $this->be(new class extends User {});

        $this->handle($request);
        $this->travel(15)->seconds();

        // Past the ten seconds someone signed in gets, well inside the minute a guest gets.
        $this->assertEquals('OK', $this->handle($request)->getContent());
    }

    public function test_never_blocks_livewire_requests_even_with_an_identical_payload()
    {
        $plain = Request::create('/contact', 'POST', ['name' => 'Jane']);
        $this->handle($plain);

        // A Livewire update POST hashes to the same cache key (same ip/path/payload),
        // but must never be blocked: Livewire issues its own debounced requests to a
        // versioned `/livewire-{hash}/update` path, which a literal `livewire/update`
        // substring check (the old guard condition) no longer matches in Livewire 4.
        $livewire = Request::create('/contact', 'POST', ['name' => 'Jane']);
        $livewire->headers->set('X-Livewire', 'true');

        $first = $this->handle($livewire);
        $second = $this->handle($livewire);

        $this->assertEquals('OK', $first->getContent());
        $this->assertEquals('OK', $second->getContent());
    }
}
