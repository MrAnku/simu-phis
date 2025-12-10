<?php

use App\Models\Company;
use App\Models\PhishSetting;
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
        Schema::create('phish_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('email');
            $table->boolean('phish_results_visible')->default(false);
            $table->timestamps();
        });

        $companies = Company::all();
        foreach ($companies as $company) {
            PhishSetting::create([
                'company_id' => $company->company_id,
                'email' => $company->email,
                'phish_results_visible' => false,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phish_settings');
    }
};
