<?php

use App\Models\BlueCollarTrainingUser;
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
        Schema::table('blue_collar_training_users', function (Blueprint $table) {
           $table->boolean('training_started')->default(false)->after('training_type');
        });

         // Make training_started true for completed trainings
        BlueCollarTrainingUser::where('completed', 1)
        ->orWhere('personal_best', '>', 0)->update(['training_started' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blue_collar_training_users', function (Blueprint $table) {
           $table->dropColumn('training_started');
        });
    }
};
