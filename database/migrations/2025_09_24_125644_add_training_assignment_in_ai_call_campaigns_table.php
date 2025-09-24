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
           $table->string('training_assignment')->default('random')->nullable()->after('scorm_training');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_call_campaigns', function (Blueprint $table) {
            $table->dropColumn('training_assignment');
        });
    }
};
