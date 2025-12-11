<?php

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
        $records = DB::table('company')
            ->select(
                'email',
                'full_name',
                'company_name',
                'company_id',
                'partner_id',
                'employees',
                'storage_region',
                'approved',
                'service_status',
                'password',
                'pass_create_token',
                'account_type',
                'mssp_id',
                'created_at',
                'approve_date',
                'usedemployees',
                'role',
                'enabled_feature',
                'ip_whitelist'
            )
            ->where('approved', 1)
            ->where('service_status', 1)
            ->get();

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
