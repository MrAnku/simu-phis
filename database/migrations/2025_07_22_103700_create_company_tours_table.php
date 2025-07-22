<?php

use App\Models\Company;
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
        Schema::create('company_tours', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->tinyInteger('dashboard')->default(0);
            $table->tinyInteger('sidebar')->default(0);
            $table->tinyInteger('settings')->default(0);
            $table->timestamps();
        });

        $companies = Company::all();
        foreach($companies as $company){
            DB::table('company_tours')->insert([
                'company_id' => $company->company_id,
                'dashboard' => 0,
                'sidebar' => 0,
                'settings' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_tours');
    }
};
