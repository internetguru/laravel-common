<?php

namespace Tests\Unit;

use Tests\TestCase;

class SqliteDefaultsDisabledTest extends TestCase
{
    /**
     * Set the connections up before the providers register, as an application's
     * config/database.php is.
     */
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $config = $app['config'];
        $config->set('ig-common.sqlite_defaults', false);
        $config->set('database.connections.skeleton', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'busy_timeout' => null,
            'journal_mode' => null,
            'transaction_mode' => 'DEFERRED',
        ]);
    }

    public function test_disabled_defaults_leave_the_connection_alone()
    {
        $this->assertNull(config('database.connections.skeleton.busy_timeout'));
        $this->assertNull(config('database.connections.skeleton.journal_mode'));
        $this->assertSame('DEFERRED', config('database.connections.skeleton.transaction_mode'));
    }
}
