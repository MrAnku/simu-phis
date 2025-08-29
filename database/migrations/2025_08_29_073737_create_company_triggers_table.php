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
        Schema::create('company_triggers', function (Blueprint $table) {
            $table->id();
            $table->enum('event_type', ['new_user']);
            $table->integer('training')->unsigned()->nullable();
            $table->integer('policy')->unsigned()->nullable();
            $table->tinyInteger('status')->default(1);
            $table->string('company_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_triggers');
    }
};
