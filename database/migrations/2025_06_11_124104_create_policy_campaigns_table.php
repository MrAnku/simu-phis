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
        Schema::create('policy_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_name');
            $table->string('campaign_id');
            $table->string('users_group');
            $table->string('policy');
            $table->timestamp('scheduled_at');
            $table->string('company_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_campaigns');
    }
};
