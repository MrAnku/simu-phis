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
            // Rename columns
            $table->renameColumn('training_notif_localized', 'localized_notification');
            $table->renameColumn('help_destination', 'help_redirect_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_settings', function (Blueprint $table) {
            // Revert column names
            $table->renameColumn('localized_notification', 'training_notif_localized');
            $table->renameColumn('help_redirect_to', 'help_destination');
        });
    }
};
