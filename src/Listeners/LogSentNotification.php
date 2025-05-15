<?php

namespace InternetGuru\LaravelCommon\Listeners;

use Illuminate\Notifications\Events\NotificationSent;
use InternetGuru\LaravelCommon\Models\MailLog;

class LogSentNotification
{
    public function handle(NotificationSent $event)
    {
        if ($event->channel !== 'mail') {
            return;
        }

        $message  = $event->response->getOriginalMessage();
        $headers = $message->getHeaders();
        $to = $headers->get('to')->toString();
        $replyTo = $headers->get('reply-to')?->toString() ?? null;
        $subject = $message->getSubject() ?? 'n/a';
        $body = $message->getTextBody() ?? 'n/a';

        MailLog::log($to, $replyTo, $subject, $body);
    }
}
