<?php

namespace InternetGuru\LaravelCommon\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use InternetGuru\LaravelCommon\Exceptions\GeolocationServiceException;
use InternetGuru\LaravelCommon\Mail\MailMessage as IgMailMessage;
use InternetGuru\LaravelCommon\Middleware\LogNotificationFailure;
use InternetGuru\LaravelCommon\Services\GeolocationService;
use Throwable;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 10;

    public int $backoff = 120; // 2 minutes

    protected ?string $ipAddress;

    protected ?string $timezone;

    protected ?int $userId;

    protected ?string $url = null;

    public function __construct()
    {
        $this->ipAddress = request()?->ip();
        try {
            $timezone = app(GeolocationService::class)->getLocation($this->ipAddress)->timezone;
        } catch (GeolocationServiceException $ex) {
            $timezone = null;
        }
        $this->timezone = $timezone;
        $this->userId = auth()?->id();
        $this->url = session('currentPage', null);
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    protected function getExtraMailData(): array
    {
        return [
            'ip' => $this->ipAddress,
            'timezone' => $this->timezone,
            'userId' => $this->userId,
            'url' => $this->url,
        ];
    }

    public function toMail(object $notifiable, ?string $refNumber = null): MailMessage
    {
        $message = new IgMailMessage($refNumber);
        $message->setExtraMailData($this->getExtraMailData());

        return $message
            ->subject('Laravel Common Base Notification')
            ->view(
                [
                    'html' => 'ig-common::layouts.email-html',
                    'text' => 'ig-common::layouts.email-plain',
                ],
                [
                    'content' => 'This is a base notification. Please extend this class and override the toMail method.',
                ]
            );
    }

    public function middleware()
    {
        return [
            new LogNotificationFailure,
        ];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Notification job permanently failed: ' . $exception->getMessage(), [
            'exception' => $exception,
            'notification' => get_class($this),
        ]);
    }
}
