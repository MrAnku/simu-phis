<?php

namespace App\Services\Reports;

use App\Models\{

    CompanyLicense,
    Campaign,
    QuishingCamp,
    WaCampaign,
    CompanySettings,
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
}
