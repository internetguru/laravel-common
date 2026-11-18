<?php

namespace InternetGuru\LaravelCommon\Listeners;

use Illuminate\Mail\SentMessage;
use Illuminate\Notifications\Events\NotificationSent;
use Symfony\Component\Mime\Email;
use InternetGuru\LaravelCommon\Models\MailLog;

class LogSentNotification
{
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

        MailLog::log($to, $replyTo, $subject, $event->notification::class);
    }
}
