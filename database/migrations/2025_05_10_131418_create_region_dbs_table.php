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
        Schema::create('region_dbs', function (Blueprint $table) {
            $table->id();
            $table->string('region_name');
            $table->string('db_host');
            $table->integer('db_port');
            $table->string('db_database');
            $table->string('db_username');
            $table->string('db_password')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('region_dbs');
    }
};
