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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->string('role');
            $table->text('msg')->nullable(); // For additional information
            $table->unsignedBigInteger('role_id')->nullable(); // Reference to the user who performed the action
            $table->ipAddress('ip_address')->nullable(); // Storing the IP address
            $table->string('user_agent')->nullable(); // Storing the user agent (browser info)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
