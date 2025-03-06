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
    Schema::create('blue_collar_employees', function (Blueprint $table) {
        $table->id();
        $table->string('group_id');
        $table->string('user_name');
        $table->string('user_company')->nullable();
        $table->string('user_job_title')->nullable();
        $table->bigInteger('whatsapp')->nullable();
        $table->timestamp('breach_scan_date')->nullable();
        $table->string('company_id');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blue_collar_employees');
    }
};
