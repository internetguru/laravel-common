<?php

namespace InternetGuru\LaravelCommon\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * A record of a sent mail. The body is not kept: it can carry sign-in PINs and links.
 */
class MailLog extends Model
{
    protected $fillable = [
        'to',
        'replyto',
        'subject',
        'notification',
    ];

    public static function log(string $to, ?string $replyTo, string $subject, ?string $notification = null): void
    {
        $fields = [
            'to' => $to,
            'replyto' => $replyTo,
            'subject' => $subject,
            'notification' => $notification,
        ];

        self::create($fields);

        Log::info('Mail sent', $fields);
    }
}
