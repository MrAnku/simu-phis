<?php

use App\Models\TrainingGame;
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
        Schema::table('training_games', function (Blueprint $table) {
            $table->bigInteger('passing_score')->after('cover_image')->nullable();
        });

       TrainingGame::query()->update(['passing_score' => 60]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_games', function (Blueprint $table) {
            $table->dropColumn('passing_score');
        });
    }
};
