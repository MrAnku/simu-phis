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
        Schema::table('scorm_trainings', function (Blueprint $table) {
            $table->string('scorm_version')->default('1.2')->after('category'); // Adding scorm_version column
            // Ensure the column is nullable if you want to allow existing records without this field
            // $table->string('scorm_version')->nullable()->default('1.2')->after('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scorm_trainings', function (Blueprint $table) {
            $table->dropColumn('scorm_version'); // Dropping scorm_version column
        });
    }
};
