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
            $table->longText('ai_analysis')->nullable()->after('body');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phish_triage_report_logs', function (Blueprint $table) {
            $table->dropColumn('ai_analysis');
        });
    }
};
