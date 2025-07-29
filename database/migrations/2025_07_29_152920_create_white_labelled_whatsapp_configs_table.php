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
        Schema::create('white_labelled_whatsapp_configs', function (Blueprint $table) {
            $table->id();
            $table->string('from_phone_id');
            $table->string('access_token');
            $table->string('business_id');
            $table->string('company_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('white_labelled_whatsapp_configs');
    }
};
