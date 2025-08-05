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
        Schema::table('assigned_policies', function (Blueprint $table) {
           $table->integer('reading_time')->nullable()->after('json_quiz_response')->comment('in seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assigned_policies', function (Blueprint $table) {
            $table->dropColumn('reading_time');
        });
    }
};
