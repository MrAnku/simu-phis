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
        Schema::table('outlook_ad_tokens', function (Blueprint $table) {
            $table->text('refresh_token')->nullable()->after('access_token');
            $table->dateTime('expires_at')->nullable()->after('refresh_token');
        });
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
