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
        Schema::table('outlook_ad_tokens', function (Blueprint $table) {
            $table->text('refresh_token')->nullable()->after('access_token');
            $table->dateTime('expires_at')->nullable()->after('refresh_token');
        });
        //delete existing tokens
        DB::table('outlook_ad_tokens')->whereNull('refresh_token')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outlook_ad_tokens', function (Blueprint $table) {
            $table->dropColumn('refresh_token');
            $table->dropColumn('expires_at');
        });
    }
};
