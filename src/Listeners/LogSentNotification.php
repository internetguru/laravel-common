<?php

namespace InternetGuru\LaravelCommon\Listeners;

use Illuminate\Mail\SentMessage;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Log;
use InternetGuru\LaravelCommon\Models\MailLog;
use Symfony\Component\Mime\Email;
use Throwable;

class LogSentNotification
{
    /** @return void */
    public function handle(NotificationSent $event)
    {
        if ($event->channel !== 'mail') {
            return;
        }

        if (! $event->response instanceof SentMessage) {
            return;
        }

        $message = $event->response->getOriginalMessage();
        if (! $message instanceof Email) {
            return;
        }
        $headers = $message->getHeaders();
        $to = $headers->get('to')?->toString() ?? '';
        $replyTo = $headers->get('reply-to')?->toString() ?? null;
        $subject = $message->getSubject() ?? 'n/a';

        // The mail is already sent; failing here would retry the job and send it again
        try {
            MailLog::log($to, $replyTo, $subject, $event->notification::class);
        } catch (Throwable $e) {
            Log::error('Mail log failed: ' . $e->getMessage(), [
                'exception' => $e,
                'notification' => $event->notification::class,
            ]);
        }
    }
}
