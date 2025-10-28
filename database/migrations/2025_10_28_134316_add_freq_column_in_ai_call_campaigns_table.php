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
            $table->date('launch_date')->nullable()->after('status');
            $table->string('call_freq')->default('once')->after('launch_date');
            $table->date('expire_after')->nullable()->after('call_freq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_call_campaigns', function (Blueprint $table) {
            $table->dropColumn('call_freq');
            $table->dropColumn('expire_after');
            $table->dropColumn('launch_date');
        });
    }
};
