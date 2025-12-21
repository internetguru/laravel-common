<?php

namespace InternetGuru\LaravelCommon\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use InternetGuru\LaravelCommon\Exceptions\GeolocationServiceException;
use InternetGuru\LaravelCommon\Mail\MailMessage as IgMailMessage;
use InternetGuru\LaravelCommon\Services\GeolocationService;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
        $this->url = session('currentPage', config('app.url'));
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

    public function toMail(object $notifiable): MailMessage
    {
        $message = new IgMailMessage;
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
}
