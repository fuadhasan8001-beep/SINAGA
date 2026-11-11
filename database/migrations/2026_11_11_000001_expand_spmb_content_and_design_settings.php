<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('spmb_settings')) {
            return;
        }

        Schema::table('spmb_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('spmb_settings', 'contact_name')) {
                $table->string('contact_name', 120)->nullable();
            }
            if (! Schema::hasColumn('spmb_settings', 'contact_role')) {
                $table->string('contact_role', 120)->nullable();
            }
            if (! Schema::hasColumn('spmb_settings', 'closing_text')) {
                $table->text('closing_text')->nullable();
            }
            if (! Schema::hasColumn('spmb_settings', 'extra_sections')) {
                $table->json('extra_sections')->nullable();
            }
            if (! Schema::hasColumn('spmb_settings', 'design_settings')) {
                $table->json('design_settings')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('spmb_settings')) {
            return;
        }

        Schema::table('spmb_settings', function (Blueprint $table): void {
            $columns = array_values(array_filter(
                ['contact_name', 'contact_role', 'closing_text', 'extra_sections', 'design_settings'],
                fn (string $column): bool => Schema::hasColumn('spmb_settings', $column),
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
