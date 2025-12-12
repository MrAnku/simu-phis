<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateTrainingSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('training_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->boolean('content_survey')->default(false);
            $table->json('questions')->nullable();
            $table->timestamps();
        });

        // Select all fields from company table
       $records = Company::all();
       if ($records->isNotEmpty()) {
            foreach ($records as $record) {
                DB::table('training_settings')->insert([
                    'company_id'       => $record->company_id,
                     'email'       => $record->email,
                    'content_survey'   => 0,
                    'survey_questions'   => null,
                    'created_at'       => $record->created_at,
                    'updated_at'       => $record->created_at,
                ]);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('training_settings');
    }
}
