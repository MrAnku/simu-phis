<?php

namespace App\Services\Reports;

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
}
