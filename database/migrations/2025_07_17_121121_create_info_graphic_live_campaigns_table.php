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
        Schema::create('info_graphic_live_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_name');
            $table->string('campaign_id');
            $table->string('user_name');
            $table->string('user_email');
            $table->tinyInteger('sent')->default(0);
            $table->integer('infographic');
            $table->string('company_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('info_graphic_live_campaigns');
    }
};
