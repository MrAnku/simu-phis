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
        Schema::table('all_campaigns', function (Blueprint $table) {
            $table->json('phishing_material')->nullable()->change();
            $table->json('training_module')->nullable()->change();

            // Add 'training_assignment' column as VARCHAR
            $table->string('training_assignment', 255)->nullable()->after('training_module');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('all_campaigns', function (Blueprint $table) {
            $table->string('phishing_material', 255)->change();
            $table->string('training_module', 255)->change();

            // Drop 'training_assignment' column
            $table->dropColumn('training_assignment');
        });
    }
};
