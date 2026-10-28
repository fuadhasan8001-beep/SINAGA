<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('content_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 32)->index();
            $table->string('title', 180);
            $table->string('slug', 200)->nullable();
            $table->string('excerpt', 500)->default('');
            $table->longText('body')->nullable();
            $table->string('image_path')->nullable();
            $table->string('document_path')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('starts_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->boolean('published')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['type', 'published', 'sort_order']);
        });
    }

    public function down(): void { Schema::dropIfExists('content_entries'); }
};
