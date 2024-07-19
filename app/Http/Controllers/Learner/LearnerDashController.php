<?php

namespace App\Http\Controllers\Learner;

use Illuminate\Http\Request;
use App\Models\TrainingModule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\TrainingAssignedUser;

class LearnerDashController extends Controller
{
    public function index()
    {

        $userEmail = session('learner')->login_username; // Assuming you're using Laravel's built-in authentication

        $averageScore = DB::table('training_assigned_users')
            ->where('user_email', $userEmail)
            ->avg('personal_best');

        $assignedTrainingCount = TrainingAssignedUser::with('trainingData')
            ->where('user_email', $userEmail)
            ->where('completed', 0)
            ->get();

        $completedTrainingCount = TrainingAssignedUser::with('trainingData')
            ->where('user_email', $userEmail)
            ->where('completed', 1)
            ->get();

        // return $assignedTrainingCount;

        return view('learning.dashboard', compact('averageScore', 'assignedTrainingCount', 'completedTrainingCount'));
    }

    public function startTraining($training_id, $training_lang)
    {

        // $training_id = decrypt($training_id);

        return view('learning.training', ['trainingid' => $training_id, 'training_lang'=>$training_lang]);
    }

    public function loadTraining($training_id, $training_lang)
    {
        // Decode the ID
        $id = decrypt($training_id);
        $training_lang = base64_decode($training_lang);

        // Validate the ID
        if ($id === false) {
            return response()->json(['status' => 0, 'msg' => 'Invalid training module ID.']);
        }

        // Fetch the training data
        $trainingData = TrainingModule::find($id);

        // Check if the training module exists
        if (!$trainingData) {
            return response()->json(['status' => 0, 'msg' => 'Training Module Not Found']);
        }

        // Access the module_language attribute
        $moduleLanguage = $training_lang;

        // You can now use $moduleLanguage as needed
        if ($moduleLanguage !== 'en') {

            $jsonQuiz = json_decode($trainingData->json_quiz, true);

            $translatedArray = translateArrayValues($jsonQuiz, $moduleLanguage);
            $translatedJson_quiz = json_encode($translatedArray, JSON_UNESCAPED_UNICODE);
            // var_dump($translatedArray);

            $trainingData->json_quiz = $translatedJson_quiz;
            // var_dump($trainingData);
            // echo json_encode($trainingData, JSON_UNESCAPED_UNICODE);
        }

        // Pass data to the view
        return response()->json(['status' => 1, 'jsonData' => $trainingData]);
    }

    public function updateTrainingScore(Request $request)
    {
        // Validate the request
        $request->validate([
            'trainingScore' => 'required|integer',
        ]);

        $trainingScore = $request->input('trainingScore');

        // Assuming the session has 'training_assigned_user_id'
        $user_email = session('learner')->login_username;
        $user_id = session('learner')->user_id;

        // Fetch the current personal best score
        $isScoreGreaterThanBefore = DB::table('training_assigned_users')
            ->where('user_id', $user_id)
            ->where('user_email', $user_email)
            ->value('personal_best');

        $previousScore = (int)$isScoreGreaterThanBefore;
        $currentScore = (int)$trainingScore;

        if ($currentScore > $previousScore) {
            // Update the personal best score
            DB::table('training_assigned_users')
                ->where('user_id', $user_id)
                ->where('user_email', $user_email)
                ->update(['personal_best' => $currentScore]);

            if ($currentScore == 100) {
                // Update the training completion status
                DB::table('training_assigned_users')
                    ->where('user_id', $user_id)
                    ->where('user_email', $user_email)
                    ->update([
                        'completed' => 1,
                        'completion_date' => now()->format('Y-m-d'),
                    ]);
            }
        }

        return response()->json(['message' => 'Score updated']);
    }
}
