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
        Schema::table('ai_call_campaigns', function (Blueprint $table) {
            $table->date('schedule_date')->nullable()->after('launch_type');
            $table->string('time_zone')->nullable()->after('schedule_date');
            $table->timestamp('start_time')->nullable()->after('time_zone');
            $table->timestamp('end_time')->nullable()->after('start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_call_campaigns', function (Blueprint $table) {
            $table->dropColumn('schedule_date');
            $table->dropColumn('time_zone');
            $table->dropColumn('start_time');
            $table->dropColumn('end_time');
        });
    }
};
