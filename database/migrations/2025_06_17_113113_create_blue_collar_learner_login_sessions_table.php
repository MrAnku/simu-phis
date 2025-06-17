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
        Schema::create('blue_collar_learner_login_sessions', function (Blueprint $table) {
            $table->id();
            $table->text('token');
            $table->string('whatsapp_number', 255);
            $table->timestamp('expiry');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blue_collar_learner_login_sessions');
    }
};
