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
        Schema::create('white_labelled_companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('company_email');
            $table->string('domain');
            $table->string('learn_domain');
            $table->string('dark_logo');
            $table->string('light_logo');
            $table->string('favicon');
            $table->string('company_name');
            $table->integer('approved_by_partner')->default(0);
            $table->timestamp('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('white_labelled_companies');
    }
};
