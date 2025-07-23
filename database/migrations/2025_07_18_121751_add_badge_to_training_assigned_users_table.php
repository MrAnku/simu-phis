<?php

use App\Models\TrainingAssignedUser;
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
        Schema::table('training_assigned_users', function (Blueprint $table) {
            $table->longText('badge')->nullable()->after('grade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_assigned_users', function (Blueprint $table) {
            $table->dropColumn('badge');
        });
    }
};
