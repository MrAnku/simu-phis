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
        Schema::create('new_ip_logins', function (Blueprint $table) {
            $table->id();
            $table->string('email')->comment('company email or subadmin email');
            $table->string('ip_address');
            $table->dateTime('login_time');
            $table->string('timezone')->nullable();
            $table->boolean('notified')->default(false);
            $table->string('company_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('new_ip_logins');
    }
};
