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
        Schema::table('comic_queues', function (Blueprint $table) {
            $table->string('comic_url')->nullable()->after('task_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comic_queues', function (Blueprint $table) {
            $table->dropColumn('comic_url');
        });
    }
};
