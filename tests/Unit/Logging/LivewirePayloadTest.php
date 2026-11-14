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
}
