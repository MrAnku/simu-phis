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
        Schema::create('comic_assigned_users', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_id');
            $table->integer('user_id');
            $table->string('user_name');
            $table->string('user_email');
            $table->integer('comic');
            $table->dateTime('assigned_at');
            $table->dateTime('seen_at')->nullable();
            $table->string('company_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comic_assigned_users');
    }
};
