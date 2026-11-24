<?php

namespace Tests\Unit\Models;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InternetGuru\LaravelCommon\Models\MailLog;
use Tests\TestCase;

class MailLogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('database.default', 'testing');
        Config::set('database.connections.testing', ['driver' => 'sqlite', 'database' => ':memory:']);
        (require __DIR__ . '/../../../database/migrations/2025_05_15_000000_add_mail_logs_table.php')->up();
    }

    public function test_records_a_sent_mail_without_its_body()
    {
        (require __DIR__ . '/../../../database/migrations/2026_11_14_000000_drop_bodies_from_mail_logs.php')->up();
        Log::spy();

        MailLog::log('jane@example.com', null, 'Your sign-in PIN', 'App\Notifications\PinLoginNotification');

        $this->assertDatabaseHas('mail_logs', [
            'to' => 'jane@example.com',
            'subject' => 'Your sign-in PIN',
            'notification' => 'App\Notifications\PinLoginNotification',
            'body' => null,
        ]);
        Log::shouldHaveReceived('info')->with('Mail sent', \Mockery::on(fn (array $context) => ! array_key_exists('body', $context)));
    }

    public function test_creating_the_table_again_leaves_it_and_its_rows_alone()
    {
        DB::table('mail_logs')->insert(['to' => 'jane@example.com', 'subject' => 'PIN', 'body' => 'Hello']);

        (require __DIR__ . '/../../../database/migrations/2025_05_15_000000_add_mail_logs_table.php')->up();

        $this->assertSame(1, DB::table('mail_logs')->count());
    }

    public function test_migration_empties_the_bodies_already_stored()
    {
        DB::table('mail_logs')->insert(['to' => 'jane@example.com', 'subject' => 'PIN', 'body' => 'Your PIN is 483920']);

        (require __DIR__ . '/../../../database/migrations/2026_11_14_000000_drop_bodies_from_mail_logs.php')->up();

        $this->assertNull(DB::table('mail_logs')->value('body'));
    }

    private function insertSentDaysAgo(int $days): void
    {
        DB::table('mail_logs')->insert([
            'to' => "sent-{$days}@example.com",
            'subject' => 'PIN',
            'created_at' => now()->subDays($days),
            'updated_at' => now()->subDays($days),
        ]);
    }

    public function test_prunes_rows_older_than_the_configured_days()
    {
        (require __DIR__ . '/../../../database/migrations/2026_11_14_000000_drop_bodies_from_mail_logs.php')->up();
        Config::set('ig-common.mail_log_days', 90);
        $this->insertSentDaysAgo(91);
        $this->insertSentDaysAgo(89);

        $this->artisan('model:prune', ['--model' => [MailLog::class]])->assertSuccessful();

        $this->assertSame(['sent-89@example.com'], DB::table('mail_logs')->pluck('to')->all());
    }

    public function test_keeps_every_row_when_pruning_is_off()
    {
        (require __DIR__ . '/../../../database/migrations/2026_11_14_000000_drop_bodies_from_mail_logs.php')->up();
        Config::set('ig-common.mail_log_days', 0);
        $this->insertSentDaysAgo(400);

        $this->artisan('model:prune', ['--model' => [MailLog::class]])->assertSuccessful();

        $this->assertSame(1, DB::table('mail_logs')->count());
    }

    public function test_schedules_pruning_daily()
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command ?? '', 'model:prune') && str_contains($event->command, 'MailLog'));

        $this->assertNotNull($event);
        $this->assertSame('0 0 * * *', $event->expression);
    }
}
