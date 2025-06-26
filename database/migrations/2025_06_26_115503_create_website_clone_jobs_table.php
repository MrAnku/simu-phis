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
        Schema::create('website_clone_jobs', function (Blueprint $table) {
            $table->id();
            
            $table->string('url');
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->string('file_url')->nullable();
            $table->text('error_message')->nullable();
            $table->string('company_id');
            $table->timestamps();

           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_clone_jobs');
    }
};
