<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('association_histories', function (Blueprint $table) {
            $table->id();
            $table->morphs('associable');
            $table->string('column_name');
            $table->string('column_prev_value')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('association_histories');
    }
};
