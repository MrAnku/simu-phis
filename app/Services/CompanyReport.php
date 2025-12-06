<?php

namespace App\Services;

use App\Models\Users;
use App\Models\Campaign;
use App\Models\WaCampaign;
use App\Models\CampaignLive;
use App\Models\QuishingCamp;
use App\Models\TprmCampaign;
use App\Models\AiCallCampaign;
use App\Models\AiCallCampLive;
use App\Models\AssignedPolicy;
use App\Models\PolicyCampaignLive;
use App\Models\WaLiveCampaign;
use App\Models\QuishingLiveCamp;
use App\Models\ScormAssignedUser;
use App\Models\TrainingAssignedUser;
use App\Models\UsersGroup;
use Illuminate\Support\Facades\Cache;

class CompanyReport
{
    protected $companyId;

    public function __construct($companyId)
    {
        $this->companyId = $companyId;
    }

    public function employees(): object
    {
        return Users::where('company_id', $this->companyId)
            ->get()
            ->unique('user_email')
            ->values();
    }

    public function userGroups(): object
    {
        return UsersGroup::where('company_id', $this->companyId)
            ->get();
    }

    // Risk Assessment Methods
    public function calculateOverallRiskScore(): float
    {
        $payloadClicked = $this->payloadClicked();
        $compromised = $this->compromised();
        $totalSimulations = $this->totalSimulations();

        $totalCompromised = $payloadClicked + $compromised;

        if ($totalSimulations > 0) {
            $rawScore = 100 - (($totalCompromised / $totalSimulations) * 100);
            $clamped = max(0, min(100, $rawScore));
            return round($clamped, 2); // ensures values like 2.1099999 become 2.11
        }

        return 100.00;
    }

    public function emailSent($through = null, $forMonth = null): int
    {
        $query = CampaignLive::where('company_id', $this->companyId)
            ->where('sent', 1);
        $quishingQuery = QuishingLiveCamp::where('company_id', $this->companyId)
            ->where('sent', '1');

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
            $quishingQuery->whereMonth('created_at', $forMonth);
        }

