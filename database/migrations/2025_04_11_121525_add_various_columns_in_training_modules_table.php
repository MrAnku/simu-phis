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
        Schema::table('training_modules', function (Blueprint $table) {
            $table->id()->change();
            $table->string('core_behaviour')->nullable()->after('training_type');
            $table->string('content_type')->nullable()->after('core_behaviour');
            $table->string('language')->nullable()->after('content_type');
            $table->string('security')->nullable()->after('language');
            $table->string('role')->nullable()->after('security');
            $table->string('industry')->nullable()->after('role');
            $table->string('duration')->nullable()->after('industry');
            $table->string('tags')->nullable()->after('duration');
            $table->string('program_resources')->nullable()->after('tags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_modules', function (Blueprint $table) {
            $table->dropColumn([
                'core_behaviour',
                'content_type',
                'language',
                'security',
                'role',
                'industry',
                'duration',
                'tags',
                'program_resources',
            ]);
        });
    }
};
