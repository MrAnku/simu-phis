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
        Schema::table('white_labelled_companies', function (Blueprint $table) {
            $table->dropColumn(['company_name', 'dark_logo', 'light_logo', 'favicon']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('white_labelled_companies', function (Blueprint $table) {
            $table->string('company_name')->after('company_id');
            $table->string('dark_logo')->nullable()->after('learn_domain');
            $table->string('light_logo')->nullable()->after('dark_logo');
            $table->string('favicon')->nullable()->after('light_logo');
        });
    }
};
