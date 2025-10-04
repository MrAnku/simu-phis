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
        Schema::table('quishing_camps', function (Blueprint $table) {
            $table->boolean('compromise_on_click')->default(false)->after('training_on_click');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quishing_camps', function (Blueprint $table) {
            $table->dropColumn('compromise_on_click');
        });
    }
};
