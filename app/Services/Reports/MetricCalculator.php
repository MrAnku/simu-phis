<?php

namespace App\Services\Reports;

use App\Models\{

    CompanyLicense,
    Campaign,
    QuishingCamp,
    WaCampaign,
    CompanySettings,
    QuishingLiveCamp,
    WaLiveCampaign,
    AiCallCampLive,
    TrainingAssignedUser,
    CampaignLive
};

class MetricCalculator
{
    /**
     * Calculate division level metrics
     *
     * @param object $group
     * @param int $groupTotalUsers
     * @param int $groupTotalSimulations
     * @param int $groupTotalCompromisedSimulations
     * @param int $campaignsRan
     * @param array $scoreRanges
     *
     * @return array
     */
    public static function divisionMetrics(
        object $group,
        int $groupTotalUsers,
        int $groupTotalSimulations,
        int $groupTotalCompromisedSimulations,
        int $campaignsRan,
        array $scoreRanges
    ): array {

        // ===== compromised rate =====
        $compromisedRate = $groupTotalSimulations > 0
            ? round(($groupTotalCompromisedSimulations / $groupTotalSimulations) * 100, 2)
            : 0;

        // ===== performance score =====
        $performanceScore = $groupTotalSimulations > 0
            ? 100 - $compromisedRate
            : 100;

        // ===== risk score =====
        $groupRiskScore = $groupTotalUsers > 0
            ? round(
                $groupTotalSimulations > 0
                    ? 100 - (($groupTotalCompromisedSimulations / $groupTotalSimulations) * 100)
                    : 100,
                2
            )
            : 100;

        // ===== risk level =====
        $groupRiskLevel = 'unknown';
        foreach ($scoreRanges as $label => $range) {
            [$min, $max] = $range;
            if ($groupRiskScore >= $min && $groupRiskScore <= $max) {
                $groupRiskLevel = $label;
                break;
            }
        }

        // ===== FINAL SAFE RESPONSE (NO NULLS) =====
        return [
            'group_name'        => $group->group_name ?? '',
            'group_id'          => $group->group_id ?? '',
            'total_users'       => $groupTotalUsers,
            'total_campaigns'   => $campaignsRan,
            'total_simulations' => $groupTotalSimulations,
            'total_compromised' => $groupTotalCompromisedSimulations,
            'compromised_rate'  => $compromisedRate,
            'performance_score' => $performanceScore,
            'risk_score'        => $groupRiskScore,
            'risk_level'        => $groupRiskLevel,
        ];
    }

    /**
     * Training progress metrics
     */
    public static function trainingProgressMetrics(
        int $total,
        int $notStarted,
        int $inProgress,
        int $completed
    ): array {
        return [
            'total_assigned_users' => $total,
            'not_started_training' => $notStarted,
            'not_started_training_rate' => $total > 0 ? round($notStarted / $total * 100) : 0,
            'progress_training' => $inProgress,
            'progress_training_rate' => $total > 0 ? round($inProgress / $total * 100) : 0,
            'completed_training' => $completed,
            'completed_training_rate' => $total > 0 ? round($completed / $total * 100) : 0,
        ];
    }

    /**
     * General statistics
     */
    public static function generalStatistics(
        int $totalAssigned,
        int $educated,
        int $completed,
        int $totalEmployees
    ): array {
        return [
            'educated_user_percent' => $totalAssigned > 0
                ? round($educated / $totalAssigned * 100, 2)
                : 0,

            'roles_responsibility_percent' => $totalEmployees > 0
                ? round($completed / $totalEmployees * 100)
                : 0,

            'certified_users_percent' => $totalAssigned > 0
                ? round($completed / $totalAssigned * 100)
                : 0,
        ];
    }

    /**
     * Total employees from license
     */
    public static function totalEmployees(?CompanyLicense $license): int
    {
        return $license
            ? (int) $license->used_employees
            + (int) $license->used_tprm_employees
            + (int) $license->used_blue_collar_employees
            : 0;
    }

