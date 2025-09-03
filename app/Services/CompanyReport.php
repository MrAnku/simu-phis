<?php

namespace App\Services;

use App\Models\CampaignLive;
use App\Models\AiCallCampLive;
use App\Models\WaLiveCampaign;
use App\Models\QuishingLiveCamp;
use App\Models\TrainingAssignedUser;

class CompanyReport
{
    protected $companyId;

    public function __construct($companyId)
    {
        $this->companyId = $companyId;
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

    public function payloadClicked($email = null): int
    {
        $email =  CampaignLive::where('company_id', $this->companyId)
            ->where('payload_clicked', 1)
            ->count();
        $quishing = QuishingLiveCamp::where('company_id', $this->companyId)
            ->where('qr_scanned', '1')
            ->count();
        $whatsapp = WaLiveCampaign::where('company_id', $this->companyId)
            ->where('payload_clicked', 1)
            ->count();
        $ai = AiCallCampLive::where('company_id', $this->companyId)
            ->where('compromised', 1)
            ->count();

        return $email + $quishing + $whatsapp + $ai;
    }

    public function compromised($email = null): int
    {
        $email =  CampaignLive::where('company_id', $this->companyId)
            ->where('emp_compromised', 1)
            ->count();
        $quishing = QuishingLiveCamp::where('company_id', $this->companyId)
            ->where('compromised', '1')
            ->count();
        $whatsapp = WaLiveCampaign::where('company_id', $this->companyId)
            ->where('compromised', 1)
            ->count();
        $ai = AiCallCampLive::where('company_id', $this->companyId)
            ->where('compromised', 1)
            ->count();

        return $email + $quishing + $whatsapp + $ai;
    }

    public function totalSimulations($email = null): int
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

    public function totalTrainingAssigned(): int
    {
        return TrainingAssignedUser::where('company_id', $this->companyId)
            ->count();
    }

    public function completedTraining(): int
    {
        return TrainingAssignedUser::where('company_id', $this->companyId)
            ->where('completed', 1)
            ->count();
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
}