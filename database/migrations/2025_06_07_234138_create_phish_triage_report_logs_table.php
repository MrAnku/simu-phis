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
        Schema::create('phish_triage_report_logs', function (Blueprint $table) {
            $table->id();
            $table->string('user_email');
            $table->string('reported_email');
            $table->string('subject');
            $table->longText('headers');
            $table->longText('body');
            $table->string('company_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phish_triage_report_logs');
    }
};
