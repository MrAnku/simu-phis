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
        Schema::create('wa_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_id');
            $table->string('campaign_name');
            $table->string('campaign_type');
            $table->string('employee_type');
            $table->unsignedBigInteger('phishing_website');
            $table->longText('training_module')->nullable();
            $table->string('training_assignment')->nullable();
            $table->integer('days_until_due')->nullable();
            $table->string('training_lang')->nullable();
            $table->string('training_type')->nullable();
            $table->string('template_name');
            $table->string('users_group');
            $table->string('schedule_type');
            $table->datetime('launch_time');
            $table->enum('status', ['pending', 'running', 'completed']);
            $table->json('variables')->nullable();
            $table->string('company_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_campaigns');
    }
};
