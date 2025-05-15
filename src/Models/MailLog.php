<?php

namespace InternetGuru\LaravelCommon\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class MailLog extends Model
{
    protected $fillable = [
        'to',
        'subject',
        'body',
    ];

    public static function log(string $to, string $subject, string $body)
    {
        self::create([
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
        ]);

        Log::info('Mail sent', [
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
        ]);
    }
}