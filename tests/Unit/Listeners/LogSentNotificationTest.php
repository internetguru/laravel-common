<?php

namespace Tests\Unit\Listeners;

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LogSentNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('database.default', 'testing');
        Config::set('database.connections.testing', ['driver' => 'sqlite', 'database' => ':memory:']);
        Config::set('mail.default', 'array');
        (require __DIR__ . '/../../../database/migrations/2025_05_15_000000_add_mail_logs_table.php')->up();
        (require __DIR__ . '/../../../database/migrations/2026_11_14_000000_drop_bodies_from_mail_logs.php')->up();
    }

    public function test_a_sent_mail_is_logged()
    {
        $notification = new class extends Notification
        {
            public function via(object $notifiable): array
            {
                return ['mail'];
            }

            public function toMail(object $notifiable): MailMessage
            {
                return (new MailMessage)->subject('Your sign-in PIN')->line('Your PIN is 483920');
            }
        };

        NotificationFacade::route('mail', 'jane@example.com')->notifyNow($notification);

        $this->assertDatabaseHas('mail_logs', [
            'subject' => 'Your sign-in PIN',
            'notification' => $notification::class,
            'body' => null,
        ]);
        $this->assertStringContainsString('jane@example.com', DB::table('mail_logs')->value('to'));
    }

    public function test_a_notification_without_a_sent_message_is_not_logged()
    {
        event(new NotificationSent(new AnonymousNotifiable, new class extends Notification {}, 'mail', null));

        $this->assertSame(0, DB::table('mail_logs')->count());
    }

    public function test_a_failing_mail_log_does_not_fail_the_sent_mail()
    {
        Schema::drop('mail_logs');
        Log::spy();
        $notification = new class extends Notification
        {
            public function via(object $notifiable): array
            {
                return ['mail'];
            }

            public function toMail(object $notifiable): MailMessage
            {
                return (new MailMessage)->subject('Your order');
            }
        };

        NotificationFacade::route('mail', 'jane@example.com')->notifyNow($notification);

        Log::shouldHaveReceived('error')
            ->withArgs(fn (string $message, array $context) => str_starts_with($message, 'Mail log failed')
                && $context['notification'] === $notification::class)
            ->once();
    }
}
