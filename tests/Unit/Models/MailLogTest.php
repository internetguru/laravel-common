<?php

namespace Tests\Unit\Models;

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

    public function test_migration_empties_the_bodies_already_stored()
    {
        DB::table('mail_logs')->insert(['to' => 'jane@example.com', 'subject' => 'PIN', 'body' => 'Your PIN is 483920']);

        (require __DIR__ . '/../../../database/migrations/2026_11_14_000000_drop_bodies_from_mail_logs.php')->up();

        $this->assertNull(DB::table('mail_logs')->value('body'));
    }
}
