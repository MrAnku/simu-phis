<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use App\Models\CampaignLive;
use App\Models\QuishingLiveCamp;
use App\Models\WaLiveCampaign;
use App\Models\TprmCampaign;
use App\Models\TprmCampaignLive;

use App\Models\{
    TrainingAssignedUser,
    CompanyLicense,
    Campaign,
    QuishingCamp,
    WaCampaign,
    CompanySettings,
    Users,
    UsersGroup
};

use App\Repositories\ReportRepository;

class BaseReportService
{
    protected $repository;

    public function __construct(ReportRepository $repository)
    {
        $this->repository = $repository;
    }

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

    /**
     * Generates the Awareness and Education Report.
     * Aggregates training statistics, license usage, and user-wise details.
     *
     * @return JsonResponse
     */
    public function fetchAwarenessEduReporting(): JsonResponse
    {
        try {

            $companyId = (string) (Auth::user()->company_id ?? '');
            if ($companyId === '') {
                return ResponseBuilder::awarenessEmpty();
            }

            // ===== COUNTS =====
            // Fetch all counts in a single query using conditional aggregation
            $trainingCounts = $this->repository->getTrainingCounts($companyId);

            $totalAssignedUsers = $trainingCounts->total ?? 0;
            $notStarted = $trainingCounts->not_started ?? 0;
            $inProgress = $trainingCounts->in_progress ?? 0;
            $completed = $trainingCounts->completed ?? 0;
            $educatedUsers = $trainingCounts->educated ?? 0;

            // ===== TRAINING METRICS =====
            $trainingStats = MetricCalculator::trainingProgressMetrics(
                $totalAssignedUsers,
                $notStarted,
                $inProgress,
                $completed
            );

            // ===== EDUCATED USERS =====
            // Already calculated above
            // $educatedUsers = ...

            // ===== LICENSE =====
            $license = $this->repository->getCompanyLicense($companyId);

            $totalEmployees = MetricCalculator::totalEmployees($license);

            $generalStats = MetricCalculator::generalStatistics(
                $totalAssignedUsers,
                $educatedUsers,
                $completed,
                $totalEmployees
            );

            // ===== DURATION =====
            $durationStats = MetricCalculator::durationStatistics($companyId);

            // ===== USER-WISE DETAILS =====
            $onboardingDetails = $this->userWiseTrainingDetails($companyId);

            return ResponseBuilder::awarenessSuccess(
                $trainingStats,
                $generalStats,
                $durationStats,
                $onboardingDetails
            );
        } catch (\Throwable $e) {
            return ResponseBuilder::error($e->getMessage());
        }
    }

    /**
     * User-wise training details.
     * Optimized with eager loading to prevent N+1 queries on TrainingModule relationship.
     *
     * @param string $companyId
     * @return array User details with their assigned trainings
     */
    private function userWiseTrainingDetails(string $companyId): array
    {
        // 1. Fetch all training assignments for this company, eager loading the 'trainingData' relationship
        //    to access name, passing_score etc. without extra queries.
        $allTrainings = $this->repository->getAllTrainingAssignments($companyId)
            ->groupBy('user_email');

        // 2. Fetch all users
        $users = $this->repository->getCompanyUsers($companyId);

        $result = [];

        foreach ($users as $user) {
            // Get trainings for this user from the collection in memory
            $trainings = $allTrainings->get($user->user_email, collect());

            $assignedTrainings = [];

            foreach ($trainings as $training) {
                // $trainingData is now eager loaded on $training
                $trainingData = $training->trainingData;

                $assignedTrainings[] = [
                    'training_name' => $trainingData->name ?? '',
                    'training_type' => $training->training_type ?? '',
                    'assigned_date' => $training->assigned_date,
                    'training_started' => $training->training_started ? 'Yes' : 'No',
                    'passing_score' => $trainingData->passing_score ?? 0,
                    'training_due_date' => $training->training_due_date,
                    'is_completed' => $training->completed ? 'Yes' : 'No',
                    'completion_date' => $training->completion_date,
                    'personal_best' => $training->personal_best ?? 0,
                    'certificate_id' => $training->certificate_id ?? '',
                    'last_reminder_date' => $training->last_reminder_date,
                    'status' => $training->completed ? 'Completed' : 'In Progress',
                ];
            }

            $result[] = [
                'user_name' => $user->user_name ?? '',
                'user_email' => $user->user_email ?? '',
                'total_assigned_trainings' => $trainings->count(),
                'assigned_trainings' => $assignedTrainings,
                'total_completed_trainings' => $trainings->where('completed', 1)->count(),
                'total_certificates' => $trainings->whereNotNull('certificate_id')->count(),
                'count_of_outstanding_training' => $trainings->where('personal_best', '>=', 70)->count(),
            ];
        }

        return $result;
    }
}
