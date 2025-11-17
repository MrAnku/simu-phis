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
        Schema::create('company_brandings', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('company_name');
            $table->string('favicon');
            $table->string('light_logo');
            $table->string('dark_logo');
            $table->timestamps();
        });

        // Insert records from white_labelled_companies table
        $records = DB::table('white_labelled_companies')
            ->select('company_id', 'company_name', 'favicon', 'light_logo', 'dark_logo', 'created_at', 'updated_at')
            ->where('approved_by_partner', 1)
            ->where('service_status', 1)
            ->get();

        if ($records->isNotEmpty()) {
            foreach ($records as $record) {
                DB::table('company_brandings')->insert([
                    'company_id' => $record->company_id,
                    'company_name' => $record->company_name,
                    'favicon' => $record->favicon,
                    'light_logo' => $record->light_logo,
                    'dark_logo' => $record->dark_logo,
                    'created_at' => $record->created_at,
                    'updated_at' => $record->updated_at,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_brandings');
    }
};
