<?php

use App\Models\CompanyLicense;
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
        Schema::create('company_alert_mails', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->dateTime('license_expired')->nullable();
            $table->dateTime('need_support')->nullable();
            $table->dateTime('user_limit_exceed')->nullable();
            $table->timestamps();
        });

        $companyIds = CompanyLicense::pluck('company_id')->toArray();
        foreach ($companyIds as $companyId) {
            DB::table('company_alert_mails')->insert([
                'company_id' => $companyId,
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
        Schema::dropIfExists('company_alert_mails');
    }
};
