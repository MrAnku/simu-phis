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
        Schema::table('all_campaigns', function (Blueprint $table) {
            $table->longText('selected_users')->nullable()->after('users_group')->comment('null is for all users of the users_group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('all_campaigns', function (Blueprint $table) {
            $table->dropColumn('selected_users');
        });
    }
};
