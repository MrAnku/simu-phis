<?php

namespace App\Services\Reports;

use App\Http\Controllers\Api\ApiDashboardController;
use App\Models\AiCallCampLive;
use App\Models\BreachedEmail;
use App\Models\CampaignLive;
use App\Models\QuishingLiveCamp;
use App\Models\TrainingModule;
use App\Services\CompanyReport;
use App\Services\EmployeeReport;
use App\Models\TrainingAssignedUser;
use App\Models\ScormAssignedUser;
use App\Models\Badge;
use App\Models\WaLiveCampaign;

class OverallNormalEmployeeReport
{
    protected $companyId;
    //constructor
    public function __construct($companyId)
    {
        $this->companyId = $companyId;
    }

    public function generateReport(): array
    {
        $apiDashboardController = new ApiDashboardController();
        // Logic to generate overall normal employee report
        return [
            'total_employees' => $this->totalEmployees(),
            'in_simulation' => $this->employeesInSimulation(),
            'overall_risk_score' => $this->overallRiskScore(),
            'emails_breached' => $this->emailsBreached(),
            'risk_analysis' => $this->riskAnalysis(),
            'training_analysis' => $this->trainingAnalysis(),
            'game_analysis' => $this->gameAnalysis(),
            'interaction_average' => $this->interactionAverage(),
            'score_average' => $this->scoreAverage(),
            'most_assigned_trainings' => $this->mostAssignedTrainings(),
            'most_completed_trainings' => $this->mostCompletedTrainings(),
            'most_compromised_employees' => $this->mostCompromisedEmployees(),
            'most_clicked_employees' => $this->mostClickedEmployees(),
            'most_ignored_employees' => $this->mostIgnoredEmployees(),
            'employee_badges' => $this->empBadges(),
            'leaderboard' => $apiDashboardController->buildLeaderboard()

        ];
    }
    private function totalEmployees(): int
    {
        $companyReport = new CompanyReport($this->companyId);
        return $companyReport->employees()->count();
    }

    private function employeesInSimulation(): int
    {
        $companyReport = new CompanyReport($this->companyId);
        $employees = $companyReport->employees();
        $emailsArray = $employees->pluck('user_email')->toArray();
        $email = CampaignLive::where('company_id', $this->companyId)
            ->whereIn('user_email', $emailsArray)
            ->count('user_email');
        $quishing = QuishingLiveCamp::where('company_id', $this->companyId)
            ->whereIn('user_email', $emailsArray)
            ->count('user_email');
        $whatsapp = WaLiveCampaign::where('company_id', $this->companyId)
            ->whereIn('user_email', $emailsArray)
            ->count('user_email');
        $ai_vishing = AiCallCampLive::where('company_id', $this->companyId)
            ->whereIn('user_email', $emailsArray)
            ->count('user_email');
        return $email + $quishing + $whatsapp + $ai_vishing;
    }

    private function overallRiskScore(): float
    {
        $companyReport = new CompanyReport($this->companyId);
        // Logic to calculate overall risk score
        return $companyReport->calculateOverallRiskScore();
    }

    private function emailsBreached(): int
    {
        $breachedEmails = BreachedEmail::where('company_id', $this->companyId)->count();
        // Logic to calculate email breached percentage
        return $breachedEmails;
    }

    public function riskAnalysis(): array
    {

        //users who are compromised less than or equal to 3 times
        $companyReport = new CompanyReport($this->companyId);
        $employees = $companyReport->employees();
        $inHighRisk = 0;
        $inModerateRisk = 0;
        $inLowRisk = 0;
        foreach ($employees as $employee) {
            $employeeReport = new EmployeeReport($employee->user_email, $this->companyId);
            $compromiseCount = $employeeReport->compromised();
            if ($compromiseCount <= 3) {
                $inLowRisk++;
            } elseif ($compromiseCount > 3 && $compromiseCount <= 6) {
                $inModerateRisk++;
            } else {
                $inHighRisk++;
            }
        }
        return [
            'in_high_risk' => $inHighRisk,
            'in_moderate_risk' => $inModerateRisk,
            'in_low_risk' => $inLowRisk,
        ];
    }

    private function trainingAnalysis(): array
    {
        $companyReport = new CompanyReport($this->companyId);
        // Logic to analyze training data
        return [
            'training_assigned' => $companyReport->totalTrainingAssigned(),
            'training_started' => $companyReport->totalTrainingStarted(),
            'training_completed' => $companyReport->completedTraining(),
            'badges_earned' => $companyReport->totalBadgesAssigned(),
            'certified_employees' => $companyReport->certifiedUsers(),
        ];
    }

    private function gameAnalysis(): array
    {
        $companyReport = new CompanyReport($this->companyId);
        // Logic to analyze game data
        return [
            'game_assigned' => $companyReport->totalGameAssigned(),
            'game_started' => $companyReport->totalGameStarted(),
            'game_completed' => $companyReport->completedGame(),
        ];
    }

    private function interactionAverage(): array
    {
        $companyReport = new CompanyReport($this->companyId);
        // Logic to calculate interaction averages
        return [
            'open_rate' => round($companyReport->emailViewed() / $companyReport->emailSent() * 100, 2),
            'click_rate' => round($companyReport->clickRate(), 2),
            'compromise_rate' => round($companyReport->compromised() / $companyReport->totalSimulations() * 100, 2),
            'ignore_rate' => round($companyReport->emailIgnored() / $companyReport->emailSent() * 100, 2),
        ];
    }

