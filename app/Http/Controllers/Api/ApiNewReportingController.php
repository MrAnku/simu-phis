<?php

namespace App\Http\Controllers\Api;

use App\Models\Users;
use Illuminate\Http\Request;
use App\Models\TrainingModule;
use App\Http\Controllers\Controller;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\Auth;

class ApiNewReportingController extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id;
        $trainings = TrainingModule::where(function ($query) use ($companyId) {
            $query->where('company_id', $companyId)
                ->orWhere('company_id', 'default');
        })
        ->whereHas('trainingAssigned')
        ->select('id', 'name', 'category', 'passing_score')
        ->get();

        return response()->json([
            'success' => true,
            'message' => 'Reporting data retrieved successfully',
            'data' => [
                'trainings' => $trainings,
                'simulations' => [
                    'email',
                    'whatsapp',
                    'quishing',
                    'ai_vishing',
                    'tprm'
                ],
                'progress' => $this->getEmployeeProgress()
            ]
        ]);
    }

    private function getEmployeeProgress()
    {
        $companyId = Auth::user()->company_id;
        $uniqueUsers = Users::where('company_id', $companyId)
            ->whereHas('assignedTrainingsNew')
            ->select('user_name', 'user_email')
            ->distinct()
            ->get();
        $usersData = [];
        foreach ($uniqueUsers as $user) {
            $scoreAvg = TrainingAssignedUser::where('company_id', $companyId)
                ->where('user_email', $user->user_email)
                ->whereNotNull('personal_best')
                ->avg('personal_best');
            $totalTrainings = TrainingAssignedUser::where('company_id', $companyId)
                ->where('user_email', $user->user_email)
                ->count();

            $userDetails = [];

            $userDetails['name'] = $user->user_name;
            $userDetails['email'] = $user->user_email;
            $userDetails['score_avg'] = round($scoreAvg, 2);
            $userDetails['total_trainings'] = $totalTrainings;
            $usersData[] = $userDetails;

        }
        return $usersData;
    }

    public function trainingReport(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $trainingId = $request->route('training_id');

        if (!$trainingId) {
            return response()->json([
                'success' => false,
                'message' => 'Training ID is required'
            ], 400);
        }


        return response()->json([
            'success' => true,
            'message' => 'Training report retrieved successfully',
            'data' => [
                'cards' => $this->getTrainingStatistics($trainingId)
            ]
        ]);
    }

    public function getTrainingStatistics($trainingId){
        $companyId = Auth::user()->company_id;

        //training score average
        $scoreAvg = TrainingAssignedUser::where('company_id', $companyId)
            ->where('training', $trainingId)
            ->whereNotNull('personal_best')
            ->avg('personal_best');
        

        //training assigned

        $totalAssigned = TrainingAssignedUser::where('company_id', $companyId)
            ->where('training', $trainingId)
            ->count();

        //in progress
        $inProgress = TrainingAssignedUser::where('company_id', $companyId)
            ->where('training', $trainingId)
            ->where('personal_best', '<', 0)
            ->where('completed', 0)
            ->count();

        //training completed

        $completed = TrainingAssignedUser::where('company_id', $companyId)
            ->where('training', $trainingId)
            ->where('completed', 1)
            ->whereNotNull('personal_best')
            ->count();

        return [
            'score_avg' => round($scoreAvg, 2),
            'total_assigned' => $totalAssigned,
            'in_progress' => $inProgress,
            'completed' => $completed
        ];
    }
}
