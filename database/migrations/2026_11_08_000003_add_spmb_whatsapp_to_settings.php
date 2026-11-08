<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spmb_settings', function (Blueprint $table): void {
            $table->string('contact_whatsapp', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('spmb_settings', function (Blueprint $table): void {
            $table->dropColumn('contact_whatsapp');
        });
    }
};
