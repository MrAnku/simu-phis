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
        Schema::create('custom_notification_emails', function (Blueprint $table) {
            $table->id();
            $table->string('template_name');
            $table->string('email_subject');
            $table->string('file_path');
            $table->boolean('status')->default(false);
            $table->string('company_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_notification_emails');
    }
};
