<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_session_locks', function ($table): void {
            $table->dropUnique(['session_id']);
        });
    }

    public function down(): void
    {
        Schema::table('account_session_locks', function ($table): void {
            $table->unique('session_id');
        });
    }
};