    /**
     * Duration statistics
     */
    public static function durationStatistics(string $companyId): array
    {
        $email = Campaign::where('company_id', $companyId)->pluck('days_until_due');
        $quish = QuishingCamp::where('company_id', $companyId)->pluck('days_until_due');
        $wa    = WaCampaign::where('company_id', $companyId)->pluck('days_until_due');

        $merged = $email->merge($quish)->merge($wa);

        $avg = $merged->count() > 0 ? round($merged->avg(), 2) : 0;

        return [
            'avg_education_duration' => round($avg),
            'avg_overall_duration' => round($avg),
            'training_assign_reminder_freq_days' =>
            (int) (CompanySettings::where('company_id', $companyId)
                ->value('training_assign_remind_freq_days') ?? 0),
        ];
    }

    public static function initialTrainingOverallStats(): array
    {
        return [
            'total_assigned_trainings' => 0,
            'total_completed_trainings' => 0,
            'total_in_progress_trainings' => 0,
            'total_not_started_trainings' => 0,
            'total_overdue_trainings' => 0,
            'total_certified_users' => 0,
            'users_scored_above_40' => 0,
            'users_scored_above_60' => 0,
            'users_scored_above_80' => 0,
            'overall_completion_rate' => 0,
            'overall_progress_rate' => 0,
            'static_training_users' => 0,
            'scorm_training_users' => 0,
            'gamified_training_users' => 0,
            'ai_training_users' => 0,
            'game_training_users' => 0,
        ];
    }

    public static function trainingAssignmentCounts($q): array
    {
        return [
            'total' => $q->count(),
            'completed' => (clone $q)->where('completed', 1)->count(),
            'in_progress' => (clone $q)->where('training_started', 1)->where('completed', 0)->count(),
            'not_started' => (clone $q)->where('training_started', 0)->count(),
            'overdue' => (clone $q)->where('training_due_date', '<', now())->where('completed', 0)->count(),
            'certified' => (clone $q)->whereNotNull('certificate_id')->count(),
            'score_40' => (clone $q)->where('personal_best', '>=', 40)->count(),
            'score_60' => (clone $q)->where('personal_best', '>=', 60)->count(),
            'score_80' => (clone $q)->where('personal_best', '>=', 80)->count(),
        ];
    }

    public static function completionRates(int $total, int $completed, int $progress): array
    {
        return [
            'completion' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'progress'   => $total > 0 ? round((($progress + $completed) / $total) * 100, 2) : 0,
        ];
    }

    public static function mergeOverallStats(array &$overall, array $c): void
    {
        $overall['total_assigned_trainings'] += $c['total'];
        $overall['total_completed_trainings'] += $c['completed'];
        $overall['total_in_progress_trainings'] += $c['in_progress'];
        $overall['total_not_started_trainings'] += $c['not_started'];
        $overall['total_overdue_trainings'] += $c['overdue'];
        $overall['total_certified_users'] += $c['certified'];
        $overall['users_scored_above_40'] += $c['score_40'];
        $overall['users_scored_above_60'] += $c['score_60'];
        $overall['users_scored_above_80'] += $c['score_80'];
    }

    public static function finalOverallRates(array &$o): void
    {
        $o['overall_completion_rate'] =
            $o['total_assigned_trainings'] > 0
            ? round(($o['total_completed_trainings'] / $o['total_assigned_trainings']) * 100, 2)
            : 0;

        $o['overall_progress_rate'] =
            $o['total_assigned_trainings'] > 0
            ? round((($o['total_completed_trainings'] + $o['total_in_progress_trainings']) / $o['total_assigned_trainings']) * 100, 2)
            : 0;
    }

    public static function simulationCounts(string $companyId, int $moduleId): array
    {
        return [
            'email_simulations' =>
            CampaignLive::where('training_module', $moduleId)->where('company_id', $companyId)->count(),
            'quishing_simulations' =>
            QuishingLiveCamp::where('training_module', $moduleId)->where('company_id', $companyId)->count(),
            'ai_call_simulations' =>
            AiCallCampLive::where('training_module', $moduleId)->where('company_id', $companyId)->count(),
            'whatsapp_simulations' =>
            WaLiveCampaign::where('training_module', $moduleId)->where('company_id', $companyId)->count(),
        ];
    }

