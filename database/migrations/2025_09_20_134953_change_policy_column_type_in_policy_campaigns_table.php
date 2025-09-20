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
        Schema::table('policy_campaigns', function (Blueprint $table) {
            $table->longText('policy')->change();
        });

        DB::table('policy_campaigns')->get()->each(function ($row) {
            DB::table('policy_campaigns')
                ->where('id', $row->id)
                ->update(['policy' => json_encode([(string)$row->policy])]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('policy_campaigns', function (Blueprint $table) {
            $table->string('policy')->change();
        });
    }
};
