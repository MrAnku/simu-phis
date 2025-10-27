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
        Schema::table('quishing_camps', function (Blueprint $table) {
          $table->string('email_freq')->default('once')->after('quishing_lang');
          $table->date('expire_after')->nullable()->after('email_freq');
          $table->date('launch_date')->nullable()->after('quishing_lang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quishing_camps', function (Blueprint $table) {
            $table->dropColumn('email_freq');
            $table->dropColumn('expire_after');
            $table->dropColumn('launch_date');
        });
    }
};
