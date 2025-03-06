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
        Schema::table('whatsapp_camp_users', function (Blueprint $table) {
         $table->string('employee_type')->after('user_email');
         $table->string('user_email')->nullable()->change();
       
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_camp_users', function (Blueprint $table) {
            $table->dropColumn('employee_type');
            $table->string('user_email')->nullable(false)->change();
        });
    }
};
