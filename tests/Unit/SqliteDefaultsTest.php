<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SqliteDefaultsTest extends TestCase
{
    private string $databaseFile;

    /**
     * Set the connections up before the providers register, as an application's
     * config/database.php is.
     */
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        // Outside unit tests, where the defaults apply; the queue must not be sync there
        $app->detectEnvironment(fn () => 'local');
        $app['config']->set('queue.default', 'null');

        $this->databaseFile = sys_get_temp_dir() . '/ig-common-sqlite-defaults-' . getmypid() . '.sqlite';
        touch($this->databaseFile);

        $config = $app['config'];
        $config->set('database.connections.skeleton', [
            'driver' => 'sqlite',
            'database' => $this->databaseFile,
            'prefix' => '',
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
            'transaction_mode' => 'DEFERRED',
        ]);
        $config->set('database.connections.custom', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'busy_timeout' => 1000,
            'journal_mode' => 'delete',
            'transaction_mode' => 'EXCLUSIVE',
        ]);
        $config->set('database.connections.other', [
            'driver' => 'mysql',
            'database' => 'app',
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach (['', '-wal', '-shm'] as $suffix) {
            @unlink($this->databaseFile . $suffix);
        }
    }

    public function test_unset_sqlite_options_get_the_defaults()
    {
        $this->assertSame(5000, config('database.connections.skeleton.busy_timeout'));
        $this->assertSame('wal', config('database.connections.skeleton.journal_mode'));
        $this->assertSame('IMMEDIATE', config('database.connections.skeleton.transaction_mode'));
    }

    public function test_options_set_by_the_application_are_kept()
    {
        $this->assertSame(1000, config('database.connections.custom.busy_timeout'));
        $this->assertSame('delete', config('database.connections.custom.journal_mode'));
        $this->assertSame('EXCLUSIVE', config('database.connections.custom.transaction_mode'));
    }

    public function test_other_drivers_are_left_alone()
    {
        $this->assertSame(['driver' => 'mysql', 'database' => 'app'], config('database.connections.other'));
    }

    public function test_the_connection_applies_the_pragmas()
    {
        $connection = DB::connection('skeleton');

        $this->assertSame('wal', $connection->selectOne('pragma journal_mode')->journal_mode);
        $this->assertSame(5000, (int) $connection->selectOne('pragma busy_timeout')->timeout);
    }
}
