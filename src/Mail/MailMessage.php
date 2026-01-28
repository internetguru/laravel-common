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
        // Reset noreply message from previous calls ~ e.g from base
        $this->extraMailData['noreplyMessage'] = '';

        $replyToAddress = array_column($this->replyTo ?? [], 0);
        $fromAddress = array_column($this->from ?? [], 0);

        // Replyto has priority over From address
        $checkAddresses = empty($replyToAddress) ? $fromAddress : $replyToAddress;

        // One valid address is enough
        $valid = false;
        foreach ($checkAddresses as $address) {
            if ($this->isValidReplyAddress($address)) {
                $valid = true;
                break;
            }
        }

        if (! $valid) {
            $this->extraMailData['noreplyMessage'] = __('ig-common::layouts.email.no-reply-note');
        }

        return parent::view($view, array_merge($data, $this->extraMailData));
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
