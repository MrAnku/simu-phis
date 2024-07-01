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
        Schema::create('all_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_id');
            $table->string('campaign_name');
            $table->string('campaign_type');
            $table->string('users_group', 10);
            $table->unsignedBigInteger('training_module')->nullable();
            $table->unsignedBigInteger('phishing_material')->nullable();
            $table->foreign('users_group')->references('group_id')->on('users_group');
            $table->foreign('training_module')->references('id')->on('training_modules');
            $table->string('training_lang', 255)->default('en')->nullable();

            $table->foreign('phishing_material')->references('id')->on('phishing_emails');

            $table->string('email_lang', 255)->default('en')->nullable();
            $table->string('launch_time');
            $table->string('launch_type');
            $table->string('email_freq');
            $table->time('startTime');
            $table->time('endTime');
            $table->string('expire_after')->nullable();
            $table->string('status');
            $table->string('company_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('all_campaigns');
    }
};
