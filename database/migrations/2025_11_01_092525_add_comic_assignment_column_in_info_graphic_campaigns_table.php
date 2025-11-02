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
        Schema::table('info_graphic_campaigns', function (Blueprint $table) {
            $table->enum('comic_assignment', ['random', 'all'])->default('random')->after('comics');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('info_graphic_campaigns', function (Blueprint $table) {
            $table->dropColumn('comic_assignment');
        });
    }
};
