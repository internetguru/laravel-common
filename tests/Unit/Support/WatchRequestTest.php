<?php

namespace Tests\Unit\Support;

use Illuminate\Http\Request;
use InternetGuru\LaravelCommon\Support\WatchRequest;
use Tests\TestCase;

class WatchRequestTest extends TestCase
{
    /** @param list<list<string>> $callsPerComponent */
    private function livewireRequest(array $callsPerComponent): Request
    {
        return Request::create('/livewire/update', 'POST', [
            'components' => array_map(fn (array $methods) => [
                'snapshot' => '{}',
                'updates' => ['note' => 'typed'],
                'calls' => array_map(fn (string $method) => ['method' => $method, 'params' => []], $methods),
            ], $callsPerComponent),
        ]);
    }

    public function test_checks_alone_are_a_watch_request_even_with_typed_values_and_child_commits()
    {
        $this->assertTrue(WatchRequest::is($this->livewireRequest([['watchRefresh'], ['watchCheck'], ['$commit']])));
    }

    public function test_a_user_action_makes_it_an_ordinary_request()
    {
        $this->assertFalse(WatchRequest::is($this->livewireRequest([['watchRefresh'], ['save']])));
    }

    public function test_a_request_without_checks_is_not_a_watch_request()
    {
        $this->assertFalse(WatchRequest::is($this->livewireRequest([['$commit']])));
        $this->assertFalse(WatchRequest::is(Request::create('/', 'POST')));
    }
}