    public function scoreAverage(): array
    {
        $companyReport = new CompanyReport($this->companyId);
        // Logic to calculate score averages
        return [
            'static_training' => $companyReport->trainingScoreAverage('static_training'),
            'conversational_training' => $companyReport->trainingScoreAverage('conversational_training'),
            'gamified_training' => $companyReport->trainingScoreAverage('gamified'),
            'ai_training' => $companyReport->trainingScoreAverage('ai_training'),
        ];
    }

    private function mostAssignedTrainings(): array
    {
        $trainingIds = TrainingModule::whereIn('company_id', ['default', $this->companyId])->pluck('id')->toArray();

        $mostAssignedTrainingIds = [];

        foreach ($trainingIds as $trainingId) {
            $assignedCount = TrainingAssignedUser::where('company_id', $this->companyId)
                ->where('training', $trainingId)
                ->count();
            if ($assignedCount > 2) {
                $trainingTitle = TrainingModule::find($trainingId)->name ?? 'Unknown';
                $mostAssignedTrainingIds[] = [
                    'training_name' => $trainingTitle,
                    'assigned_to_employees' => $assignedCount,
                ];
            }
        }

        return $mostAssignedTrainingIds;
    }

    private function mostCompletedTrainings(): array
    {
        $trainingIds = TrainingModule::whereIn('company_id', ['default', $this->companyId])->pluck('id')->toArray();

        $mostCompletedTrainingIds = [];

        foreach ($trainingIds as $trainingId) {
            $completedCount = TrainingAssignedUser::where('company_id', $this->companyId)
                ->where('training', $trainingId)
                ->where('completed', 1)
                ->count();
            if ($completedCount > 2) {
                $trainingTitle = TrainingModule::find($trainingId)->name ?? 'Unknown';
                $mostCompletedTrainingIds[] = [
                    'training_name' => $trainingTitle,
                    'employees_completed' => $completedCount,
                ];
            }
        }

        return $mostCompletedTrainingIds;
    }

    public function mostCompromisedEmployees(): array
    {
        $companyReport = new CompanyReport($this->companyId);
        $employees = $companyReport->employees();
        $compromiseData = [];
        foreach ($employees as $employee) {
            $employeeReport = new EmployeeReport($employee->user_email, $this->companyId);
            $compromiseCount = $employeeReport->compromised();
            if ($compromiseCount > 2) {
                $compromiseData[] = [
                    'employee_name' => $employee->user_name,
                    'compromised' => $compromiseCount,
                ];
            }
        }
        return $compromiseData;
    }

    public function mostClickedEmployees(): array
    {
        $companyReport = new CompanyReport($this->companyId);
        $employees = $companyReport->employees();
        $clickData = [];
        foreach ($employees as $employee) {
            $employeeReport = new EmployeeReport($employee->user_email, $this->companyId);
            $clickCount = $employeeReport->payloadClicked();
            if ($clickCount > 2) {
                $clickData[] = [
                    'employee_name' => $employee->user_name,
                    'clicked' => $clickCount,
                ];
            }
        }
        return $clickData;
    }

    private function mostIgnoredEmployees(): array
    {
        $companyReport = new CompanyReport($this->companyId);
        $employees = $companyReport->employees();
        $ignoreData = [];
        foreach ($employees as $employee) {
            $employeeReport = new EmployeeReport($employee->user_email, $this->companyId);
            $ignoreCount = $employeeReport->totalIgnored();
            if ($ignoreCount > 2) {
                $ignoreData[] = [
                    'employee_name' => $employee->user_name,
                    'ignored' => $ignoreCount,
                ];
            }
        }
        return $ignoreData;
    }

    private function empBadges()
    {
        // Build a map of badge id => ['name' => ..., 'description' => ...]
        $badges = Badge::select(['id', 'name', 'description'])->get()->keyBy('id');

        $trainings = TrainingAssignedUser::where('company_id', $this->companyId)
            ->whereNotNull('badge')
            ->select(['user_name', 'user_email', 'badge'])
            ->get();

        $scorms = ScormAssignedUser::where('company_id', $this->companyId)
            ->whereNotNull('badge')
            ->select(['user_name', 'user_email', 'badge'])
            ->get();

        $records = $trainings->concat($scorms);

        $uniqueUsers = $records
            ->groupBy('user_email') // group all records of same user
            ->map(function ($userRecords) use ($badges) {
                // Merge all badges arrays for the user (stored as JSON arrays in DB)
                $allBadgeIds = $userRecords->pluck('badge')
                    ->flatMap(fn($b) => json_decode($b, true) ?? []) // convert JSON -> array, guard null
                    ->unique()
                    ->values()
                    ->all();

                // badge names (backward-compatible)
                $badgeNames = collect($allBadgeIds)
                    ->map(fn($id) => $badges[$id]->name ?? 'Unknown Badge')
                    ->values()
                    ->all();

                // badge objects with description
                $badgeObjects = collect($allBadgeIds)
                    ->map(function ($id) use ($badges) {
                        $badge = $badges[$id] ?? null;
                        return [
                            'name' => $badge->name ?? 'Unknown Badge',
                            'description' => $badge->description ?? '',
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'user_email'  => $userRecords->first()->user_email,
                    'user_name'   => $userRecords->first()->user_name,
                    'badge_count' => count($badgeNames),
                    'badges'      => $badgeObjects,
                ];
            })
            ->values();

        return $uniqueUsers;
    }
}
