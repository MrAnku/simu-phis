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
        Schema::table('policies', function (Blueprint $table) {
              $table->longText('policy_prompt')->nullable()->after('policy_description'); 
               $table->longText('generated_text')->nullable()->after('policy_prompt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('policies', function (Blueprint $table) {
         $table->dropColumn(['policy_prompt', 'generated_text']);
        });
    }
};
