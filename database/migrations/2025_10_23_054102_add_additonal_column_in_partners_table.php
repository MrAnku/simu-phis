<?php

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
        Schema::table('partners', function (Blueprint $table) {
            $table->string('official_address')->nullable()->after('company');
            $table->string('company_type')->nullable()->after('official_address');
            $table->string('website_url')->nullable()->after('company_type');
            $table->string('country')->nullable()->after('website_url');
            $table->string('company_size')->nullable()->after('country');
            $table->string('phone_no')->nullable()->after('company_size');
            $table->string('nature_of_partnership')->nullable()->after('phone_no');
            $table->integer('monthly_clients')->nullable()->after('nature_of_partnership')->comment('expected number of clients per month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn([
                'official_address',
                'company_type',
                'website_url',
                'country',
                'company_size',
                'phone_no',
                'nature_of_partnership',
                'monthly_clients'
            ]);
        });
    }
};
