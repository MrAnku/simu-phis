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
        Schema::table('company', function (Blueprint $table) {
            $table->tinyInteger('eula_accepted')
                ->default(0)
                ->after('pass_create_token');
            $table->timestamp('eula_accepted_at')
                ->nullable()
                ->after('eula_accepted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company', function (Blueprint $table) {
            $table->dropColumn('eula_accepted');
            $table->dropColumn('eula_accepted_at');
        });
    }
};
