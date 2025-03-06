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
    Schema::create('blue_collar_groups', function (Blueprint $table) {
        $table->id();
        $table->string('group_id', 255);
        $table->string('group_name', 255);
        $table->mediumText('users')->nullable();
        $table->string('company_id', 255);
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blue_collar_groups');
    }
};
