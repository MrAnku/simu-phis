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
        Schema::create('company_trigger_trainings', function (Blueprint $table) {
            $table->id();
            $table->string('user_email');
            $table->tinyInteger('training')->nullable()->comment('This training will be assigned');
            $table->tinyInteger('policy')->nullable()->comment('This policy will be assigned');
            $table->tinyInteger('status')->default(0);
            $table->string('company_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_trigger_trainings');
    }
};
