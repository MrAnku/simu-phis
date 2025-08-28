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
        Schema::create('phishing_replies', function (Blueprint $table) {
            $table->id();
            $table->string('from_address');
            $table->string('subject');
            $table->longText('headers');
            $table->longText('body');
            $table->string('campaign_id');
            $table->string('campaign_type');
            $table->string('company_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phishing_replies');
    }
};
