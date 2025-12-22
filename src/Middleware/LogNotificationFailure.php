<?php

namespace InternetGuru\LaravelCommon\Middleware;

use Illuminate\Support\Facades\Log;
use Throwable;

class LogNotificationFailure
{
    public function handle($job, $next)
    {
        try {
            return $next($job);
        } catch (\Throwable $e) {
            if ($job->attempts() < $job->tries) {
                Log::warning('Notification failed, retrying: ' . $e->getMessage());
                $job->release($job->notification->backoff);

                return;
            }

            throw $e;
        }
    }
}
