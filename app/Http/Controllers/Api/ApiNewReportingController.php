<?php

namespace App\Http\Controllers\Api;

use App\Models\Users;
use Illuminate\Http\Request;
use App\Models\TrainingModule;
use App\Http\Controllers\Controller;
use App\Models\AiCallCampLive;
use App\Models\CampaignLive;
use App\Models\QuishingLiveCamp;
use App\Models\TrainingAssignedUser;
use App\Models\UsersGroup;
use App\Models\WaLiveCampaign;
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
        $trainingId = base64_decode($request->route('training_id'));

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
                'cards' => $this->getTrainingStatistics($trainingId),
                'simulation_over_time' => $this->trainingSimulationOverTime($trainingId),
                'progress_breakdown' => $this->progressBreakdown($trainingId),
                'security_awareness_radar' => $this->getSecurityAwarenessRadar($trainingId),
                'performance_by_group' => $this->getPerformanceByGroup($trainingId),
                // 'comparision_modules' => $this->getComparisionModules($trainingId)
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

    public function trainingSimulationOverTime($trainingId){
        $companyId = Auth::user()->company_id;

        // Prepare date periods for last 7 months (including current month)
        $periods = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $periods[] = [
            'label' => $date->format('M y'),
            'start' => $date->copy()->startOfMonth()->toDateString(),
            'end' => $date->copy()->endOfMonth()->toDateString(),
            ];
        }

        $result = [];
        foreach ($periods as $period) {
            // Email
            $emailCount = CampaignLive::where('company_id', $companyId)
            ->where('training_module', $trainingId)
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->count();

            // Quishing
            $quishingCount = QuishingLiveCamp::where('company_id', $companyId)
            ->where('training_module', $trainingId)
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->count();

            // Whatsapp
            $whatsappCount = WaLiveCampaign::where('company_id', $companyId)
            ->where('training_module', $trainingId)
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->count();

            // AI Vishing
            $aiCount = AiCallCampLive::where('company_id', $companyId)
            ->where('training_module', $trainingId)
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->count();

            $result[] = [
            'month' => $period['label'],
            'email' => $emailCount,
            'quishing' => $quishingCount,
            'whatsapp' => $whatsappCount,
            'AI' => $aiCount,
            ];
        }
        return $result;
    }

    public function progressBreakdown($trainingId){
        $companyId = Auth::user()->company_id;

        // Get total assigned users for this training
        $totalAssigned = TrainingAssignedUser::where('company_id', $companyId)
            ->where('training', $trainingId)
            ->count();

        // Get completed, in progress, and certified counts
        $completed = TrainingAssignedUser::where('company_id', $companyId)
            ->where('training', $trainingId)
            ->where('completed', 1)
            ->whereNotNull('personal_best')
            ->count();

        $inProgress = TrainingAssignedUser::where('company_id', $companyId)
            ->where('training', $trainingId)
            ->where('personal_best', '>', 0)
            ->where('personal_best', '<', 100)
            ->count();

        $certified = TrainingAssignedUser::where('company_id', $companyId)
            ->where('training', $trainingId)
            ->where('certificate_id', '!=', null)
            ->count();

        // Calculate percentages, avoid user by zero
        $completedPercent = $totalAssigned > 0 ? round(($completed / $totalAssigned) * 100, 2) : 0;
        $inProgressPercent = $totalAssigned > 0 ? round(($inProgress / $totalAssigned) * 100, 2) : 0;
        $certifiedPercent = $totalAssigned > 0 ? round(($certified / $totalAssigned) * 100, 2) : 0;

        return [
            'completed_rate' => $completedPercent,
            'in_progress_rate' => $inProgressPercent,
            'certified_rate' => $certifiedPercent
        ];
    }

    public function getSecurityAwarenessRadar($trainingId)
    {
        $companyId = Auth::user()->company_id;

        //get random 5 training modules
        $trainings = TrainingModule::where(function ($query) use ($companyId) {
            $query->where('company_id', $companyId)
                ->orWhere('company_id', 'default');
        })
        ->whereHas('trainingAssigned')
        ->inRandomOrder()
        ->take(6)
        ->get();

        // Prepare radar chart data
        $radarData = [];
        foreach ($trainings as $training) {
            $completed = TrainingAssignedUser::where('company_id', $companyId)
                ->where('training', $training->id)
                ->where('completed', 1)
                ->count();

            $inProgress = TrainingAssignedUser::where('company_id', $companyId)
                ->where('training', $training->id)
                ->where('personal_best', 0)
                ->count();

            $radarData[] = [
                'training' => $training->name,
                'completed' => $completed,
                'in_progress' => $inProgress
            ];
        }

        return $radarData;
    }

    public function getPerformanceByGroup($trainingId)
    {
        $companyId = Auth::user()->company_id;

        // Get all users for the company
        $userGroups = UsersGroup::where('company_id', $companyId)
            ->get();

        $performanceData = [];
        foreach ($userGroups as $userGroup) {
            if($userGroup->users == null) {
                continue; // Skip if no users in user group
            }
            $usersArray = json_decode($userGroup->users, true);

            $completed = TrainingAssignedUser::where('company_id', $companyId)
                ->where('training', $trainingId)
                ->whereIn('user_id', $usersArray)
                ->where('completed', 1)
                ->count();

            $inProgress = TrainingAssignedUser::where('company_id', $companyId)
                ->where('training', $trainingId)
                ->whereIn('user_id', $usersArray)
                ->where('personal_best', 0)
                ->count();
           

            $performanceData[] = [
                'group_name' => $userGroup->group_name,
                'completed' => $completed,
                'in_progress' => $inProgress
            ];
        }

        return $performanceData;
    }

    // public function getComparisionModules($trainingId){

    //     $companyId = Auth::user()->company_id;

    //     $randomfiveModuleIds = TrainingModule::where(function ($query) use ($companyId) {
    //         $query->where('company_id', $companyId)
    //             ->orWhere('company_id', 'default');
    //     })
    //     ->whereHas('trainingAssigned')
    //     ->inRandomOrder()
    //     ->take(5)
    //     ->pluck('id')
    //     ->toArray();
    //     $comparisionData = [];
    //     foreach ($randomfiveModuleIds as $moduleId) {
    //         $scoreAvg = TrainingAssignedUser::where('company_id', $companyId)
    //             ->where('training', $moduleId)
    //             ->whereNotNull('personal_best')
    //             ->avg('personal_best');
    //         $totalAssigned = TrainingAssignedUser::where('company_id', $companyId)
    //             ->where('training', $moduleId)
    //             ->count();
    // }


}
