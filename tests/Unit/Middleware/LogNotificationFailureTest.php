<?php

namespace Tests\Unit\Middleware;

use Illuminate\Support\Facades\Log;
use InternetGuru\LaravelCommon\Middleware\LogNotificationFailure;
use Mockery;
use Tests\TestCase;

class LogNotificationFailureTest extends TestCase
{
    public function test_a_retried_failure_keeps_its_exception_and_notification()
    {
        Log::spy();
        $notification = new class
        {
            public int $backoff = 30;
        };
        $job = Mockery::mock();
        $job->tries = 3;
        $job->notification = $notification;
        $job->shouldReceive('attempts')->andReturn(1);
        $job->shouldReceive('release')->once()->with(30);
        $exception = new \RuntimeException('SMTP down');

        (new LogNotificationFailure)->handle($job, fn () => throw $exception);

        Log::shouldHaveReceived('warning')->once()->with('Notification failed, retrying', [
            'exception' => $exception,
            'notification' => $notification::class,
        ]);
    }
}
