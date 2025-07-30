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
        Schema::table('blue_collar_training_users', function (Blueprint $table) {
            $table->string('certificate_path')->nullable()->after('certificate_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blue_collar_training_users', function (Blueprint $table) {
            $table->dropColumn('certificate_path');
        });
    }
};
