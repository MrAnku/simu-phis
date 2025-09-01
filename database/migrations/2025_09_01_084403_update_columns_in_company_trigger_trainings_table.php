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
        Schema::table('company_trigger_trainings', function (Blueprint $table) {
            $table->enum('employee_type', ['normal', 'bluecollar'])->default('normal')->after('id');
            $table->integer('user_id')->after('employee_type');
            $table->string('user_name')->after('user_id');
            $table->string('user_email')->nullable()->change();
            $table->string('user_whatsapp')->after('user_email')->nullable();
            $table->json('training')->nullable()->change();
            $table->json('policy')->nullable()->change();
            $table->json('scorm')->after('policy')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_trigger_trainings', function (Blueprint $table) {
            $table->dropColumn('employee_type');
            $table->dropColumn('user_id');
            $table->dropColumn('user_name');
            $table->string('user_email')->required()->change();
            $table->dropColumn('user_whatsapp');
            $table->tinyInteger('training')->nullable()->change();
            $table->tinyInteger('policy')->nullable()->change();
            $table->dropColumn('scorm');

        });
    }
};
