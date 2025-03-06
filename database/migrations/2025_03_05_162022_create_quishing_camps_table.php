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
        Schema::create('quishing_camps', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_id');
            $table->string('campaign_name');
            $table->string('campaign_type');
            $table->string('users_group');
            $table->longText('training_module')->nullable();
            $table->string('training_assignment')->nullable();
            $table->integer('days_until_due')->nullable();
            $table->string('training_lang')->nullable();
            $table->string('training_type')->nullable();
            $table->longText('quishing_material')->nullable();
            $table->string('quishing_lang')->nullable();
            $table->enum('status', ['pending', 'running', 'completed']);
            $table->string('company_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quishing_camps');
    }
};
