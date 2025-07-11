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
        Schema::table('quishing_camps', function (Blueprint $table) {
            $table->integer('sender_profile')
                ->nullable()
                ->after('quishing_material')
                ->comment('optional sender profile for the campaign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quishing_camps', function (Blueprint $table) {
            $table->dropColumn('sender_profile');
        });
    }
};
