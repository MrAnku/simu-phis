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
        Schema::table('company_triggers', function (Blueprint $table) {
            $table->enum('employee_type', ['normal', 'bluecollar'])->default('normal')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_triggers', function (Blueprint $table) {
            $table->enum('employee_type', ['normal', 'bluecollar', 'tprm'])->default('normal')->change();
        });
    }
};
