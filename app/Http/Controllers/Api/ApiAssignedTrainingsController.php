<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssignedPolicy;
use App\Models\BlueCollarScormAssignedUser;
use App\Models\BlueCollarTrainingUser;
use App\Models\ComicAssignedUser;
use App\Models\ScormAssignedUser;
use App\Models\TrainingAssignedUser;
use Carbon\Carbon;
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

            // Get all Comic assignments
            $comics = ComicAssignedUser::where('company_id', $companyId)->get();

            // Get all Policies assignments
            $policies = AssignedPolicy::where('company_id', $companyId)->get();

            $employees = $this->buildEmployeeTrainingSummary($trainings, $scorms, $comics, $policies);

            return response()->json([
                'success' => true,
                'message' => __('Employees with trainings fetched successfully.'),
                'data' => array_values($employees)
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
            ], 500);
        }
    }

    private function buildEmployeeTrainingSummary($trainings, $scorms, $comics, $policies)
    {
        $employees = [];

        foreach ($trainings as $training) {
            $email = $training->user_email;
            if (!isset($employees[$email])) {
                $employees[$email] = [
                    'user_email' => $email,
                    'user_name' => $training->user_name,
                    'trainings' => 0,
                    'games' => 0,
                    'scorms' => 0,
                    'comics' => 0,
                    'policies' => 0,
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
                    'user_name' => $scorm->user_name,
                    'trainings' => 0,
                    'games' => 0,
                    'scorms' => 0,
                    'comics' => 0,
                    'policies' => 0,
                ];
            }
            $employees[$email]['scorms']++;
        }

        foreach ($comics as $comic) {
            $email = $comic->user_email;
            if (!isset($employees[$email])) {
                $employees[$email] = [
                    'user_email' => $email,
                    'user_name' => $comic->user_name,
                    'trainings' => 0,
                    'games' => 0,
                    'scorms' => 0,
                    'comics' => 0,
                    'policies' => 0,
                ];
            }
            $employees[$email]['comics']++;
        }

        foreach ($policies as $policy) {
            $email = $policy->user_email;
            if (!isset($employees[$email])) {
                $employees[$email] = [
                    'user_email' => $email,
                    'user_name' => $policy->user_name,
                    'trainings' => 0,
                    'games' => 0,
                    'scorms' => 0,
                    'comics' => 0,
                    'policies' => 0,
                ];
            }
            $employees[$email]['policies']++;
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

            return response()->json([
                'success' => true,
                'message' => __('Fetch Blue Collar Employees with trainings fetched successfully.'),
                'data' => array_values($employees)
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
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
                    'user_name' => $training->user_name,
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
                    'user_name' => $scorm->user_name,
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
                // $request->validate([
                //     'identifier' => 'email|exists:users,user_email'
                // ]);
                $trainings = TrainingAssignedUser::where('company_id', $companyId)
                    ->where('user_email', $identifier)
                    ->where('training_type', '!=',  'games')
                    ->with('trainingData')
                    ->get();
            } else {
                // $request->validate([
                //     'identifier' => 'numeric|exists:blue_collar_employees,whatsapp'
                // ]);
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
                    'status' => $training->completed == 1 ? 'completed' : 'incomplete',
                    'is_completed'      => $training->completed
                ];
            }

            return response()->json([
                'success' => true,
                'message' => __('Training details fetched successfully.'),
                'data' => $result
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
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
                // $request->validate([
                //     'identifier' => 'email|exists:users,user_email'
                // ]);
                $games = TrainingAssignedUser::where('company_id', $companyId)
                    ->where('user_email', $identifier)
                    ->where('training_type', 'games')
                    ->with('trainingGame')
                    ->get();
            } else {
                // $request->validate([
                //     'identifier' => 'numeric|exists:blue_collar_employees,whatsapp'
                // ]);
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
                    'status' => $game->completed == 1 ? 'completed' : 'incomplete',
                    'is_completed'      => $game->completed
                ];
            }

            return response()->json([
                'success' => true,
                'message' => __('Game details fetched successfully.'),
                'data' => $result
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
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
                // $request->validate([
                //     'identifier' => 'email|exists:users,user_email'
                // ]);
                $scorms = ScormAssignedUser::where('company_id', $companyId)
                    ->where('user_email', $identifier)
                    ->with('scormTrainingData')
                    ->get();
            } else {
                // $request->validate([
                //     'identifier' => 'numeric|exists:blue_collar_employees,whatsapp'
                // ]);
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
                    'status' => $scorm->completed == 1 ? 'completed' : 'incomplete',
                    'is_completed'      => $scorm->completed
                ];
            }

            return response()->json([
                'success' => true,
                'message' => __('SCORM details fetched successfully.'),
                'data' => $result
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
            ], 500);
        }
    }

    public function fetchComicDetails(Request $request)
    {
        try {
            $request->validate([
                'identifier' => 'required'
            ]);

            $companyId = Auth::user()->company_id;
            $identifier = $request->identifier;

            $comics = ComicAssignedUser::where('company_id', $companyId)
                ->where('user_email', $identifier)
                ->with('comicData')
                ->get();

            $result = [];
            foreach ($comics as $comic) {
                $result[] = [
                    'id'    => $comic->id,
                    'comic_name'    => $comic->comicData->name ?? null,
                    'assigned_at'    => Carbon::parse($comic->assigned_at)->toDateString(),
                    'seen_at' => $comic->seen_at,
                    'status' => $comic->seen_at ? 'seen' : 'unseen'
                ];
            }

            return response()->json([
                'success' => true,
                'message' => __('Comic details fetched successfully.'),
                'data' => $result
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
            ], 500);
        }
    }

    public function fetchPolicyDetails(Request $request)
    {
        try {
            $request->validate([
                'identifier' => 'required'
            ]);

            $companyId = Auth::user()->company_id;
            $identifier = $request->identifier;

            $policies = AssignedPolicy::where('company_id', $companyId)
                ->where('user_email', $identifier)
                ->with('policyData')
                ->get();

            $result = [];
            foreach ($policies as $policy) {
                $result[] = [
                    'id'    => $policy->id,
                    'policy_name'    => $policy->policyData->policy_name ?? null,
                    'accepted_at'    => $policy->accepted_at,
                    'reading_time' => $policy->reading_time,
                    'status' => $policy->accepted == 1 ? 'accepted' : 'not_accepted',
                ];
            }

            return response()->json([
                'success' => true,
                'message' => __('Policy details fetched successfully.'),
                'data' => $result
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
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
                    'message' => __('Training deleted successfully.')
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('Assigned Training not found.')
                ], 404);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
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
                    'message' => __('Assigned Scorm deleted successfully.')
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('Assigned Scorm not found.')
                ], 404);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
            ], 500);
        }
    }

    public function deleteComic(Request $request)
    {
        try {
            $request->validate([
                'comic_id' => 'required'
            ]);

            $comic_id = $request->query('comic_id');

            $companyId = Auth::user()->company_id;

            $comicDeleted = ComicAssignedUser::where('company_id', $companyId)
                ->where('id', $comic_id)
                ->delete();

            if ($comicDeleted) {
                return response()->json([
                    'success' => true,
                    'message' => __('Comic deleted successfully.')
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('Assigned Comic not found.')
                ], 404);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
            ], 500);
        }
    }

    public function deletePolicy(Request $request)
    {
        try {
            $request->validate([
                'policy_id' => 'required'
            ]);

            $policy_id = $request->query('policy_id');

            $companyId = Auth::user()->company_id;

            $policyDeleted = AssignedPolicy::where('company_id', $companyId)
                ->where('id', $policy_id)
                ->delete();

            if ($policyDeleted) {
                return response()->json([
                    'success' => true,
                    'message' => __('Assigned Policy deleted successfully.')
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('Assigned Policy not found.')
                ], 404);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
            ], 500);
        }
    }
}
