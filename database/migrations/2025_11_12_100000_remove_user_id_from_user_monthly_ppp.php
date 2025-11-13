<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_monthly_ppp', function (Blueprint $table) {
            // Drop the unique constraint that includes user_id
            $table->dropUnique(['company_id', 'user_id', 'month_year']);
            
            // Drop indexes that include user_id
            $table->dropIndex(['company_id', 'user_id', 'month_year']);
            
            // Drop the user_id column
            $table->dropColumn('user_id');
            
            // Create new unique constraint without user_id
            $table->unique(['company_id', 'user_email', 'month_year']);
            
            // Create new indexes without user_id
            $table->index(['company_id', 'user_email', 'month_year']);
        });
    }

    public function down()
    {
        Schema::table('user_monthly_ppp', function (Blueprint $table) {
            // Add back user_id column
            $table->string('user_id')->after('company_id');
            
            // Drop new constraints
            $table->dropUnique(['company_id', 'user_email', 'month_year']);
            $table->dropIndex(['company_id', 'user_email', 'month_year']);
            
            // Restore original constraints
            $table->unique(['company_id', 'user_id', 'month_year']);
            $table->index(['company_id', 'user_id', 'month_year']);
        });
    }
};