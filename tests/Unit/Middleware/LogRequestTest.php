<?php

namespace Tests\Unit\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InternetGuru\LaravelCommon\Http\Middleware\LogRequest;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;
use Tests\TestCase;

class LogRequestTest extends TestCase
{
    /**
     * @param  array<int, array{updates: array<string, mixed>, calls: array<int, array<string, mixed>>}>  $components
     */
    private function handleLivewireRequest(array $components): void
    {
        $request = Request::create(EndpointResolver::updatePath(), 'POST', ['components' => $components]);
        $request->headers->set('X-Livewire', '1');
        (new LogRequest)->handle($request, fn () => response('ok'));
    }

    public function test_get_requests_are_not_logged()
    {
        Log::spy();

        (new LogRequest)->handle(Request::create('/'), fn () => response('ok'));

        Log::shouldNotHaveReceived('info');
    }

    public function test_polls_and_focus_refreshes_are_not_logged()
    {
        Log::spy();

        $this->handleLivewireRequest([
            ['updates' => [], 'calls' => [['method' => '$refresh', 'params' => [], 'metadata' => ['type' => 'poll']]]],
            ['updates' => [], 'calls' => [['method' => '$refresh', 'params' => [], 'metadata' => []]]],
        ]);

        Log::shouldNotHaveReceived('info');
    }

    public function test_actions_and_updates_are_logged_even_alongside_a_refresh()
    {
        Log::spy();

        $this->handleLivewireRequest([
            ['updates' => [], 'calls' => [['method' => '$refresh', 'params' => [], 'metadata' => ['type' => 'poll']]]],
            ['updates' => [], 'calls' => [['method' => 'save', 'params' => [], 'metadata' => []]]],
        ]);
        $this->handleLivewireRequest([
            ['updates' => ['content' => 'New'], 'calls' => [['method' => '$refresh', 'params' => [], 'metadata' => ['type' => 'poll']]]],
        ]);
        (new LogRequest)->handle(Request::create('/login', 'POST'), fn () => response('ok'));

        Log::shouldHaveReceived('info')->with('Incoming request')->times(3);
    }

    public function test_can_be_turned_off()
    {
        config(['ig-common.log_requests' => false]);
        Log::spy();

        (new LogRequest)->handle(Request::create('/login', 'POST'), fn () => response('ok'));

        Log::shouldNotHaveReceived('info');
    }
}
