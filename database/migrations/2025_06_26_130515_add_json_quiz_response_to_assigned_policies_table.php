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
           $table->json('json_quiz_response')->nullable()->after('accepted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assigned_policies', function (Blueprint $table) {
           $table->dropColumn('json_quiz_response');
        });
    }
};
