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

    protected ?array $sessionData = null;

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
        $this->sessionData = session()?->all();
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
            'session' => $this->sessionData,
        ];
    }

    protected function getMailMessage(): MailMessage
    {
        $mailMessage = new IgMailMessage;
        $mailMessage->setExtraMailData($this->getExtraMailData());

        return $mailMessage;
    }
}
