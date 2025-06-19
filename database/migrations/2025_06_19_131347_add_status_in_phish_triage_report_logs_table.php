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
        Schema::table('phish_triage_report_logs', function (Blueprint $table) {
            $table->enum('status', ['reported', 'safe', 'spam', 'blocked'])
                ->default('reported')
                ->after('ai_analysis')
                ->comment('Status of the report triage process');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phish_triage_report_logs', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
