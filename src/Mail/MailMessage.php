<?php

namespace InternetGuru\LaravelCommon\Mail;

use Illuminate\Notifications\Messages\MailMessage as BaseMailMessage;
use Illuminate\Support\Str;

class MailMessage extends BaseMailMessage
{
    protected array $extraMailData = [
        'noreplyMessage' => '',
    ];

    protected string $refNumber;

    public function __construct()
    {
        $this->refNumber = Str::ref(5);
    }

    public function setRefNumber(string $refNumber)
    {
        $this->refNumber = $refNumber;

        return $this;
    }

    public function subject($subject)
    {
        $ref = strtoupper($this->refNumber);
        $this->subject = "$subject (Ref {$ref})";

        return $this;
    }

    public function replyTo($address, $name = null)
    {
        $this->updatenoreplyMessage($address);

        return parent::replyTo($address, $name);
    }

    public function from($address, $name = null)
    {
        $this->updatenoreplyMessage($address);

        return parent::from($address, $name);
    }

    public function view($view, array $data = [])
    {
        $address = $this->from[0][0] ?? config('mail.from.address', '');
        $this->updatenoreplyMessage($address);

        return parent::view($view, array_merge($data, $this->extraMailData));
    }

    public function setExtraMailData(array $data): static
    {
        $this->extraMailData = array_merge($this->extraMailData, $data);

        return $this;
    }

    private function updatenoreplyMessage(?string $address): void
    {
        if (! $address) {
            return;
        }
        if (Str::contains($address, ['no-reply', 'noreply'], ignoreCase: true)) {
            $this->setExtraMailData(['noreplyMessage' => __('ig-common::layouts.email.no-reply-note')]);
        } else {
            $this->setExtraMailData(['noreplyMessage' => '']);
        }
    }
}
