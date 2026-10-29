<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spmb_photos', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 160);
            $table->text('caption')->nullable();
            $table->string('image_path');
            $table->boolean('published')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spmb_photos');
    }
};
