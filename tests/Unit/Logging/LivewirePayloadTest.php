<?php

namespace Tests\Unit\Logging;

use Illuminate\Http\Request;
use InternetGuru\LaravelCommon\Logging\LivewirePayload;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;
use Tests\TestCase;

class LivewirePayloadTest extends TestCase
{
    public function test_recognises_the_hashed_update_path()
    {
        $this->assertStringNotContainsString('livewire/update', EndpointResolver::updatePath());
        $this->assertTrue(LivewirePayload::isUpdate(Request::create(EndpointResolver::updatePath(), 'POST')));
        $this->assertFalse(LivewirePayload::isUpdate(Request::create('/login', 'POST')));
    }

    public function test_replaces_snapshots_with_the_component_name_and_drops_refreshes()
    {
        $snapshot = json_encode(['data' => ['big' => 'state'], 'memo' => ['name' => 'booking-form']]);

        $logged = LivewirePayload::forLog([
            ['snapshot' => $snapshot, 'updates' => [], 'calls' => [['method' => 'save', 'params' => []]]],
            ['snapshot' => $snapshot, 'updates' => [], 'calls' => [['method' => '$refresh', 'metadata' => ['type' => 'poll']]]],
            ['snapshot' => $snapshot, 'updates' => ['note' => 'Hi'], 'calls' => [['method' => '$refresh']]],
        ]);

        $this->assertSame([
            0 => ['name' => 'booking-form', 'updates' => [], 'calls' => [['method' => 'save', 'params' => []]]],
            2 => ['name' => 'booking-form', 'updates' => ['note' => 'Hi'], 'calls' => []],
        ], $logged);
    }
}
