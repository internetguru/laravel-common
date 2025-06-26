<?php

namespace InternetGuru\LaravelCommon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use InternetGuru\LaravelCommon\Exceptions\DbReadOnlyException;

class ReadOnlyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (! config('app.readonly', false)) {
            return;
        }

        // Hook into the connection resolving to add a custom statement preparer
        DB::beforeExecuting(function ($query, $bindings, $connection) {
            $sql = strtolower(trim($query));

            // Skip sessions table queries
            if (str_contains($sql, 'sessions')) {
                return;
            }

            // Skip token_auths table queries (+mail_logs)
            if (str_contains($sql, 'token_auths')) {
                return;
            }

            if (str_contains($sql, 'mail_logs')) {
                return;
            }

            // More comprehensive check for read-only operations
            if (
                str_starts_with($sql, 'select') ||
                str_starts_with($sql, 'show') ||
                str_starts_with($sql, 'describe') ||
                str_starts_with($sql, 'explain') ||
                str_starts_with($sql, 'pragma') // For SQLite pragma queries
            ) {
                return;
            }

            // Throw exception before the query is executed
            throw new DbReadOnlyException(__('ig-common::errors.readonly'));
        });
    }
}
