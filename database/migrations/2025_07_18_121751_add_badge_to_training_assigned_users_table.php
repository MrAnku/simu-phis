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
            $table->json('badge')->nullable()->after('grade');
        });

        // $allUsers = TrainingAssignedUser::all();
        // foreach ($allUsers as $user) {
        //     $badge = getMatchingBadge('score', $user->personal_best);
        //     // This helper function accepts a criteria type and value, and returns the first matching badge

        //     if ($badge) {
        //         // Decode existing badges (or empty array if null)
        //         $existingBadges = json_decode($user->badge, true) ?? [];

        //         // Avoid duplicates
        //         if (!in_array($badge, $existingBadges)) {
        //             $existingBadges[] = $badge; // Add new badge
        //         }

        //         // Save back to the model
        //         $user->badge = json_encode($existingBadges);
        //     }

        //     $user->save();

        //     $totalCompletedTrainings = TrainingAssignedUser::where('user_email', $user->user_email)
        //         ->where('completed', 1)->count();

        //     $badge = getMatchingBadge('courses_completed', $totalCompletedTrainings);
        //     if ($badge) {
        //         // Decode existing badges (or empty array if null)
        //         $existingBadges = json_decode($user->badge, true) ?? [];

        //         // Avoid duplicates
        //         if (!in_array($badge, $existingBadges)) {
        //             $existingBadges[] = $badge; // Add new badge
        //         }

        //         // Save back to the model
        //         $user->badge = json_encode($existingBadges);
        //     }
        //     $user->save();
        // }
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
