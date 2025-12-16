<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\Auth;
use App\Repositories\ReportRepository;
use App\Services\Reports\ResponseBuilder;
use App\Services\Reports\MetricCalculator;

/**
 * Service for generating Division/Department level reports.
 */
class DivisionReportService
{
    protected ReportRepository $repository;

    /**
     * @param ReportRepository $repository
     */
    public function __construct(ReportRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Fetch reporting data for division users.
     *
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function fetchDivisionUsersReporting()
    {
        try {

            $companyId = (string) (Auth::user()->company_id ?? '');

            if ($companyId === '') {
                return ResponseBuilder::divisionUsersEmpty();
            }

            $totalUsers = $this->repository->getCompanyUsersCount($companyId);
            if ($totalUsers === 0) {
                return ResponseBuilder::divisionUsersEmpty();
            }

            $divisionGroupDetails = [];

            $scoreRanges = [
                'Poor' => [0, 20],
                'Fair' => [21, 40],
                'Good' => [41, 60],
                'Very Good' => [61, 80],
                'Excellent' => [81, 100],
            ];

            $userGroups = $this->repository->getUserGroups($companyId);

            // Pre-fetch all simulation data in bulk
            $simulationData = $this->fetchBulkSimulationData($companyId, $userGroups);

            // Pre-fetch campaign counts per group
            $campaignCounts = $this->repository->getCampaignCountsByGroup($companyId);

            foreach ($userGroups as $group) {
                $users = json_decode($group->users, true) ?? [];
                if (empty($users)) continue;

                $groupId = $group->group_id;
                $groupTotalUsers = count($users);

                // Get pre-fetched data for this group
                $emailTotal = $simulationData['email'][$groupId]['total'] ?? 0;
                $emailComp = $simulationData['email'][$groupId]['compromised'] ?? 0;
                $quishingTotal = $simulationData['quishing'][$groupId]['total'] ?? 0;
                $quishingComp = $simulationData['quishing'][$groupId]['compromised'] ?? 0;
                $waTotal = $simulationData['whatsapp'][$groupId]['total'] ?? 0;
                $waComp = $simulationData['whatsapp'][$groupId]['compromised'] ?? 0;

                // ===== FINAL COUNTS =====
                $groupTotalSimulations = $emailTotal + $quishingTotal + $waTotal;
                $groupTotalCompromisedSimulations = $emailComp + $quishingComp + $waComp;

                $campaignsRan = $campaignCounts[$groupId] ?? 0;

                $metrics = MetricCalculator::divisionMetrics(
                    $group,
                    $groupTotalUsers,
                    $groupTotalSimulations,
                    $groupTotalCompromisedSimulations,
                    $campaignsRan,
                    $scoreRanges
                );

                $divisionGroupDetails[] = $metrics;
            }


            $campaignsLast7Days = $this->campaignsLast7Days($companyId);

            return ResponseBuilder::divisionUsersSuccess(
                count($userGroups),
                $divisionGroupDetails,
                $campaignsLast7Days
            );
        } catch (\Throwable $e) {
            return ResponseBuilder::error($e->getMessage());
        }
    }

    /**
     * Fetch all simulation data in bulk to avoid N+1 queries.
     *
     * Retrieves campaign live status (compromised/total) for Email, Quishing, and WhatsApp
     * and maps them by user ID and group ID for O(1) access.
     *
     * @param string $companyId
     * @param \Illuminate\Database\Eloquent\Collection $userGroups
     * @return array [
     *    'email' => [groupId => ['total' => int, 'compromised' => int]],
     *    'quishing' => [...],
     *    'whatsapp' => [...]
     * ]
     */
    private function fetchBulkSimulationData(string $companyId, $userGroups): array
    {
        $result = [
            'email' => [],
            'quishing' => [],
            'whatsapp' => [],
        ];

        // Build user-to-group mapping
        $userGroupMap = [];
        foreach ($userGroups as $group) {
            $users = json_decode($group->users, true) ?? [];
            foreach ($users as $userId) {
                if (!isset($userGroupMap[$userId])) {
                    $userGroupMap[$userId] = [];
                }
                $userGroupMap[$userId][] = $group->group_id;
            }
        }

        $allUserIds = array_keys($userGroupMap);
        if (empty($allUserIds)) {
            return $result;
        }

        // EMAIL - Fetch all at once
        $emailData = $this->repository->getEmailSimulationData($companyId, $allUserIds);

        foreach ($emailData as $record) {
            $groupIds = $userGroupMap[$record->user_id] ?? [];
            foreach ($groupIds as $groupId) {
                if (!isset($result['email'][$groupId])) {
                    $result['email'][$groupId] = ['total' => 0, 'compromised' => 0];
                }
                $result['email'][$groupId]['total']++;
                if ($record->emp_compromised == 1) {
                    $result['email'][$groupId]['compromised']++;
                }
            }
        }

        // QUISHING - Fetch all at once
        $quishingData = $this->repository->getQuishingSimulationData($companyId, $allUserIds);

        foreach ($quishingData as $record) {
            $groupIds = $userGroupMap[$record->user_id] ?? [];
            foreach ($groupIds as $groupId) {
                if (!isset($result['quishing'][$groupId])) {
                    $result['quishing'][$groupId] = ['total' => 0, 'compromised' => 0];
                }
                $result['quishing'][$groupId]['total']++;
                if ($record->compromised == '1') {
                    $result['quishing'][$groupId]['compromised']++;
                }
            }
        }

        // WHATSAPP - Fetch all at once
        $waData = $this->repository->getWhatsappSimulationData($companyId, $allUserIds);

        foreach ($waData as $record) {
            $groupIds = $userGroupMap[$record->user_id] ?? [];
            foreach ($groupIds as $groupId) {
                if (!isset($result['whatsapp'][$groupId])) {
                    $result['whatsapp'][$groupId] = ['total' => 0, 'compromised' => 0];
                }
                $result['whatsapp'][$groupId]['total']++;
                if ($record->compromised == 1) {
                    $result['whatsapp'][$groupId]['compromised']++;
                }
            }
        }

        return $result;
    }

    /**
     * Returns campaign statistics of last 7 days.
     * Optimized to use single query with joins/subqueries per campaign type.
     *
     * @param string $companyId
     * @return array List of campaign stats
     */
    private function campaignsLast7Days(string $companyId): array
    {
        if ($companyId === '') {
            return [];
        }

        $sevenDaysAgo = now()->subDays(7)->startOfDay();
        $campaignsLast7Days = [];

        // EMAIL - Use joins to reduce queries
        $emailCampaigns = $this->repository->getEmailCampaignsLast7Days($companyId, $sevenDaysAgo);

        foreach ($emailCampaigns as $camp) {
            $total = $camp->total_users ?? 0;
            $compromised = $camp->compromised_users ?? 0;

            $campaignsLast7Days[] = [
                'campaign_id' => $camp->campaign_id,
                'campaign_name' => $camp->campaign_name,
                'campaign_type' => 'email',
                'total_users' => $total,
                'compromised_users' => $compromised,
                'compromised_rate' => $total > 0 ? round(($compromised / $total) * 100, 2) : 0,
                'created_at' => $camp->created_at->format('Y-m-d H:i:s'),
            ];
        }

        // QUISHING - Use joins to reduce queries
        $quishingCampaigns = $this->repository->getQuishingCampaignsLast7Days($companyId, $sevenDaysAgo);

        foreach ($quishingCampaigns as $camp) {
            $total = $camp->total_users ?? 0;
            $compromised = $camp->compromised_users ?? 0;

            $rate = $total > 0 ? round(($compromised / $total) * 100, 2) : 0;

            $campaignsLast7Days[] = [
                'campaign_id' => $camp->campaign_id,
                'campaign_name' => $camp->campaign_name,
                'campaign_type' => 'quishing',
                'total_users' => $total,
                'compromised_users' => $compromised,
                'compromised_rate' => $rate,
                'created_at' => $camp->created_at->format('Y-m-d H:i:s'),
            ];
        }

        // WHATSAPP - Use joins to reduce queries
        $waCampaigns = $this->repository->getWhatsappCampaignsLast7Days($companyId, $sevenDaysAgo);

        foreach ($waCampaigns as $camp) {
            $total = $camp->total_users ?? 0;
            $compromised = $camp->compromised_users ?? 0;

            $rate = $total > 0 ? round(($compromised / $total) * 100, 2) : 0;

            $campaignsLast7Days[] = [
                'campaign_id' => $camp->campaign_id,
                'campaign_name' => $camp->campaign_name,
                'campaign_type' => 'whatsapp',
                'total_users' => $total,
                'compromised_users' => $compromised,
                'compromised_rate' => $rate,
                'created_at' => $camp->created_at->format('Y-m-d H:i:s'),
            ];
        }

        // TPRM - Use joins to reduce queries
        $tprmCampaigns = $this->repository->getTprmCampaignsLast7Days($companyId, $sevenDaysAgo);

        foreach ($tprmCampaigns as $camp) {
            $total = $camp->total_users ?? 0;
            $compromised = $camp->compromised_users ?? 0;

            $rate = $total > 0 ? round(($compromised / $total) * 100, 2) : 0;

            $campaignsLast7Days[] = [
                'campaign_id' => $camp->campaign_id,
                'campaign_name' => $camp->campaign_name,
                'campaign_type' => 'tprm',
                'total_users' => $total,
                'compromised_users' => $compromised,
                'compromised_rate' => $rate,
                'created_at' => $camp->created_at->format('Y-m-d H:i:s'),
            ];
        }

        return $campaignsLast7Days;
    }
}
