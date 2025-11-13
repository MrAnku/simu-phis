<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_monthly_ppp', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('user_id');
            $table->string('user_email');
            $table->string('month_year'); // Store like "November 2025"
            $table->decimal('ppp_percentage', 5, 2); // 0.00 to 100.00
            $table->timestamps();

            // Ensure one record per user per month
            $table->unique(['company_id', 'user_id', 'month_year']);
            
            // Index for faster queries
            $table->index(['company_id', 'user_id', 'month_year']);
            $table->index(['company_id', 'month_year']);
            $table->index(['user_email', 'month_year']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_monthly_ppp');
    }
};