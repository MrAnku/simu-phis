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
        ->select('name', 'category', 'passing_score')
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
}
