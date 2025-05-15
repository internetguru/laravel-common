<?php

namespace InternetGuru\LaravelCommon\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use InternetGuru\LaravelCommon\Models\MailLog;

class IgMailable extends Mailable
{
    use Queueable, SerializesModels;

    protected function buildSubject($message)
    {
        // random 5 digit number, can start with 0
        $refNumber = str_pad(random_int(100, 99999), 5, '0', STR_PAD_LEFT);
        $ref = "(Ref #{$refNumber})";

        if ($this->subject) {
            $message->subject($this->subject . $ref);
        } else {
            $message->subject(Str::title(Str::snake(class_basename($this), ' ')) . $ref);
        }

        return $this;
    }

    public function send($mailer)
    {
        return $this->withLocale($this->locale, function () use ($mailer) {
            $this->prepareMailableForDelivery();

            $mailer = $mailer instanceof MailFactory
                            ? $mailer->mailer($this->mailer)
                            : $mailer;

            return $mailer->send($this->buildView(), $this->buildViewData(), function ($message) {
                $this->buildFrom($message)
                    ->buildRecipients($message)
                    ->buildSubject($message)
                    ->buildTags($message)
                    ->buildMetadata($message)
                    ->runCallbacks($message)
                    ->buildAttachments($message)
                    ->logEmail($message);
            });
        });
    }

    protected function logEmail($message)
    {
        $toAddresses = array_map(function ($recipient) {
            return $recipient['address'];
        }, $this->to ?? []);

        MailLog::log($message->getTo()[0]->getAddress(), $message->getSubject(), $message->getTextBody());
    }
}
