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
        Schema::create('qsh_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email_subject');
            $table->enum('difficulty', ['easy', 'medium', 'hard']);
            $table->string('file');
            $table->unsignedBigInteger('website')->nullable();
            $table->unsignedBigInteger('sender_profile')->nullable();

            $table->string('company_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qsh_templates');
    }
};
