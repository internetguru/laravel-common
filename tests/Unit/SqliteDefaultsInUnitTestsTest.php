<?php

namespace Tests\Unit;

use Tests\TestCase;

class SqliteDefaultsInUnitTestsTest extends TestCase
{
    /**
     * Set the connections up before the providers register, as an application's
     * config/database.php is.
     */
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('database.connections.skeleton', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'busy_timeout' => null,
            'journal_mode' => null,
            'transaction_mode' => 'DEFERRED',
        ]);
    }

    public function test_unit_tests_leave_the_connection_alone()
    {
        $this->assertTrue($this->app->runningUnitTests());
        $this->assertNull(config('database.connections.skeleton.busy_timeout'));
        $this->assertNull(config('database.connections.skeleton.journal_mode'));
        $this->assertSame('DEFERRED', config('database.connections.skeleton.transaction_mode'));
    }
}
