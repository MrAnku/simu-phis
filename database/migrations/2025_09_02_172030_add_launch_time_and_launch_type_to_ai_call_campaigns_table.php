<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_call_campaigns', function (Blueprint $table) {
            $table->timestamp('launch_time')->default(DB::raw('CURRENT_TIMESTAMP'))->after('status');
            $table->string('launch_type')->default('immediately')->after('launch_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_call_campaigns', function (Blueprint $table) {
            $table->dropColumn('launch_time');
            $table->dropColumn('launch_type');
        });
    }
};
