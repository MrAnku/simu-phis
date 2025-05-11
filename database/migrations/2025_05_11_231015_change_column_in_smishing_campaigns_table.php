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
        Schema::table('smishing_campaigns', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['template_id']);
        });

        Schema::table('smishing_campaigns', function (Blueprint $table) {
            // Now change the column type
            $table->longText('template_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('smishing_campaigns', function (Blueprint $table) {
            // Revert column type
            $table->unsignedBigInteger('template_id')->change();
        });

        Schema::table('smishing_campaigns', function (Blueprint $table) {
            // Re-add the foreign key
            $table->foreign('template_id')->references('id')->on('smishing_templates')->onDelete('cascade');
        });
    }
};
