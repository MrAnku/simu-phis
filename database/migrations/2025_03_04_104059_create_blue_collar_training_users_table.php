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
        Schema::create('blue_collar_training_users', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->string('campaign_id', 255);
            $table->string('user_id', 255);
            $table->string('user_name', 255);
            $table->string('user_whatsapp', 255);
            $table->string('training', 255);
            $table->string('training_lang', 255)->nullable();
            $table->string('training_type', 255)->default('static_training');
            $table->integer('personal_best')->default(0);
            $table->string('completed', 255)->default('0');
            $table->date('assigned_date');
            $table->date('training_due_date');
            $table->date('completion_date')->nullable();
            $table->string('company_id', 255);
            $table->string('certificate_id', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blue_collar_training_users');
    }
};
