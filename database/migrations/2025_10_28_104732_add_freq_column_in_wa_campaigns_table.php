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
        Schema::table('wa_campaigns', function (Blueprint $table) {
            $table->date('launch_date')->nullable()->after('selected_users');
            $table->string('msg_freq')->default('once')->after('launch_date');
            $table->date('expire_after')->nullable()->after('msg_freq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_campaigns', function (Blueprint $table) {
            $table->dropColumn('msg_freq');
            $table->dropColumn('expire_after');
            $table->dropColumn('launch_date');
        });
    }
};
