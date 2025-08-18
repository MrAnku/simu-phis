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
        Schema::create('auto_sync_employees', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('local_group_id');
            $table->string('provider_group_id');
            $table->integer('sync_freq_days')->comment('Frequency in days to sync employees');
            $table->integer('sync_employee_limit')->comment('Limit for the number of employees to sync');
            $table->dateTime('last_synced_at')->nullable()->comment('Timestamp of the last sync');
            $table->string('company_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_sync_employees');
    }
};
