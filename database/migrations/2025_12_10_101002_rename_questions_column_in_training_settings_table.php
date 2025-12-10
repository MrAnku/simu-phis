<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_settings', function (Blueprint $table) {
            $table->renameColumn('questions', 'survey_questions');
        });
    }

    public function down(): void
    {
        Schema::table('training_settings', function (Blueprint $table) {
            $table->renameColumn('survey_questions', 'questions');
        });
    }
};
