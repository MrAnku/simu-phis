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
        Schema::table('ai_call_camp_live', function (Blueprint $table) {
            $table->string('training_type', 255)
            ->default("static_training")
            ->after('training_lang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_call_camp_live', function (Blueprint $table) {
            $table->dropColumn('training_type');
        });
    }
};
