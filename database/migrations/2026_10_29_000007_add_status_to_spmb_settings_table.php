<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('spmb_settings') && ! Schema::hasColumn('spmb_settings', 'status')) {
            Schema::table('spmb_settings', function (Blueprint $table): void {
                $table->string('status', 24)->default('preparation')->after('introduction');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('spmb_settings') && Schema::hasColumn('spmb_settings', 'status')) {
            Schema::table('spmb_settings', function (Blueprint $table): void {
                $table->dropColumn('status');
            });
        }
    }
};
