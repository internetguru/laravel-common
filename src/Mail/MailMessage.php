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
        $this->subject = "$subject (Ref #{$this->refNumber})";

        return $this;
    }

    public function view($view, array $data = [])
    {
        return parent::view($view, $data + $this->extraMailData);
    }

    public function setExtraMailData(array $data): static
    {
        $this->extraMailData = $data;

        return $this;
    }
}
