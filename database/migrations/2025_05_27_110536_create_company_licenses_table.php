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
        Schema::create('company_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->integer('employees');
            $table->integer('used_employees')->default(0);
            $table->integer('tprm_employees')->default(0);
            $table->integer('used_tprm_employees')->default(0);
            $table->integer('blue_collar_employees')->default(0);
            $table->integer('used_blue_collar_employees')->default(0);
            $table->date('expiry');
            $table->timestamps();
        });

        // Now insert based on users table
        $companies = DB::table('company')->get();

        foreach ($companies as $company) {
            DB::table('company_licenses')->insert([
                'company_id' => $company->company_id,
                'employees' => $company->employees,
                'used_employees' => $company->usedemployees,
                'tprm_employees' => $company->employees,
                'used_tprm_employees' => 0,
                'blue_collar_employees' => $company->employees,
                'used_blue_collar_employees' => 0,
                'expiry' => now()->addMonths(2),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_licenses');
    }
};
