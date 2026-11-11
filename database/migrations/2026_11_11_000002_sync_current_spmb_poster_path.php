<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PREVIOUS_POSTER = 'spmb/posters/LYIAerRKNfVpva9WuI0VzjOqpkuw6yEQoXflzgVH.png';

    private const CURRENT_POSTER = 'spmb/posters/JZqm2AbqoIzElA78An1GKBzmk5LAGMYA7iVPTuua.png';

    public function up(): void
    {
        if (Schema::hasTable('spmb_settings') && Schema::hasColumn('spmb_settings', 'poster_path')) {
            DB::table('spmb_settings')->where('poster_path', self::PREVIOUS_POSTER)
                ->update(['poster_path' => self::CURRENT_POSTER]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('spmb_settings') && Schema::hasColumn('spmb_settings', 'poster_path')) {
            DB::table('spmb_settings')->where('poster_path', self::CURRENT_POSTER)
                ->update(['poster_path' => self::PREVIOUS_POSTER]);
        }
    }
};
