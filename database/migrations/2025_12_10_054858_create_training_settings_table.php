<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrainingSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('training_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');                 // varchar
            $table->boolean('content_survey')->default(false); // true / false
            $table->json('questions')->nullable();        // json format
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('training_settings');
    }
}
