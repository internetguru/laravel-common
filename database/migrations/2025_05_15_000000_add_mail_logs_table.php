<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // An app whose schema dump already holds the table publishes this migration unrun.
        if (Schema::hasTable('mail_logs')) {
            return;
        }

        Schema::create('mail_logs', function (Blueprint $table) {
            $table->id();
            $table->string('to');
            $table->string('replyto')->nullable();
            $table->string('subject');
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_logs');
    }
};
