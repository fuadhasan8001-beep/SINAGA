<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropForeign(['author_id']);
            $table->foreignId('author_id')->nullable()->change();
            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();
            $table->string('author_name')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('updated_by_name')->nullable();
        });

        Schema::table('content_entries', function (Blueprint $table): void {
            $table->string('author_name')->nullable();
        });

        DB::table('articles')
            ->leftJoin('users', 'users.id', '=', 'articles.author_id')
            ->select(['articles.id', 'users.name'])
            ->orderBy('articles.id')
            ->chunk(200, function ($articles): void {
                foreach ($articles as $article) {
                    if ($article->name !== null) {
                        DB::table('articles')->where('id', $article->id)->update(['author_name' => $article->name]);
                    }
                }
            });

        DB::table('content_entries')
            ->leftJoin('users', 'users.id', '=', 'content_entries.author_id')
            ->select(['content_entries.id', 'users.name'])
            ->orderBy('content_entries.id')
            ->chunk(200, function ($entries): void {
                foreach ($entries as $entry) {
                    if ($entry->name !== null) {
                        DB::table('content_entries')->where('id', $entry->id)->update(['author_name' => $entry->name]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropForeign(['author_id']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['author_name', 'updated_by', 'updated_by_name']);
            $table->foreignId('author_id')->nullable(false)->change();
            $table->foreign('author_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('content_entries', function (Blueprint $table): void {
            $table->dropColumn('author_name');
        });
    }
};
