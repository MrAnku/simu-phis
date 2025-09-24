<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_call_campaigns', function (Blueprint $table) {
            $table->longText('training_module')->nullable()->change();
            $table->longText('scorm_training')->nullable()->change();
        });

        DB::table('ai_call_campaigns')->get()->each(function ($row) {
            $updateData = [];

            if (!empty($row->training_module)) {
                $updateData['training_module'] = json_encode([(string)$row->training_module]);
            }

            if (!empty($row->scorm_training)) {
                $updateData['scorm_training'] = json_encode([(string)$row->scorm_training]);
            }

            if (!empty($updateData)) {
                DB::table('ai_call_campaigns')
                    ->where('id', $row->id)
                    ->update($updateData);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_call_campaigns', function (Blueprint $table) {
            $table->string('training_module')->nullable()->change();
            $table->string('scorm_training')->nullable()->change();
        });
    }
};
