<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('all_campaigns', function (Blueprint $table) {
            $table->string('email_freq')->default('once')->change();
            $table->date('expire_after')->nullable()->change();
        });

        DB::table('all_campaigns')
            ->where('email_freq', 'one')
            ->update(['email_freq' => 'once']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('all_campaigns', function (Blueprint $table) {
            $table->string('email_freq')->default('once')->change();
            $table->string('expire_after')->nullable()->change();
        });

        DB::table('all_campaigns')
            ->where('email_freq', 'once')
            ->update(['email_freq' => 'one']);
    }
};
