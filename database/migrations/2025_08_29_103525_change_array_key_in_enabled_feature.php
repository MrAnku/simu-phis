<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $features = [
            'send-training' => 'send_training',
            'training-analytics' => 'training_analytics'
        ];

        $companies = DB::table('company')->get();

        foreach ($companies as $company) {
            try {
                $featureArray = json_decode($company->enabled_feature, true);
                foreach ($featureArray as &$value) {
                    if ($value === "send-training") {
                        $value = "send_training";
                    }
                    if ($value === "training-analytics") {
                        $value = "training_analytics";
                    }
                }

                DB::table('company')
                    ->where('id', $company->id)
                    ->update(['enabled_feature' => json_encode($featureArray)]);
                    
            } catch (\Exception $e) {
                continue;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enabled_feature', function (Blueprint $table) {
            //
        });
    }
};
