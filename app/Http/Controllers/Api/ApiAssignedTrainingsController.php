<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlueCollarScormAssignedUser;
use App\Models\BlueCollarTrainingUser;
use App\Models\ScormAssignedUser;
use App\Models\TrainingAssignedUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ApiAssignedTrainingsController extends Controller
{
    public function fetchEmpWithTrainings()
    {
        try {
            $companyId = Auth::user()->company_id;

            // Get all training assignments (including games)
            $trainings = TrainingAssignedUser::where('company_id', $companyId)->get();

            // Get all SCORM assignments
            $scorms = ScormAssignedUser::where('company_id', $companyId)->get();

            $employees = $this->buildEmployeeTrainingSummary($trainings, $scorms);

            if (empty($employees)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No trainings have been assigned to any employees.'
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Employees with trainings fetched successfully.',
                'data' => array_values($employees)
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    private function buildEmployeeTrainingSummary($trainings, $scorms)
    {
        $employees = [];

        foreach ($trainings as $training) {
            $email = $training->user_email;
            if (!isset($employees[$email])) {
                $employees[$email] = [
                    'user_email' => $email,
                    'trainings' => 0,
                    'games' => 0,
                    'scorms' => 0,
                ];
            }
            if ($training->training_type === 'games') {
                $employees[$email]['games']++;
            } else {
                $employees[$email]['trainings']++;
            }
        }

        foreach ($scorms as $scorm) {
            $email = $scorm->user_email;
            if (!isset($employees[$email])) {
                $employees[$email] = [
                    'user_email' => $email,
                    'trainings' => 0,
                    'games' => 0,
                    'scorms' => 0,
                ];
            }
            $employees[$email]['scorms']++;
        }

        return $employees;
    }

    public function fetchBlueCollarWithTrainings()
    {
        try {
            $companyId = Auth::user()->company_id;

            // Get all training assignments (including games)
            $trainings = BlueCollarTrainingUser::where('company_id', $companyId)->get();

            // Get all SCORM assignments
            $scorms = BlueCollarScormAssignedUser::where('company_id', $companyId)->get();

            $employees = $this->buildBlueEmpTrainingSummary($trainings, $scorms);

            if (empty($employees)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No trainings have been assigned to any employees.'
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Fetch Blue Collar Employees with trainings fetched successfully.',
                'data' => array_values($employees)
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    private function buildBlueEmpTrainingSummary($trainings, $scorms)
    {
        $employees = [];

        foreach ($trainings as $training) {
            $user_whatsapp = $training->user_whatsapp;
            if (!isset($employees[$user_whatsapp])) {
                $employees[$user_whatsapp] = [
                    'user_whatsapp' => $user_whatsapp,
                    'trainings' => 0,
                    'games' => 0,
                    'scorms' => 0,
                ];
            }
            if ($training->training_type === 'games') {
                $employees[$user_whatsapp]['games']++;
            } else {
                $employees[$user_whatsapp]['trainings']++;
            }
        }

        foreach ($scorms as $scorm) {
            $user_whatsapp = $scorm->user_whatsapp;
            if (!isset($employees[$user_whatsapp])) {
                $employees[$user_whatsapp] = [
                    'user_whatsapp' => $user_whatsapp,
                    'trainings' => 0,
                    'games' => 0,
                    'scorms' => 0,
                ];
            }
            $employees[$user_whatsapp]['scorms']++;
        }

        return $employees;
    }

    public function fetchTrainingDetails(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|in:normal,blue_collar',
                'identifier' => 'required'
            ]);

            $companyId = Auth::user()->company_id;
            $type = $request->type;
            $identifier = $request->identifier;

            if ($type === 'normal') {
                $request->validate([
                    'identifier' => 'email|exists:training_assigned_users,user_email'
                ]);
                $trainings = TrainingAssignedUser::where('company_id', $companyId)
                    ->where('user_email', $identifier)
                    ->where('training_type', '!=',  'games')
                    ->with('trainingData')
                    ->get();
            } else {
                $request->validate([
                    'identifier' => 'numeric|exists:blue_collar_training_users,user_whatsapp'
                ]);
                $trainings = BlueCollarTrainingUser::where('company_id', $companyId)
                    ->where('user_whatsapp', $identifier)
                    ->where('training_type', '!=',  'games')
                    ->with('trainingData')
                    ->get();
            }

            $result = [];
            foreach ($trainings as $training) {
                $result[] = [
                    'id'    => $training->id,
                    'training_name'    => $training->trainingData->name ?? null,
                    'personal_best'    => $training->personal_best,
                    'passing_score'    => $training->trainingData->passing_score ?? null,
                    'training_due_date' => $training->training_due_date,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Training details fetched successfully.',
                'data' => $result
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function fetchGameDetails(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|in:normal,blue_collar',
                'identifier' => 'required'
            ]);

            $companyId = Auth::user()->company_id;
            $type = $request->type;
            $identifier = $request->identifier;

            if ($type === 'normal') {
                $request->validate([
                    'identifier' => 'email|exists:training_assigned_users,user_email'
                ]);
                $games = TrainingAssignedUser::where('company_id', $companyId)
                    ->where('user_email', $identifier)
                    ->where('training_type', 'games')
                    ->with('trainingGame')
                    ->get();
            } else {
                $request->validate([
                    'identifier' => 'numeric|exists:blue_collar_training_users,user_whatsapp'
                ]);
                $games = BlueCollarTrainingUser::where('company_id', $companyId)
                    ->where('user_whatsapp', $identifier)
                    ->where('training_type', 'games')
                    ->with('trainingGame')
                    ->get();
            }

            $result = [];
            foreach ($games as $game) {
                $result[] = [
                    'id'    => $game->id,
                    'game_name'    => $game->trainingGame->name ?? null,
                    'personal_best'    => $game->personal_best,
                    'passing_score'    => $game->trainingGame->passing_score ?? null,
                    'game_due_date' => $game->training_due_date,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Game details fetched successfully.',
                'data' => $result
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function fetchScormDetails(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|in:normal,blue_collar',
                'identifier' => 'required'
            ]);

            $companyId = Auth::user()->company_id;
            $type = $request->type;
            $identifier = $request->identifier;

            if ($type === 'normal') {
                $request->validate([
                    'identifier' => 'email|exists:scorm_assigned_users,user_email'
                ]);
                $scorms = ScormAssignedUser::where('company_id', $companyId)
                    ->where('user_email', $identifier)
                    ->with('scormTrainingData')
                    ->get();
            } else {
                $request->validate([
                    'identifier' => 'numeric|exists:blue_collar_scorm_assigned_users,user_whatsapp'
                ]);
                $scorms = BlueCollarScormAssignedUser::where('company_id', $companyId)
                    ->where('user_whatsapp', $identifier)
                    ->with('scormTrainingData')
                    ->get();
            }

            $result = [];
            foreach ($scorms as $scorm) {
                $result[] = [
                    'id'    => $scorm->id,
                    'scorm_name'    => $scorm->scormTrainingData->name ?? null,
                    'personal_best'    => $scorm->personal_best,
                    'passing_score'    => $scorm->scormTrainingData->passing_score ?? null,
                    'scorm_due_date' => $scorm->scorm_due_date,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'SCORM details fetched successfully.',
                'data' => $result
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteTraining(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|in:normal,blue_collar',
                'training_id' => 'required'
            ]);

            $companyId = Auth::user()->company_id;
            $type = $request->type;

            if ($type === 'normal') {
                $trainingDeleted = TrainingAssignedUser::where('company_id', $companyId)
                    ->where('id', $request->training_id)
                    ->delete();
            } else {
                $trainingDeleted = BlueCollarTrainingUser::where('company_id', $companyId)
                    ->where('id', $request->training_id)
                    ->delete();
            }

            if ($trainingDeleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Training deleted successfully.'
                ], 200);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Assigned Training not found.'
                ], 404);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteScorm(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|in:normal,blue_collar',
                'training_id' => 'required'
            ]);

            $companyId = Auth::user()->company_id;
            $type = $request->type;

            if ($type === 'normal') {
                $trainingDeleted = ScormAssignedUser::where('company_id', $companyId)
                    ->where('id', $request->training_id)
                    ->delete();
            } else {
                $trainingDeleted = BlueCollarScormAssignedUser::where('company_id', $companyId)
                    ->where('id', $request->training_id)
                    ->delete();
            }

            if ($trainingDeleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Scorm deleted successfully.'
                ], 200);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Assigned Scorm not found.'
                ], 404);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
