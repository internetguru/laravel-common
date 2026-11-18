<?php

namespace InternetGuru\LaravelCommon\Middleware;

use Closure;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Log;
use InternetGuru\LaravelCommon\Notifications\BaseNotification;
use Throwable;

class LogNotificationFailure
{
    /**
     * @param  SendQueuedNotifications&object{notification: BaseNotification}  $job
     * @param  Closure(SendQueuedNotifications): mixed  $next
     * @return mixed
     */
    public function handle($job, $next)
    {
        try {
            return $next($job);
        } catch (Throwable $e) {
            if ($job->attempts() < $job->tries) {
                Log::warning('Notification failed, retrying', [
                    'exception' => $e,
                    'notification' => $job->notification::class,
                ]);
                $job->release($job->notification->backoff);

                return;
            }

            throw $e;
        }
    }
}