        return $query->count() + $quishingQuery->count();
    }

    public function trainingScoreAverage($trainingType): float
    {
        $assignedUsers = TrainingAssignedUser::where('company_id', $this->companyId)
            ->where('training_type', $trainingType)
            ->avg('personal_best');
        return $assignedUsers ? round($assignedUsers, 2) : 0.00;
    }

    public function emailViewed($forMonth = null): int
    {
        $emailQuery = CampaignLive::where('company_id', $this->companyId)
            ->where('mail_open', 1);

        $quishingQuery = QuishingLiveCamp::where('company_id', $this->companyId)
            ->where('mail_open', '1');

        if ($forMonth) {
            $emailQuery->whereMonth('created_at', $forMonth);
            $quishingQuery->whereMonth('created_at', $forMonth);
        }

        return $emailQuery->count() + $quishingQuery->count();
    }

    public function payloadClicked($forMonth = null): int
    {
        $emailQuery =  CampaignLive::where('company_id', $this->companyId)
            ->where('payload_clicked', 1);
        $quishingQuery = QuishingLiveCamp::where('company_id', $this->companyId)
            ->where('qr_scanned', '1');
        $whatsappQuery = WaLiveCampaign::where('company_id', $this->companyId)
            ->where('payload_clicked', 1);

        if ($forMonth) {
            $emailQuery->whereMonth('created_at', $forMonth);
            $quishingQuery->whereMonth('created_at', $forMonth);
            $whatsappQuery->whereMonth('created_at', $forMonth);
        }

        return $emailQuery->count() + $quishingQuery->count() + $whatsappQuery->count();
    }

    public function emailIgnored($forMonth = null): int
    {
        $emailQuery = CampaignLive::where('company_id', $this->companyId)
            ->where('mail_open', 0)
            ->where('payload_clicked', 0)
            ->where('emp_compromised', 0);
        $quishingQuery = QuishingLiveCamp::where('company_id', $this->companyId)
            ->where('mail_open', '0')
            ->where('qr_scanned', '0')
            ->where('compromised', '0');

        if ($forMonth) {
            $emailQuery->whereMonth('created_at', $forMonth);
            $quishingQuery->whereMonth('created_at', $forMonth);
        }

        return $emailQuery->count() + $quishingQuery->count();
    }

    public function compromised($forMonth = null): int
    {
        $emailQuery =  CampaignLive::where('company_id', $this->companyId)
            ->where('emp_compromised', 1);
        $quishingQuery = QuishingLiveCamp::where('company_id', $this->companyId)
            ->where('compromised', '1');
        $whatsappQuery = WaLiveCampaign::where('company_id', $this->companyId)
            ->where('compromised', 1);
        $aiQuery = AiCallCampLive::where('company_id', $this->companyId)
            ->where('compromised', 1);

        if ($forMonth) {
            $emailQuery->whereMonth('created_at', $forMonth);
            $quishingQuery->whereMonth('created_at', $forMonth);
            $whatsappQuery->whereMonth('created_at', $forMonth);
            $aiQuery->whereMonth('created_at', $forMonth);
        }

        return $emailQuery->count() + $quishingQuery->count() + $whatsappQuery->count() + $aiQuery->count();
    }

    public function totalSimulations(): int
    {
        $email =  CampaignLive::where('company_id', $this->companyId)
            ->count();
        $quishing = QuishingLiveCamp::where('company_id', $this->companyId)
            ->count();
        $whatsapp = WaLiveCampaign::where('company_id', $this->companyId)
            ->count();
        $ai = AiCallCampLive::where('company_id', $this->companyId)
            ->count();

        return $email + $quishing + $whatsapp + $ai;
    }

    public function totalTrainingAssigned($forMonth = null): int
    {
        $query = TrainingAssignedUser::where('company_id', $this->companyId);
        $scormQuery = ScormAssignedUser::where('company_id', $this->companyId);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
            $scormQuery->whereMonth('created_at', $forMonth);
        }

        return $query->count() + $scormQuery->count();
    }

    public function totalTrainingStarted($forMonth = null): int
    {
        $query = TrainingAssignedUser::where('company_id', $this->companyId)->where('training_started', 1);
        $scormQuery = ScormAssignedUser::where('company_id', $this->companyId)->where('scorm_started', 1);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
            $scormQuery->whereMonth('created_at', $forMonth);
        }

        return $query->count() + $scormQuery->count();
    }

    public function completedTraining($forMonth = null): int
    {
        $query = TrainingAssignedUser::where('company_id', $this->companyId)
            ->where('completed', 1);
        $scormQuery = ScormAssignedUser::where('company_id', $this->companyId)
            ->where('completed', 1);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
            $scormQuery->whereMonth('created_at', $forMonth);
        }

        return $query->count() + $scormQuery->count();
    }

    public function pendingTraining($forMonth = null): int
    {
        $query = TrainingAssignedUser::where('company_id', $this->companyId)
            ->where('completed', 0);
        $scormQuery = ScormAssignedUser::where('company_id', $this->companyId)
            ->where('completed', 0);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
            $scormQuery->whereMonth('created_at', $forMonth);
        }

        return $query->count() + $scormQuery->count();
    }

    public function totalBadgesAssigned($forMonth = null): int
    {
        $query = TrainingAssignedUser::where('company_id', $this->companyId)
            ->where('badge', '!=', null);
        $scormQuery = ScormAssignedUser::where('company_id', $this->companyId)
            ->where('badge', '!=', null);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
            $scormQuery->whereMonth('created_at', $forMonth);
        }
        $badges = 0;

        $training = $query->get();
        $scorm = $scormQuery->get();
        foreach ($training as $item) {
            // $badges = json_decode($item->badge, true);
            // $badges += is_array($badges) ? count($badges) : 0;
            $badges++;
        }
        foreach ($scorm as $item) {
            // $badges = json_decode($item->badge, true);
            // $badges += is_array($badges) ? count($badges) : 0;
            $badges++;
        }

        return $badges;
    }

    public function totalGameAssigned($forMonth = null): int
    {
        $query = TrainingAssignedUser::where('company_id', $this->companyId)
            ->where('training_type', 'games');

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function totalGameStarted($forMonth = null): int
    {
        $query = TrainingAssignedUser::where('company_id', $this->companyId)
            ->where('training_type', 'games')
            ->where('training_started', 1);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function completedGame($forMonth = null): int
    {
        $query = TrainingAssignedUser::where('company_id', $this->companyId)
            ->where('training_type', 'games')
            ->where('completed', 1);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function inProgressTraining($forMonth = null): int
    {
        $query = TrainingAssignedUser::where('company_id', $this->companyId)
            ->where('training_started', 1)
            ->where('completed', 0);
        $scormQuery = ScormAssignedUser::where('company_id', $this->companyId)
            ->where('scorm_started', 1)
            ->where('completed', 0);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
            $scormQuery->whereMonth('created_at', $forMonth);
        }

        return $query->count() + $scormQuery->count();
    }

    public function certifiedUsers($forMonth = null): int
    {
        $query = TrainingAssignedUser::where('company_id', $this->companyId)
            ->where('certificate_path', '!=', null);
        $scormQuery = ScormAssignedUser::where('company_id', $this->companyId)
            ->where('certificate_path', '!=', null);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
            $scormQuery->whereMonth('created_at', $forMonth);
        }

        return $query->count() + $scormQuery->count();
    }

    public function overdueTraining($forMonth = null): int
    {
        $query = TrainingAssignedUser::where('company_id', $this->companyId)
            ->where('training_due_date', '<', now())
            ->where('completed', 0);
        $scormQuery = ScormAssignedUser::where('company_id', $this->companyId)
            ->where('scorm_due_date', '<', now())
            ->where('completed', 0);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
            $scormQuery->whereMonth('created_at', $forMonth);
        }

        return $query->count() + $scormQuery->count();
    }

    public function trainingCompletionRate(): float
    {
        $totalTraining = $this->totalTrainingAssigned();
        $completedTraining = $this->completedTraining();

        if ($totalTraining > 0) {
            return round(($completedTraining / $totalTraining) * 100, 2);
        }

        return 0.00;
    }

    public function trainingPendingRate(): float
    {
        $totalTraining = $this->totalTrainingAssigned();
        $pendingTraining = $this->pendingTraining();

        if ($totalTraining > 0) {
            return round(($pendingTraining / $totalTraining) * 100, 2);
        }

        return 0.00;
    }

    /**
     * Get training completion trend for the last 6 months
     * Returns an array with month names, assigned counts and completion counts
     * 
     * @return array
     */
    public function getTrainingCompletionTrend(): array
    {
        $trend = [];
        $now = now();

        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $monthNumber = $month->month;
            $monthName = $month->format('M Y'); // e.g., "Jan 2025"

            $trainingAssigned = TrainingAssignedUser::where('company_id', $this->companyId)
                ->whereMonth('created_at', $monthNumber)
                ->whereYear('created_at', $month->year)
                ->count();

            $scormAssigned = ScormAssignedUser::where('company_id', $this->companyId)
                ->whereMonth('created_at', $monthNumber)
                ->whereYear('created_at', $month->year)
                ->count();

            $trainingCompleted = TrainingAssignedUser::where('company_id', $this->companyId)
                ->where('completed', 1)
                ->whereMonth('updated_at', $monthNumber)
                ->whereYear('updated_at', $month->year)
                ->count();

            $scormCompleted = ScormAssignedUser::where('company_id', $this->companyId)
                ->where('completed', 1)
                ->whereMonth('updated_at', $monthNumber)
                ->whereYear('updated_at', $month->year)
                ->count();

            $totalAssigned = $trainingAssigned + $scormAssigned;
            $totalCompleted = $trainingCompleted + $scormCompleted;

            $trend[] = [
                'month' => $monthName,
                'assigned' => $totalAssigned,
                'completed' => $totalCompleted,
                'completion_rate' => $totalAssigned > 0 ? round(($totalCompleted / $totalAssigned) * 100, 2) : 0
            ];
        }

        return $trend;
    }

    public function compromiseRate(): float
    {
        $totalUsers = $this->totalSimulations();
        $compromisedUsers = $this->compromised();

        return $totalUsers > 0 ? round(($compromisedUsers / $totalUsers) * 100, 2) : 0;
    }
    public function clickRate(): float
    {
        $totalUsers = $this->totalSimulations();
        $clickedUsers = $this->payloadClicked();

        return $totalUsers > 0 ? round(($clickedUsers / $totalUsers) * 100, 2) : 0;
    }

    /**
     * Notify on click-rate threshold transitions (50% and 100%).
     * Uses cache to remember last state so notifications fire only on transitions.
     */
    public function notifyClickRateThreshold()
    {
        // Calculate current click rate and determine previous state (per-company cache key)
        $currentRate = (float) $this->clickRate();
        $cacheKey = "company:{$this->companyId}:click_rate_state";
        $previousState = Cache::get($cacheKey, 'below_50');

        // Determine the new state based on the current rate
        if ($currentRate >= 100) {
            $newState = '100_reached';
        } elseif ($currentRate >= 50) {
            $newState = '50_reached';
        } else {
            $newState = 'below_50';
        }

        // Nothing to do if state didn't change
        if ($newState === $previousState) {
            return;
        }

        // Handle transitions and send simple platform-level notifications
        switch ($newState) {
            case '100_reached':
                sendNotification('Alert: Platform click rate has reached 100%.', $this->companyId);
                break;

            case '50_reached':
                if ($previousState !== '50_reached') {
                    sendNotification('Alert: Platform click rate has reached 50%.', $this->companyId);
                }
                break;

            case 'below_50':
                // reset only; no notification
                break;
        }

        // Persist the new state so we only notify on transitions
        Cache::put($cacheKey, $newState);
    }

    public function emailReported($forMonth = null): int
    {
        $emailQuery = CampaignLive::where('company_id', $this->companyId)
            ->where('email_reported', 1);
        $quishingQuery = QuishingLiveCamp::where('company_id', $this->companyId)
            ->where('email_reported', '1');

        if ($forMonth) {
            $emailQuery->whereMonth('created_at', $forMonth);
            $quishingQuery->whereMonth('created_at', $forMonth);
        }

        return $emailQuery->count() + $quishingQuery->count();
    }


    public function emailCampaigns($forMonth = null): int
    {
        $query = Campaign::where('company_id', $this->companyId);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }
    public function quishingCampaigns($forMonth = null): int
    {
        $query = QuishingCamp::where('company_id', $this->companyId);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }
    public function aiCampaigns($forMonth = null): int
    {
        $query = AiCallCampaign::where('company_id', $this->companyId);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function tprmCampaigns($forMonth = null): int
    {
        $query = TprmCampaign::where('company_id', $this->companyId);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function whatsappCampaigns($forMonth = null): int
    {
        $query = WaCampaign::where('company_id', $this->companyId);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function getDomainWiseEmailCamp($domain)
    {
        // Base query — common filters
        $baseQuery = CampaignLive::where('company_id', $this->companyId)
            ->where('user_email', 'LIKE', '%' . $domain);

        // Use clone to reuse base query safely
        $totalSimulations = (clone $baseQuery)
            ->count();

        $totalCompromised = (clone $baseQuery)
            ->where('emp_compromised', 1)
            ->count();

        $totalPayloadClicked = (clone $baseQuery)
            ->where('payload_clicked', 1)
            ->count();

        $emailReported = (clone $baseQuery)
            ->where('email_reported', 1)
            ->count();

        $ignored = (clone $baseQuery)
            ->where('payload_clicked', 0)
            ->count();

        return [
            'total_simulations' => $totalSimulations,
            'compromised' => $totalCompromised,
            'payload_clicked' => $totalPayloadClicked,
            'email_reported' => $emailReported,
            'ignored' => $ignored,
        ];
    }

    public function getDomainWiseQuishCamp($domain)
    {
        // Base query — common filters
        $baseQuery = QuishingLiveCamp::where('company_id', $this->companyId)
            ->where('user_email', 'LIKE', '%' . $domain);

        // Use clone to reuse base query safely
        $totalSimulations = (clone $baseQuery)
           ->count();

        $totalCompromised = (clone $baseQuery)
            ->where('compromised', '1')
            ->count();

        $totalQrScanned = (clone $baseQuery)
            ->where('qr_scanned', '1')
            ->count();

        $emailReported = (clone $baseQuery)
            ->where('email_reported', '1')
            ->count();

        $ignored = (clone $baseQuery)
            ->where('qr_scanned', '0')
            ->count();

        return [
            'total_simulations' => $totalSimulations,
            'compromised' => $totalCompromised,
            'qr_scanned' => $totalQrScanned,
            'email_reported' => $emailReported,
            'ignored' => $ignored,
        ];
    }

    public function getDomainWiseAiCamp($domain)
    {
        // Base query — common filters
        $baseQuery = AiCallCampLive::where('company_id', $this->companyId)
            ->where('user_email', 'LIKE', '%' . $domain);

        // Use clone to reuse base query safely
        $totalSimulations = (clone $baseQuery)
          ->count();

        $totalCompromised = (clone $baseQuery)
            ->where('compromised', 1)
            ->count();

        $totalCallSent = (clone $baseQuery)
            ->where('calls_sent', 1)
            ->count();

        $callReported = (clone $baseQuery)
            ->whereNotNull('call_report')
            ->count();

        $completedCalls = (clone $baseQuery)
            ->whereNotNull('call_end_response')
            ->count();

        return [
            'total_simulations' => $totalSimulations,
            'compromised' => $totalCompromised,
            'call_sent' => $totalCallSent,
            'call_reported' => $callReported,
            'completed_calls' => $completedCalls,
        ];
    }

    public function getDomainWiseWaCamp($domain)
    {
        // Base query — common filters
        $baseQuery = WaLiveCampaign::where('company_id', $this->companyId)
            ->where('user_email', 'LIKE', '%' . $domain);

        // Use clone to reuse base query safely
        $totalSimulations = (clone $baseQuery)
           ->count();

        $totalCompromised = (clone $baseQuery)
            ->where('compromised', 1)
            ->count();

        $totalPayloadClicked = (clone $baseQuery)
            ->where('payload_clicked', 1)
            ->count();

        $ignored = (clone $baseQuery)
            ->where('payload_clicked', 0)
            ->count();

        return [
            'total_simulations' => $totalSimulations,
            'compromised' => $totalCompromised,
            'payload_clicked' => $totalPayloadClicked,
            'ignored' => $ignored,
        ];
    }

    public function getDomainWiseTrainings($domain)
    {
        $trainingBaseQuery = TrainingAssignedUser::where('company_id', $this->companyId)
            ->where('user_email', 'LIKE', '%' . $domain);

        $scormBaseQuery = ScormAssignedUser::where('company_id', $this->companyId)
            ->where('user_email', 'LIKE', '%' . $domain);

        $totalAssigned = (clone $trainingBaseQuery)->count() + (clone $scormBaseQuery)->count();

        $inProgressTrainings = (clone $trainingBaseQuery)
            ->where('training_started', 1)
            ->where('completed', 0)
            ->count() + (clone $scormBaseQuery)
            ->where('scorm_started', 1)
            ->where('completed', 0)
            ->count();

        $notStartedTrainings = (clone $trainingBaseQuery)
            ->where('training_started', 0)
            ->count() + (clone $scormBaseQuery)
            ->where('scorm_started', 0)
            ->count();

        $completedTrainings = (clone $trainingBaseQuery)
            ->where('completed', 1)
            ->count() + (clone $scormBaseQuery)
            ->where('completed', 1)
            ->count();

        $overDueTrainings = (clone $trainingBaseQuery)
            ->where('training_due_date', '<', now())
            ->where('completed', 0)
            ->count() + (clone $scormBaseQuery)
            ->where('scorm_due_date', '<', now())
            ->where('completed', 0)
            ->count();

        $certifiedTrainings = (clone $trainingBaseQuery)
            ->whereNotNull('certificate_id')
            ->count() + (clone $scormBaseQuery)
            ->whereNotNull('certificate_id')
            ->count();

        return [
            'total_assigned' => $totalAssigned,
            'in_progress_trainings' => $inProgressTrainings,
            'not_started_trainings' => $notStartedTrainings,
            'completed_trainings' => $completedTrainings,
            'overdue_trainings' => $overDueTrainings,
            'certified_users' => $certifiedTrainings,
        ];
    }

    public function getDomainWisePolicies($domain)
    {
        $totalSimulations = PolicyCampaignLive::where('company_id', $this->companyId)
            ->where('user_email', 'LIKE', '%' . $domain)
            ->count();

        $assignedPolicies = AssignedPolicy::where('company_id', $this->companyId)
            ->where('user_email', 'LIKE', '%' . $domain)
            ->count();

        $acceptedPolicies = AssignedPolicy::where('company_id', $this->companyId)
            ->where('user_email', 'LIKE', '%' . $domain)
            ->where('accepted', 1)
            ->count();

        return [
            'total_simulations' => $totalSimulations,
            'assigned_policies' => $assignedPolicies,
            'accepted_policies' => $acceptedPolicies,
        ];
    }

    public function calDomainRiskScore($domain): float
    {
        $payloadClicked = $this->getDomainWiseEmailCamp($domain)['payload_clicked'] + $this->getDomainWiseQuishCamp($domain)['qr_scanned'] + $this->getDomainWiseWaCamp($domain)['payload_clicked'] + $this->getDomainWiseAiCamp($domain)['compromised'];

        $compromised = $this->getDomainWiseEmailCamp($domain)['compromised'] + $this->getDomainWiseQuishCamp($domain)['compromised'] + $this->getDomainWiseWaCamp($domain)['compromised'] + $this->getDomainWiseAiCamp($domain)['compromised'];
        
        $totalSimulations = $this->getDomainWiseEmailCamp($domain)['total_simulations'] + $this->getDomainWiseQuishCamp($domain)['total_simulations'] + $this->getDomainWiseWaCamp($domain)['total_simulations'] + $this->getDomainWiseAiCamp($domain)['total_simulations'];

        $totalCompromised = $payloadClicked + $compromised;

        if ($totalSimulations > 0) {
            $rawScore = 100 - (($totalCompromised / $totalSimulations) * 100);
            $clamped = max(0, min(100, $rawScore));
            return round($clamped, 2); // ensures values like 2.1099999 become 2.11
        }

        return 100.00;
    }
    
    public function totalPoliciesAssigned($forMonth = null): int
    {
        $query = AssignedPolicy::where('company_id', $this->companyId);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function acceptedPolicies($forMonth = null): int
    {
        $query = AssignedPolicy::where('company_id', $this->companyId)->where('accepted', 1);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function acceptedPoliciesRate(): float
    {
        $totalPolicies = $this->totalPoliciesAssigned();
        $acceptedPolicies = $this->acceptedPolicies();

        if ($totalPolicies > 0) {
            return round(($acceptedPolicies / $totalPolicies) * 100, 2);
        }

        return 0.00;
    }

    public function notAcceptedPolicies($forMonth = null): int
    {
        $query = AssignedPolicy::where('company_id', $this->companyId)->where('accepted', 0);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function notAcceptedPoliciesRate(): float
    {
        $totalPolicies = $this->totalPoliciesAssigned();
        $notAcceptedPolicies = $this->notAcceptedPolicies();

        if ($totalPolicies > 0) {
            return round(($notAcceptedPolicies / $totalPolicies) * 100, 2);
        }

        return 0.00;
    }
}
