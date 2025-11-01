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
        Schema::create('comic_queues', function (Blueprint $table) {
            $table->id();
            $table->string('topic');
            $table->string('task_id')->unique();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('company_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comic_queues');
    }
};
