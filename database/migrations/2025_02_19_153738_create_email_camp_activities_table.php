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
        Schema::create('email_camp_activities', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_id');
            $table->unsignedBigInteger('campaign_live_id');
            $table->datetime('email_sent_at')->nullable();
            $table->datetime('email_viewed_at')->nullable();
            $table->datetime('payload_clicked_at')->nullable();
            $table->datetime('compromised_at')->nullable();
            $table->json('client_details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_camp_activities');
    }
};
