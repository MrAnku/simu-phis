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
        Schema::table('blue_collar_scorm_assigned_users', function (Blueprint $table) {
            $table->date('assigned_date')->nullable()->change();
            $table->date('scorm_due_date')->nullable()->change();
            $table->date('completion_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blue_collar_scorm_assigned_users', function (Blueprint $table) {
            $table->string('assigned_date')->change();
            $table->string('scorm_due_date')->change();
            $table->string('completion_date')->change();
        });
    }
};
