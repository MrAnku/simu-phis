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
        Schema::table('info_graphic_live_campaigns', function (Blueprint $table) {
            $table->integer('comic')->nullable()->after('infographic');
            $table->integer('infographic')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('info_graphic_live_campaigns', function (Blueprint $table) {
            $table->dropColumn('comic');
            $table->integer('infographic')->change();
        });
    }
};
