<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('deep_fake_audio', function (Blueprint $table) {
            $table->id();
            $table->string('audio_id');
            $table->string('name');
            $table->string('gender');
            $table->string('language');
            $table->string('use_case');
            $table->string('company_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deep_fake_audio');
    }
};
