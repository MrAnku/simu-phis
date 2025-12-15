<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scorm_assigned_users', function (Blueprint $table) {
            $table->longText('survey_response')->nullable()->after('updated_at');
        });

        Schema::table('blue_collar_training_users', function (Blueprint $table) {
            $table->longText('survey_response')->nullable()->after('updated_at');
        });

        Schema::table('blue_collar_scorm_assigned_users', function (Blueprint $table) {
            $table->longText('survey_response')->nullable()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('scorm_assigned_users', function (Blueprint $table) {
            $table->dropColumn('survey_response');
        });

        Schema::table('blue_collar_training_users', function (Blueprint $table) {
            $table->dropColumn('survey_response');
        });

        Schema::table('blue_collar_scorm_assigned_users', function (Blueprint $table) {
            $table->dropColumn('survey_response');
        });
    }
};
