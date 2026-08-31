<?php

namespace Tests\Unit\Logging;

use InternetGuru\LaravelCommon\Logging\DeduplicateRepeatedRecords;
use Tests\TestCase;

class LogDeduplicationDisabledTest extends TestCase
{
    /**
     * Testbench registers package providers before getEnvironmentSetUp() runs,
     * so the switch has to be set through the environment - which is how an
     * application turns it off anyway.
     */
    protected function setUp(): void
    {
        $_ENV['IG_LOG_DEDUPLICATION'] = 'false';
        putenv('IG_LOG_DEDUPLICATION=false');

        parent::setUp();
    }

    protected function tearDown(): void
    {
        unset($_ENV['IG_LOG_DEDUPLICATION']);
        putenv('IG_LOG_DEDUPLICATION');

        parent::tearDown();
    }

    public function test_no_channel_is_tapped_when_deduplication_is_off()
    {
        $this->assertFalse(config('ig-common.log_deduplication.enabled'));

        foreach (array_keys(config('logging.channels')) as $name) {
            $this->assertNotContains(
                DeduplicateRepeatedRecords::class,
                config("logging.channels.$name.tap") ?? [],
            );
        }
    }
}
