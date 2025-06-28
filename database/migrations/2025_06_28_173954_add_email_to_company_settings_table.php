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
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('email')->after('company_id');
        });

        // Populate email field from company table
        DB::table('company_settings')
            ->join('company', 'company_settings.company_id', '=', 'company.company_id')
            ->update([
                'company_settings.email' => DB::raw('company.email')
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
