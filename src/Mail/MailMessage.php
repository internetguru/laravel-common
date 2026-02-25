<?php

namespace InternetGuru\LaravelCommon\Mail;

use Illuminate\Notifications\Messages\MailMessage as BaseMailMessage;
use Illuminate\Support\Str;

class MailMessage extends BaseMailMessage
{
    protected array $extraMailData = [];

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

    public function view($view, array $data = [])
    {
        $this->extraMailData['refNumber'] = strtoupper($this->refNumber);

        return parent::view($view, array_merge($data, $this->extraMailData));
    }

    public function data()
    {
        $data = parent::data();

        $replyToAddress = array_column($this->replyTo ?? [], 0);
        $fromAddress = array_column($this->from ?? [], 0);

        // If reply-to is explicitly set, don't show the no-reply note
        $valid = ! empty($replyToAddress);

        // Otherwise, check if from address is a valid reply address
        if (! $valid) {
            foreach ($fromAddress as $address) {
                if ($this->isValidReplyAddress($address)) {
                    $valid = true;
                    break;
                }
            }
        }

        $data['noreplyMessage'] = $valid ? '' : __('ig-common::layouts.email.no-reply-note');

        return $data;
    }

    public function setExtraMailData(array $data): static
    {
        $this->extraMailData = array_merge($this->extraMailData, $data);

        return $this;
    }

    private function isValidReplyAddress(?string $address): bool
    {
        if (! $address) {
            return false;
        }
        if (Str::contains($address, ['no-reply', 'noreply'], ignoreCase: true)) {
            return false;
        }

        return true;
    }
}
