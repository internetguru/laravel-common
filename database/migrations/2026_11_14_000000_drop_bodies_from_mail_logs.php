<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mail bodies carried sign-in PINs and links, so they are no longer kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_logs', function (Blueprint $table) {
            $table->text('body')->nullable()->change();
            $table->string('notification')->nullable()->after('subject');
        });

        DB::table('mail_logs')->update(['body' => null]);
    }

    public function down(): void
    {
        Schema::table('mail_logs', function (Blueprint $table) {
            $table->dropColumn('notification');
        });
    }
};
