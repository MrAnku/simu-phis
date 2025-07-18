<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_call_camp_live', function (Blueprint $table) {
            $table->tinyInteger('compromised')->default(0)->after('training_assigned');
        });
        // Update existing records to set compromised to 1 if training_assigned is 1
        DB::table('ai_call_camp_live')
            ->where('training_assigned', 1)
            ->update(['compromised' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_call_camp_live', function (Blueprint $table) {
            $table->dropColumn('compromised');
        });
    }
};
