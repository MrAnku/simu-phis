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
        Schema::table('ai_call_campaigns', function (Blueprint $table) {
            $table->renameColumn('emp_group', 'users_group');
            $table->renameColumn('emp_grp_name', 'users_grp_name');
            $table->renameColumn('training', 'training_module');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_call_campaigns', function (Blueprint $table) {
            $table->renameColumn('users_group', 'emp_group');
            $table->renameColumn('users_grp_name', 'emp_grp_name');
            $table->renameColumn('training_module', 'training');
        });
    }
};
