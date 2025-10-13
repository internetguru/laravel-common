<?php

namespace InternetGuru\LaravelCommon\Mail;

use Illuminate\Notifications\Messages\MailMessage as BaseMailMessage;
use InternetGuru\LaravelCommon\Services\GeolocationService;

class MailMessage extends BaseMailMessage
{
    public function subject($subject)
    {
        // random 5 digit number, can start with 0
        $refNumber = str_pad(random_int(100, 99999), 5, '0', STR_PAD_LEFT);
        $this->subject = "$subject (Ref #{$refNumber})";

        return $this;
    }

    public function view($view, array $data = [])
    {
        $data['ip'] = request()->ip();
        $data['timezone'] = app(GeolocationService::class)->getLocation($data['ip'])->timezone;

        return parent::view($view, $data);
    }

}
