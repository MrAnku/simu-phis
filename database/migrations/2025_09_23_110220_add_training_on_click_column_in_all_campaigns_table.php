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
            $table->boolean('training_on_click')->default(0)->after('training_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('all_campaigns', function (Blueprint $table) {
            $table->dropColumn('training_on_click');
        });
    }
};
