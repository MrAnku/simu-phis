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
        Schema::create('company_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->integer('employees');
            $table->integer('used_employees')->nullable();
            $table->integer('tprm_employees')->default(0);
            $table->integer('used_tprm_employees')->nullable();
            $table->integer('blue_collar_employees')->default(0);
            $table->integer('used_blue_collar_employees')->nullable();
            $table->date('expiry');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_licenses');
    }
};
