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
            if (! Schema::hasColumn('spmb_settings', 'important_links')) {
                $table->json('important_links')->nullable();
            }

            if (! Schema::hasColumn('spmb_settings', 'poster_caption')) {
                $table->string('poster_caption', 250)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('spmb_settings')) {
            return;
        }

        Schema::table('spmb_settings', function (Blueprint $table): void {
            $columns = [];
            if (Schema::hasColumn('spmb_settings', 'important_links')) {
                $columns[] = 'important_links';
            }
            if (Schema::hasColumn('spmb_settings', 'poster_caption')) {
                $columns[] = 'poster_caption';
            }
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
