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
           $table->string('grade')->nullable()->after('personal_best');
        });

        $allUsers = BlueCollarTrainingUser::where('personal_best', '>', 0)->get();

        foreach($allUsers as $user){
            if($user->personal_best >= 90){
                $user->grade = 'A+';
            } elseif($user->personal_best >= 80){
                $user->grade = 'A';
            } elseif($user->personal_best >= 70){
                $user->grade = 'B';
            } elseif($user->personal_best >= 60){
                $user->grade = 'C';
            } else {
                $user->grade = 'D';
            }
            $user->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blue_collar_training_users', function (Blueprint $table) {
            $table->dropColumn('grade');
        });
    }
};
