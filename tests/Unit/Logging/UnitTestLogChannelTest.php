<?php

namespace Tests\Unit\Logging;

use Monolog\Handler\NullHandler;
use Tests\TestCase;

class UnitTestLogChannelTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('LOG_CHANNEL');
        unset($_ENV['LOG_CHANNEL'], $_SERVER['LOG_CHANNEL']);

        parent::tearDown();
    }

    public function test_unit_tests_log_to_the_null_channel()
    {
        $this->assertTrue($this->app->runningUnitTests());
        $this->assertSame('null', config('logging.default'));
        $this->assertSame(NullHandler::class, config('logging.channels.null.handler'));
    }

    public function test_a_log_channel_from_the_environment_does_not_bring_logging_back()
    {
        putenv('LOG_CHANNEL=stack');
        $_ENV['LOG_CHANNEL'] = $_SERVER['LOG_CHANNEL'] = 'stack';
        $this->refreshApplication();

        $this->assertSame('null', config('logging.default'));
    }
}
