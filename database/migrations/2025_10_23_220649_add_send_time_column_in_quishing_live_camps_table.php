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
        Schema::table('quishing_live_camps', function (Blueprint $table) {
            $table->timestamp('send_time')->default(DB::raw('CURRENT_TIMESTAMP'))->after('sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quishing_live_camps', function (Blueprint $table) {
            $table->dropColumn('send_time');
        });
    }
};
