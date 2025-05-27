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
        Schema::create('license_upgrade_reqs', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('partner_id');
            $table->integer('employees')->nullable();
            $table->integer('tprm_employees')->nullable();
            $table->integer('blue_collar_employees')->nullable();
            $table->date('expiry')->nullable();
            $table->boolean('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_upgrade_reqs');
    }
};
