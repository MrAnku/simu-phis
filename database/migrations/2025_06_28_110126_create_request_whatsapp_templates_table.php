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
        Schema::create('request_whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_id')->unique();
            $table->string('name');
            $table->string('category');
            $table->string('language');
            $table->string('status')->default('PENDING');
            $table->string('waba_id');
            $table->string('company_id');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_whatsapp_templates');
    }
};
