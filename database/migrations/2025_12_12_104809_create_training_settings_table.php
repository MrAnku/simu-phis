<?php

use App\Models\Company;
use Illuminate\Support\Facades\DB; // corrected
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
        Schema::create('training_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_id', 255);
            $table->string('email', 255)->nullable();
            $table->tinyInteger('content_survey')->default(0);
            $table->longText('survey_questions')->nullable();
            $table->tinyInteger('localized_notification')->default(0);
            $table->string('help_redirect_to', 255)->nullable();
            $table->timestamps();
        });

        $records = Company::where('approved', 1)
            ->where('service_status', 1)
            ->get();

        if ($records->isNotEmpty()) {
            foreach ($records as $record) {
                DB::table('training_settings')->insert([
                    'company_id'       => $record->company_id,
                    'email'            => $record->email,
                    'content_survey'   => 0,
                    'survey_questions' => null,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_settings');
    }
};
