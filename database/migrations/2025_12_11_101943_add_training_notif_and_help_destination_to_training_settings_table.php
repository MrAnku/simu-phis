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
        Schema::table('training_settings', function (Blueprint $table) {
             $table->boolean('training_notif_localized')->default(false)->after('survey_questions');
            $table->string('help_destination')->nullable()->after('training_notif_localized');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_settings', function (Blueprint $table) {
            $table->dropColumn(['training_notif_localized', 'help_destination']);
          
        });
    }
};
