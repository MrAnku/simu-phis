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
        Schema::table('user_login', function (Blueprint $table) {
            $table->string('token')->nullable()->after('login_password');
            $table->string('login_password')->nullable()->change();
            $table->timestamp('token_expiry')->nullable()->after('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_login', function (Blueprint $table) {
            $table->dropColumn('token');
            $table->string('login_password')->change();
            $table->dropColumn('token_expiry');
        });
    }
};
