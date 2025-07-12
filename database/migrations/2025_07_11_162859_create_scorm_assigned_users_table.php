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
        Schema::create('scorm_assigned_users', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_id');
            $table->unsignedBigInteger('user_id');
            $table->string('user_name');
            $table->string('user_email');
            $table->unsignedBigInteger('scorm');
            $table->boolean('scorm_started')->default(false);
            $table->unsignedInteger('personal_best')->default(0);
            $table->string('grade')->nullable();
            $table->string('completed')->default(false);
            $table->string('assigned_date')->nullable();
            $table->string('scorm_due_date')->nullable();
            $table->string('completion_date')->nullable();
            $table->string('company_id');
            $table->string('certificate_id')->nullable();
            $table->string('last_reminder_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scorm_assigned_users');
    }
};
