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
        Schema::table('ai_call_camp_live', function (Blueprint $table) {
            $table->renameColumn('employee_name', 'user_name');
            $table->renameColumn('employee_email', 'user_email');
            $table->renameColumn('training', 'training_module');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_call_camp_live', function (Blueprint $table) {
           $table->renameColumn('user_name', 'employee_name');
           $table->renameColumn('user_email', 'employee_email');
           $table->renameColumn('training_module', 'training');
        });
    }
};
