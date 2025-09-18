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
        Schema::table('website_clone_jobs', function (Blueprint $table) {
            $table->string('task_id')->nullable()->after('file_url');
            $table->enum('site_type', ['static', 'spa'])->default('spa')->after('task_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('website_clone_jobs', function (Blueprint $table) {
            $table->dropColumn('task_id');
            $table->dropColumn('site_type');
        });
    }
};
