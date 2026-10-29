<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spmb_settings', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180)->default('Penerimaan Murid Baru');
            $table->string('school_year', 30)->nullable();
            $table->text('introduction')->nullable();
            $table->json('schedule')->nullable();
            $table->json('requirements')->nullable();
            $table->json('steps')->nullable();
            $table->json('documents')->nullable();
            $table->unsignedSmallInteger('quota')->nullable();
            $table->text('fee_information')->nullable();
            $table->string('registration_url', 2048)->nullable();
            $table->string('poster_path')->nullable();
            $table->boolean('published')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('spmb_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('role', 120)->nullable();
            $table->string('phone', 40);
            $table->string('office_hours', 180)->nullable();
            $table->text('note')->nullable();
            $table->boolean('published')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('spmb_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->text('body');
            $table->dateTime('starts_at')->nullable();
            $table->boolean('published')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spmb_announcements');
        Schema::dropIfExists('spmb_contacts');
        Schema::dropIfExists('spmb_settings');
    }
};