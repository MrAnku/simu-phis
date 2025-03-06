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
        Schema::create('quishing_live_camps', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_id');
            $table->string('campaign_name');
            $table->integer('user_id');
            $table->string('user_name');
            $table->string('user_email');
            $table->integer('training_module')->nullable();
            $table->integer('days_until_due')->nullable();
            $table->string('training_lang')->nullable();
            $table->string('training_type')->nullable();
            $table->integer('quishing_material')->nullable();
            $table->string('quishing_lang')->nullable();
            $table->enum('sent', [0, 1])->default(0);
            $table->enum('mail_open', [0, 1])->default(0);
            $table->enum('qr_scanned', [0, 1])->default(0);
            $table->enum('compromised', [0, 1])->default(0);
            $table->enum('email_reported', [0, 1])->default(0);
            $table->enum('training_assigned', [0, 1])->default(0);
            $table->string('company_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quishing_live_camps');
    }
};
