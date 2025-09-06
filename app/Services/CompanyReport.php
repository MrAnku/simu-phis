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
use App\Models\WaLiveCampaign;
use App\Models\QuishingLiveCamp;
use App\Models\ScormAssignedUser;
use App\Models\TrainingAssignedUser;
use App\Models\UsersGroup;

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
            ->where('training_due_date', '<', now());
        $scormQuery = ScormAssignedUser::where('company_id', $this->companyId)
            ->where('scorm_due_date', '<', now());

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
}
