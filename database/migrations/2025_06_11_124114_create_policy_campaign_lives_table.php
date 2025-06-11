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
        Schema::create('policy_campaign_lives', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_name');
            $table->string('campaign_id');
            $table->string('user_name');
            $table->string('user_email');
            $table->boolean('sent')->default(false);
            $table->boolean('accepted')->default(false);
            $table->timestamp('accepted_at')->nullable();
            $table->string('policy');
            $table->string('company_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_campaign_lives');
    }
};
