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
        Schema::create('wa_live_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_id');
            $table->string('campaign_name');
            $table->string('campaign_type');
            $table->string('employee_type');
            $table->string('user_name');
            $table->integer('user_id');
            $table->string('user_email')->nullable();
            $table->string('user_phone');
            $table->unsignedBigInteger('phishing_website');
            $table->unsignedBigInteger('training_module')->nullable();
            $table->string('training_assignment')->nullable();
            $table->integer('days_until_due')->nullable();
            $table->string('training_lang')->nullable();
            $table->string('training_type')->nullable();
            $table->string('template_name');
            $table->json('variables')->nullable();
            $table->tinyInteger('sent')->default(0);
            $table->tinyInteger('payload_clicked')->default(0);
            $table->tinyInteger('compromised')->default(0);
            $table->tinyInteger('training_assigned')->default(0);
            $table->string('company_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_live_campaigns');
    }
};
