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

    public ?string $ipAddress = null;

    // Looked up when the mail is built, so raising a notification costs no geolocation request
    public ?string $timezone = null;

    /**
     * The key of the account the notification was raised for.
     *
     * An application may key its accounts by an auto-incrementing id or by a
     * string such as a ULID, and this is whatever auth()->id() returned.
     */
    public int|string|null $userId = null;

    public ?string $url = null;

    public function __construct()
    {
        // A queue worker or the scheduler has no requester; its request is a placeholder
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }
        $this->ipAddress = request()?->ip();
        $this->userId = auth()?->id();
        $this->url = session('currentPage', null);
    }

    // For a notification raised on someone else's behalf, e.g. staff accepting a customer's payment
    public function withoutRequester(): static
    {
        $this->ipAddress = null;
        $this->timezone = null;
        $this->userId = null;
        $this->url = null;

        return $this;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    protected function getExtraMailData(): array
    {
        return [
            'ip' => $this->ipAddress,
            'timezone' => $this->requesterTimezone(),
            'userId' => $this->userId,
            'url' => $this->url,
        ];
    }

    private function requesterTimezone(): ?string
    {
        if ($this->timezone !== null || $this->ipAddress === null) {
            return $this->timezone;
        }
        try {
            return $this->timezone = app(GeolocationService::class)->getLocation($this->ipAddress)->timezone;
        } catch (GeolocationServiceException) {
            return null;
        }
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
