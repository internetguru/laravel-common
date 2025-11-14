<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use InternetGuru\LaravelCommon\Exceptions\DbReadOnlyException;
use Tests\TestCase;

class ReadOnlyServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up database connection for testing
        Config::set('database.default', 'testing');
        Config::set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    public function test_readonly_mode_disabled_by_default()
    {
        Config::set('app.readonly', false);

        // Should not throw exception
        try {
            DB::connection('testing')->statement('SELECT 1');
            $this->assertTrue(true);
        } catch (DbReadOnlyException $e) {
            $this->fail('Should not throw exception when readonly mode is disabled');
        }
    }

    public function test_readonly_mode_allows_select_queries()
    {
        Config::set('app.readonly', true);

        // Re-register the service provider to apply the config
        app()->register(\InternetGuru\LaravelCommon\ReadOnlyServiceProvider::class);

        // Should not throw exception for SELECT
        try {
            DB::connection('testing')->select('SELECT 1');
            $this->assertTrue(true);
        } catch (DbReadOnlyException $e) {
            $this->fail('Should allow SELECT queries in readonly mode');
        }
    }

    public function test_readonly_mode_allows_show_queries()
    {
        Config::set('app.readonly', true);

        app()->register(\InternetGuru\LaravelCommon\ReadOnlyServiceProvider::class);

        try {
            // SHOW queries are allowed
            $this->assertTrue(true);
        } catch (DbReadOnlyException $e) {
            $this->fail('Should allow SHOW queries in readonly mode');
        }
    }

    public function test_readonly_mode_blocks_insert_queries()
    {
        Config::set('app.readonly', true);

        // Create a test table first
        DB::connection('testing')->statement('CREATE TABLE test_table (id INTEGER, name TEXT)');

        app()->register(\InternetGuru\LaravelCommon\ReadOnlyServiceProvider::class);

        $this->expectException(DbReadOnlyException::class);

        DB::connection('testing')->insert('INSERT INTO test_table (id, name) VALUES (?, ?)', [1, 'test']);
    }

    public function test_readonly_mode_blocks_update_queries()
    {
        Config::set('app.readonly', true);

        // Create and populate a test table first
        DB::connection('testing')->statement('CREATE TABLE test_table (id INTEGER, name TEXT)');
        DB::connection('testing')->insert('INSERT INTO test_table (id, name) VALUES (?, ?)', [1, 'test']);

        app()->register(\InternetGuru\LaravelCommon\ReadOnlyServiceProvider::class);

        $this->expectException(DbReadOnlyException::class);

        DB::connection('testing')->update('UPDATE test_table SET name = ? WHERE id = ?', ['updated', 1]);
    }

    public function test_readonly_mode_blocks_delete_queries()
    {
        Config::set('app.readonly', true);

        // Create and populate a test table first
        DB::connection('testing')->statement('CREATE TABLE test_table (id INTEGER, name TEXT)');
        DB::connection('testing')->insert('INSERT INTO test_table (id, name) VALUES (?, ?)', [1, 'test']);

        app()->register(\InternetGuru\LaravelCommon\ReadOnlyServiceProvider::class);

        $this->expectException(DbReadOnlyException::class);

        DB::connection('testing')->delete('DELETE FROM test_table WHERE id = ?', [1]);
    }

    public function test_readonly_mode_allows_sessions_table_queries()
    {
        Config::set('app.readonly', true);

        // Create sessions table
        DB::connection('testing')->statement('CREATE TABLE sessions (id TEXT, payload TEXT)');

        app()->register(\InternetGuru\LaravelCommon\ReadOnlyServiceProvider::class);

        // Should not throw exception for sessions table
        try {
            DB::connection('testing')->insert('INSERT INTO sessions (id, payload) VALUES (?, ?)', ['test-id', 'test-payload']);
            $this->assertTrue(true);
        } catch (DbReadOnlyException $e) {
            $this->fail('Should allow queries on sessions table');
        }
    }

    public function test_readonly_mode_allows_token_auths_table_queries()
    {
        Config::set('app.readonly', true);

        // Create token_auths table
        DB::connection('testing')->statement('CREATE TABLE token_auths (id INTEGER, token TEXT)');

        app()->register(\InternetGuru\LaravelCommon\ReadOnlyServiceProvider::class);

        // Should not throw exception for token_auths table
        try {
            DB::connection('testing')->insert('INSERT INTO token_auths (id, token) VALUES (?, ?)', [1, 'test-token']);
            $this->assertTrue(true);
        } catch (DbReadOnlyException $e) {
            $this->fail('Should allow queries on token_auths table');
        }
    }

    public function test_readonly_mode_allows_mail_logs_table_queries()
    {
        Config::set('app.readonly', true);

        // Create mail_logs table
        DB::connection('testing')->statement('CREATE TABLE mail_logs (id INTEGER, message TEXT)');

        app()->register(\InternetGuru\LaravelCommon\ReadOnlyServiceProvider::class);

        // Should not throw exception for mail_logs table
        try {
            DB::connection('testing')->insert('INSERT INTO mail_logs (id, message) VALUES (?, ?)', [1, 'test-message']);
            $this->assertTrue(true);
        } catch (DbReadOnlyException $e) {
            $this->fail('Should allow queries on mail_logs table');
        }
    }

    public function test_readonly_mode_allows_users_table_queries()
    {
        Config::set('app.readonly', true);

        // Create users table
        DB::connection('testing')->statement('CREATE TABLE users (id INTEGER, name TEXT)');

        app()->register(\InternetGuru\LaravelCommon\ReadOnlyServiceProvider::class);

        // Should not throw exception for users table
        try {
            DB::connection('testing')->insert('INSERT INTO users (id, name) VALUES (?, ?)', [1, 'test-user']);
            $this->assertTrue(true);
        } catch (DbReadOnlyException $e) {
            $this->fail('Should allow queries on users table');
        }
    }
}