    public static function processGames($games, string $companyId, array &$overall): array
    {
        $result = [];

        foreach ($games as $game) {
            $assigned = TrainingAssignedUser::where('training', $game->id)
                ->where('training_type', 'games')
                ->where('company_id', $companyId);

            $total = $assigned->count();
            $completed = (clone $assigned)->where('completed', 1)->count();
            $inProgress = (clone $assigned)->where('training_started', 1)->where('completed', 0)->count();

            $result[] = [
                'game_id' => $game->id,
                'name' => $game->name,
                'description' => $game->description ?? '',
                'total_assigned_games' => $total,
                'completed_games' => $completed,
                'in_progress_games' => $inProgress,
                'not_started_games' => (clone $assigned)->where('training_started', 0)->count(),
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
                'average_game_time_seconds' => round((clone $assigned)->avg('game_time') ?? 0, 2),
            ];

            $overall['game_training_users'] += $total;
        }

        return $result;
    }
    public static function calculateTrainingModuleStats($module, string $companyId): array
    {
        $assignedTrainings = TrainingAssignedUser::where('training', $module->id)
            ->where('company_id', $companyId);

        $total = $assignedTrainings->count();
        $completed = (clone $assignedTrainings)->where('completed', 1)->count();
        $inProgress = (clone $assignedTrainings)->where('training_started', 1)->where('completed', 0)->count();
        $notStarted = (clone $assignedTrainings)->where('training_started', 0)->count();
        $overdue = (clone $assignedTrainings)
            ->where('training_due_date', '<', now())
            ->where('completed', 0)
            ->count();
        $certified = (clone $assignedTrainings)->whereNotNull('certificate_id')->count();
        $score40 = (clone $assignedTrainings)->where('personal_best', '>=', 40)->count();
        $score60 = (clone $assignedTrainings)->where('personal_best', '>=', 60)->count();
        $score80 = (clone $assignedTrainings)->where('personal_best', '>=', 80)->count();

        $completionRate = $total > 0 ? round(($completed / $total) * 100, 2) : 0;
        $progressRate = $total > 0 ? round((($inProgress + $completed) / $total) * 100, 2) : 0;
        $avgScore = round((clone $assignedTrainings)->avg('personal_best') ?? 0, 2);

        $trainingType = 'static_training';
        if ($module->training_type == 'gamified') {
            $trainingType = 'gamified';
        }

        $simCounts = self::simulationCounts($companyId, $module->id);

        return [
            'data' => [
                'training_id' => $module->id,
                'name' => $module->name,
                'training_type' => $trainingType,
                'description' => $module->description ?? '',
                'passing_score' => $module->passing_score ?? 0,
                'total_assigned_trainings' => $total,
                'completed_trainings' => $completed,
                'in_progress_trainings' => $inProgress,
                'not_started_trainings' => $notStarted,
                'overdue_trainings' => $overdue,
                'certified_users' => $certified,
                'users_scored_40_plus' => $score40,
                'users_scored_60_plus' => $score60,
                'users_scored_80_plus' => $score80,
                'completion_rate' => $completionRate,
                'progress_rate' => $progressRate,
                'average_score' => $avgScore,
                'simulation_counts' => $simCounts,
            ],
            'stats' => [
                'total' => $total,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'not_started' => $notStarted,
                'overdue' => $overdue,
                'certified' => $certified,
                'score_40' => $score40,
                'score_60' => $score60,
                'score_80' => $score80,
                'type' => $trainingType
            ]
        ];
    }

    public static function fetchRecentActivities(string $companyId)
    {
        return TrainingAssignedUser::where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays(30))
            ->with('trainingData')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'user_email' => $activity->user_email,
                    'training_name' => $activity->trainingData->name ?? 'N/A',
                    'training_type' => $activity->training_type,
                    'assigned_date' => $activity->assigned_date,
                    'completion_status' => $activity->completed ? 'Completed' : ($activity->training_started ? 'In Progress' : 'Not Started'),
                    'personal_best' => $activity->personal_best,
                    'certificate_id' => $activity->certificate_id
                ];
            });
    }

    public static function calculateSummary(array $trainings, array $gameTrainings): array
    {
        return [
            'total_training_modules' => count($trainings),
            'total_game_modules' => count($gameTrainings),
            'most_popular_training' => collect($trainings)->sortByDesc('total_assigned_trainings')->first()['name'] ?? 'N/A',
            'highest_completion_rate' => collect($trainings)->max('completion_rate') ?? 0,
            'lowest_completion_rate' => collect($trainings)->min('completion_rate') ?? 0
        ];
    }
}
