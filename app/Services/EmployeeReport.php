<?php

namespace App\Services;

use App\Models\Users;
use App\Models\UsersGroup;
use App\Models\CampaignLive;
use App\Models\AiCallCampLive;
use App\Models\TrainingModule;
use App\Models\WaLiveCampaign;
use App\Models\QuishingLiveCamp;
use App\Models\ScormAssignedUser;
use App\Models\ScormTraining;
use App\Models\TrainingAssignedUser;

class EmployeeReport
{
    private string $email;
    private string $companyId;

    public function __construct(string $email, string $companyId)
    {
        $this->email = $email;
        $this->companyId = $companyId;
    }

    public function generateReport(): array
    {
        return [
            // Risk Assessment Metrics
            'overall_risk_score' => $this->calculateOverallRiskScore(),
            'user_risk_distribution' => $this->calculateUserRiskDistribution(),

            // Campaign Performance Metrics
            'click_through_rates' => $this->calculateClickThroughRates(),
            'email_report_rates' => $this->calculateEmailReportRate(),
            'compromise_rates' => $this->calculateCompromiseRates(),

            // Training Analytics
            'training_completion_rates' => $this->calculateTrainingCompletionRates(),
            // 'knowledge_retention_scores' => $this->calculateKnowledgeRetention(),
            // 'training_effectiveness' => $this->calculateTrainingEffectiveness(),
            'certification_progress' => $this->calculateCertificationProgress(),

        ];
    }

    // Risk Assessment Methods
    public function calculateOverallRiskScore(): float
    {
        $payloadClicked = $this->payloadClicked();
        $emailReported = $this->emailReported();
        $emailViewed = $this->emailViewed();
        $compromised = $this->compromised();

        $overallRiskScore = ($payloadClicked * 0.4) + ($emailReported * 0.4) + ($emailViewed * 0.2) + ($compromised * 0.2);
        return round($overallRiskScore, 2);
    }

    private function calculateUserRiskDistribution(): array
    {
        $payloadClicked = $this->payloadClicked();
        $emailReported = $this->emailReported();
        $emailViewed = $this->emailViewed();
        $compromised = $this->compromised();

        // Calculate risk level based on metrics
        $riskScore = $this->calculateOverallRiskScore();

        // Determine risk category
        if ($riskScore >= 5) {
            $riskLevel = 'high';
        } elseif ($riskScore >= 2) {
            $riskLevel = 'medium';
        } else {
            $riskLevel = 'low';
        }

        return [
            'risk_score' => round($riskScore, 2),
            'risk_level' => $riskLevel,
            'metrics' => [
                'payload_clicked' => $payloadClicked,
                'email_reported' => $emailReported,
                'email_viewed' => $emailViewed,
                'compromised' => $compromised
            ],
            'risk_factors' => [
                'high_payload_clicks' => $payloadClicked >= 3,
                'low_reporting_rate' => $emailReported < 2,
                'multiple_compromises' => $compromised >= 2,
                'high_engagement' => $emailViewed >= 5
            ]
        ];
    }



    private function calculateClickThroughRates(): array
    {
        $ctrByDepartment = [];
        $usersGroups = UsersGroup::where('company_id', $this->companyId)->get();
        foreach ($usersGroups as $group) {
            $ctrByDepartment['group_name'] = $group->group_name;
            $ctrByDepartment['total_clicks'] = $this->clicksByUsersGroup($group->group_id);
            $ctrByDepartment['click_rate'] = $this->clickRateByUsersGroup($group->group_id);
        }
        return [
            'overall_ctr' => $this->clickRate(),
            'by_campaign_type' => [
                'email' => $this->clickRate('email'),
                'quishing' => $this->clickRate('quishing'),
                'ai' => $this->clickRate('ai'),
                'whatsapp' => $this->clickRate('whatsapp')
            ],
            'by_department' => $ctrByDepartment,
            'trend' => $this->checkCtrTrend(),
            'highest_risk_type' => $this->checkHighestRiskType()
        ];
    }

    private function calculateEmailReportRate(): array
    {
        return [
            'overall_reporting_rate' => 58.7,
            'by_threat_type' => [
                'email' => 68.9, // Most familiar to users
                'quishing' => 42.3, // Lower awareness
                'ai' => 38.1, // Hardest to detect
                'whatsapp' => 55.2
            ],
            'by_department' => [
                'IT' => ['email' => 82.1, 'quishing' => 65.3, 'ai' => 58.7, 'whatsapp' => 71.2],
                'Finance' => ['email' => 72.5, 'quishing' => 38.9, 'ai' => 32.1, 'whatsapp' => 52.8],
                'HR' => ['email' => 65.2, 'quishing' => 41.7, 'ai' => 35.9, 'whatsapp' => 54.3],
                'Sales' => ['email' => 61.8, 'quishing' => 39.2, 'ai' => 33.7, 'whatsapp' => 48.9]
            ],
            'average_response_time' => [
                'email' => '3.8 minutes',
                'quishing' => '8.2 minutes',
                'ai' => '12.5 minutes',
                'whatsapp' => '6.1 minutes'
            ],
            'improvement_trend' => 14.3
        ];
    }

    private function calculateCompromiseRates(): array
    {
        return [
            'overall_compromise_rate' => $this->compromiseRate(),
            'compromised' => $this->compromised()
        ];
    }

    // Training Analytics Methods
    private function calculateTrainingCompletionRates(): array
    {
        return [
            'overall_completion' => $this->trainingCompletionRate(),
            'trainings' => $this->assignedTrainingNames(),
            'assigned_scorm' => $this->assignedScormNames(),
            'training_completed_on_time' => [
                "training" => $this->onTimeTrainingCompleted(),
                "scorm" => $this->onTimeScormCompleted(),
            ],
            'average_completion_time' => [
                "training" => $this->averageTrainingCompletionTime(),
                "scorm" => $this->averageScormCompletionTime(),
            ],
        ];
    }

    private function calculateCertificationProgress(): array
    {
        return [
            'total_certifications' => $this->totalCertificates(),
            'certified_trainings' => $this->certifiedTrainings(),
            'certified_scorm' => $this->certifiedScorm()
        ];
    }

  
    public function payloadClicked($email = null): int
    {
        $email =  CampaignLive::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->where('payload_clicked', 1)
            ->count();
        $quishing = QuishingLiveCamp::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->where('qr_scanned', '1')
            ->count();
        $whatsapp = WaLiveCampaign::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->where('payload_clicked', 1)
            ->count();
        $ai = AiCallCampLive::where('employee_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->where('compromised', 1)
            ->count();

        return $email + $quishing + $whatsapp + $ai;
    }

    public function compromised($email = null): int
    {
        $email =  CampaignLive::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->where('emp_compromised', 1)
            ->count();
        $quishing = QuishingLiveCamp::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->where('compromised', '1')
            ->count();
        $whatsapp = WaLiveCampaign::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->where('compromised', 1)
            ->count();
        $ai = AiCallCampLive::where('employee_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->where('compromised', 1)
            ->count();

        return $email + $quishing + $whatsapp + $ai;
    }

    public function compromiseRate(): float
    {
        $totalUsers = $this->usersCampaigns();
        $compromisedUsers = $this->compromised();

        return $totalUsers > 0 ? round(($compromisedUsers / $totalUsers) * 100, 2) : 0;
    }

    public function emailReported(): int
    {
        $email =  CampaignLive::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('email_reported', 1)
            ->count();
        $quishing = QuishingLiveCamp::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('email_reported', '1')
            ->count();

        return $email + $quishing;
    }

    public function emailViewed($email = null): int
    {
        $email =  CampaignLive::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->where('mail_open', 1)
            ->count();
        $quishing = QuishingLiveCamp::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->where('mail_open', '1')
            ->count();

        return $email + $quishing;
    }

    public function usersCampaigns($email = null): int
    {
        $email =  CampaignLive::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->count();
        $quishing = QuishingLiveCamp::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->count();
        $whatsapp = WaLiveCampaign::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->count();
        $ai = AiCallCampLive::where('employee_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->count();

        return $email + $quishing + $whatsapp + $ai;
    }

    public function clickRate($campaign = null): int
    {
        if ($campaign) {
            // Campaign-wise click rate calculation
            switch (strtolower($campaign)) {
                case 'email':
                    $totalEmails = CampaignLive::where('user_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->count();
                    $clickedEmails = CampaignLive::where('user_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->where('payload_clicked', 1)
                        ->count();
                    return $totalEmails > 0 ? round(($clickedEmails / $totalEmails) * 100) : 0;

                case 'quishing':
                    $totalQuishing = QuishingLiveCamp::where('user_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->count();
                    $scannedQuishing = QuishingLiveCamp::where('user_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->where('qr_scanned', '1')
                        ->count();
                    return $totalQuishing > 0 ? round(($scannedQuishing / $totalQuishing) * 100) : 0;

                case 'whatsapp':
                    $totalWhatsapp = WaLiveCampaign::where('user_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->count();
                    $clickedWhatsapp = WaLiveCampaign::where('user_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->where('payload_clicked', 1)
                        ->count();
                    return $totalWhatsapp > 0 ? round(($clickedWhatsapp / $totalWhatsapp) * 100) : 0;

                case 'ai':
                    $totalAi = AiCallCampLive::where('employee_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->count();
                    $compromisedAi = AiCallCampLive::where('employee_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->where('compromised', 1)
                        ->count();
                    return $totalAi > 0 ? round(($compromisedAi / $totalAi) * 100) : 0;

                default:
                    return 0;
            }
        } else {
            // Overall click rate calculation
            $totalCampaigns = CampaignLive::where('user_email', $this->email)
                ->where('company_id', $this->companyId)
                ->count()
                + QuishingLiveCamp::where('user_email', $this->email)
                ->where('company_id', $this->companyId)
                ->count()
                + WaLiveCampaign::where('user_email', $this->email)
                ->where('company_id', $this->companyId)
                ->count()
                + AiCallCampLive::where('employee_email', $this->email)
                ->where('company_id', $this->companyId)
                ->count();

            $totalClicks = $this->payloadClicked() + $this->compromised();

            return $totalCampaigns > 0 ? round(($totalClicks / $totalCampaigns) * 100) : 0;
        }
    }

    public function previousMonthClickRate($campaign = null): int
    {
        $lastMonth = now()->subMonth();
        $startOfLastMonth = $lastMonth->startOfMonth();
        $endOfLastMonth = $lastMonth->endOfMonth();

        if ($campaign) {
            // Campaign-wise click rate calculation for last month
            switch (strtolower($campaign)) {
                case 'email':
                    $totalEmails = CampaignLive::where('user_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                        ->count();
                    $clickedEmails = CampaignLive::where('user_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->where('payload_clicked', 1)
                        ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                        ->count();
                    return $totalEmails > 0 ? round(($clickedEmails / $totalEmails) * 100) : 0;

                case 'quishing':
                    $totalQuishing = QuishingLiveCamp::where('user_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                        ->count();
                    $scannedQuishing = QuishingLiveCamp::where('user_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->where('qr_scanned', '1')
                        ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                        ->count();
                    return $totalQuishing > 0 ? round(($scannedQuishing / $totalQuishing) * 100) : 0;

                case 'whatsapp':
                    $totalWhatsapp = WaLiveCampaign::where('user_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                        ->count();
                    $clickedWhatsapp = WaLiveCampaign::where('user_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->where('payload_clicked', 1)
                        ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                        ->count();
                    return $totalWhatsapp > 0 ? round(($clickedWhatsapp / $totalWhatsapp) * 100) : 0;

                case 'ai':
                    $totalAi = AiCallCampLive::where('employee_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                        ->count();
                    $compromisedAi = AiCallCampLive::where('employee_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->where('compromised', 1)
                        ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                        ->count();
                    return $totalAi > 0 ? round(($compromisedAi / $totalAi) * 100) : 0;

                default:
                    return 0;
            }
        } else {
            // Overall click rate calculation for last month
            $totalCampaigns = CampaignLive::where('user_email', $this->email)
                ->where('company_id', $this->companyId)
                ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                ->count()
                + QuishingLiveCamp::where('user_email', $this->email)
                ->where('company_id', $this->companyId)
                ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                ->count()
                + WaLiveCampaign::where('user_email', $this->email)
                ->where('company_id', $this->companyId)
                ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                ->count()
                + AiCallCampLive::where('employee_email', $this->email)
                ->where('company_id', $this->companyId)
                ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                ->count();

            $totalClicks = CampaignLive::where('user_email', $this->email)
                ->where('company_id', $this->companyId)
                ->where('payload_clicked', 1)
                ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                ->count()
                + QuishingLiveCamp::where('user_email', $this->email)
                ->where('company_id', $this->companyId)
                ->where('qr_scanned', '1')
                ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                ->count()
                + WaLiveCampaign::where('user_email', $this->email)
                ->where('company_id', $this->companyId)
                ->where('payload_clicked', 1)
                ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                ->count()
                + AiCallCampLive::where('employee_email', $this->email)
                ->where('company_id', $this->companyId)
                ->where('compromised', 1)
                ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                ->count();

            return $totalCampaigns > 0 ? round(($totalClicks / $totalCampaigns) * 100) : 0;
        }
    }

    public function currentMonthClickRate($campaign = null): int
    {
        $currentMonth = now();
        $startOfCurrentMonth = $currentMonth->startOfMonth();
        $endOfCurrentMonth = $currentMonth->endOfMonth();

        if ($campaign) {
            // Campaign-wise click rate calculation for current month
            switch (strtolower($campaign)) {
                case 'email':
                    $totalEmails = CampaignLive::where('user_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->whereBetween('created_at', [$startOfCurrentMonth, $endOfCurrentMonth])
                        ->count();
                    $clickedEmails = CampaignLive::where('user_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->where('payload_clicked', 1)
                        ->whereBetween('created_at', [$startOfCurrentMonth, $endOfCurrentMonth])
                        ->count();
                    return $totalEmails > 0 ? round(($clickedEmails / $totalEmails) * 100) : 0;

                case 'quishing':
                    $totalQuishing = QuishingLiveCamp::where('user_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->whereBetween('created_at', [$startOfCurrentMonth, $endOfCurrentMonth])
                        ->count();
                    $scannedQuishing = QuishingLiveCamp::where('user_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->where('qr_scanned', '1')
                        ->whereBetween('created_at', [$startOfCurrentMonth, $endOfCurrentMonth])
                        ->count();
                    return $totalQuishing > 0 ? round(($scannedQuishing / $totalQuishing) * 100) : 0;

                case 'whatsapp':
                    $totalWhatsapp = WaLiveCampaign::where('user_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->whereBetween('created_at', [$startOfCurrentMonth, $endOfCurrentMonth])
                        ->count();
                    $clickedWhatsapp = WaLiveCampaign::where('user_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->where('payload_clicked', 1)
                        ->whereBetween('created_at', [$startOfCurrentMonth, $endOfCurrentMonth])
                        ->count();
                    return $totalWhatsapp > 0 ? round(($clickedWhatsapp / $totalWhatsapp) * 100) : 0;

                case 'ai':
                    $totalAi = AiCallCampLive::where('employee_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->whereBetween('created_at', [$startOfCurrentMonth, $endOfCurrentMonth])
                        ->count();
                    $compromisedAi = AiCallCampLive::where('employee_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->where('compromised', 1)
                        ->whereBetween('created_at', [$startOfCurrentMonth, $endOfCurrentMonth])
                        ->count();
                    return $totalAi > 0 ? round(($compromisedAi / $totalAi) * 100) : 0;

                default:
                    return 0;
            }
        } else {
            // Overall click rate calculation for current month
            $totalCampaigns = CampaignLive::where('user_email', $this->email)
                ->where('company_id', $this->companyId)
                ->whereBetween('created_at', [$startOfCurrentMonth, $endOfCurrentMonth])
                ->count()
                + QuishingLiveCamp::where('user_email', $this->email)
                ->where('company_id', $this->companyId)
                ->whereBetween('created_at', [$startOfCurrentMonth, $endOfCurrentMonth])
                ->count()
                + WaLiveCampaign::where('user_email', $this->email)
                ->where('company_id', $this->companyId)
                ->whereBetween('created_at', [$startOfCurrentMonth, $endOfCurrentMonth])
                ->count()
                + AiCallCampLive::where('employee_email', $this->email)
                ->where('company_id', $this->companyId)
                ->whereBetween('created_at', [$startOfCurrentMonth, $endOfCurrentMonth])
                ->count();

            $totalClicks = CampaignLive::where('user_email', $this->email)
                ->where('company_id', $this->companyId)
                ->where('payload_clicked', 1)
                ->whereBetween('created_at', [$startOfCurrentMonth, $endOfCurrentMonth])
                ->count()
                + QuishingLiveCamp::where('user_email', $this->email)
                ->where('company_id', $this->companyId)
                ->where('qr_scanned', '1')
                ->whereBetween('created_at', [$startOfCurrentMonth, $endOfCurrentMonth])
                ->count()
                + WaLiveCampaign::where('user_email', $this->email)
                ->where('company_id', $this->companyId)
                ->where('payload_clicked', 1)
                ->whereBetween('created_at', [$startOfCurrentMonth, $endOfCurrentMonth])
                ->count()
                + AiCallCampLive::where('employee_email', $this->email)
                ->where('company_id', $this->companyId)
                ->where('compromised', 1)
                ->whereBetween('created_at', [$startOfCurrentMonth, $endOfCurrentMonth])
                ->count();

            return $totalCampaigns > 0 ? round(($totalClicks / $totalCampaigns) * 100) : 0;
        }
    }

    public function checkHighestRiskType(): string
    {
        $clickRates = [
            'Email Phishing' => $this->clickRate('email'),
            'Quishing' => $this->clickRate('quishing'),
            'AI Vishing' => $this->clickRate('ai'),
            'WhatsApp Simulation' => $this->clickRate('whatsapp')
        ];

        arsort($clickRates);
        return key($clickRates);
    }

    public function checkCtrTrend(): string
    {
        $currentCtr = $this->currentMonthClickRate();
        $previousCtr = $this->previousMonthClickRate();

        if ($currentCtr > $previousCtr) {
            return 'increasing';
        } elseif ($currentCtr < $previousCtr) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }

    public function clickRateByUsersGroup($groupId = null): int
    {
        if ($groupId) {
            // Calculate click rate for specific group
            $usersGroup = UsersGroup::where('company_id', $this->companyId)
                ->where('group_id', $groupId)
                ->first();
        } else {
            $usersGroup = UsersGroup::where('company_id', $this->companyId)
                ->first();
        }

        if (!$usersGroup) {
            return 0;
        }

        // Get users in this group
        $usersIds = json_decode($usersGroup->users, true) ?? [];

        if (empty($usersIds)) {
            return 0;
        }

        $totalCampaigns = 0;
        $totalClicks = 0;

        $userEmails = Users::whereIn('id', $usersIds)->where('company_id', $this->companyId)->pluck('user_email')->toArray();

        $usersCampaign = 0;
        $usersClicks = 0;
        foreach ($userEmails as $email) {
            $usersCampaign += $this->usersCampaigns($email);
            $usersClicks += $this->payloadClicked($email);
        }

        $totalCampaigns += $usersCampaign;
        $totalClicks += $usersClicks;

        $clickRate = $totalCampaigns > 0 ? round(($totalClicks / $totalCampaigns) * 100) : 0;


        return $clickRate;
    }

    public function clicksByUsersGroup($groupId = null): int
    {
        if ($groupId) {
            // Calculate click rate for specific group
            $usersGroup = UsersGroup::where('company_id', $this->companyId)
                ->where('group_id', $groupId)
                ->first();
        } else {
            $usersGroup = UsersGroup::where('company_id', $this->companyId)
                ->first();
        }

        if (!$usersGroup) {
            return 0;
        }

        // Get users in this group
        $usersIds = json_decode($usersGroup->users, true) ?? [];

        if (empty($usersIds)) {
            return 0;
        }

        $totalClicks = 0;

        $userEmails = Users::whereIn('id', $usersIds)->where('company_id', $this->companyId)->pluck('user_email')->toArray();

        foreach ($userEmails as $email) {
            $totalClicks += $this->payloadClicked($email);
        }

        return $totalClicks;
    }

    public function assignedTrainings($email = null): int
    {
        $assignedTrainings = TrainingAssignedUser::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->count();

        return $assignedTrainings;
    }

    public function trainingCompleted($email = null): int
    {
        $completedTrainings = TrainingAssignedUser::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->where('completed', 1)
            ->count() + ScormAssignedUser::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->where('completed', 1)
            ->count();

        return $completedTrainings;
    }

    public function trainingCompletionRate(): float
    {
        $assignedTrainings = $this->assignedTrainings();
        $completedTraining = $this->trainingCompleted();

        return $assignedTrainings > 0 ? round(($completedTraining / $assignedTrainings) * 100, 2) : 0;
    }

    public function assignedTrainingNames($email = null): array
    {
        $trainingIds = TrainingAssignedUser::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->pluck('training')
            ->toArray();
        $trainingNames = TrainingModule::whereIn('id', $trainingIds)->pluck('name')->toArray();

        

        return $trainingNames;
    }

    public function assignedScormNames($email = null): array
    {
        $scormIds = ScormAssignedUser::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->pluck('scorm')
            ->toArray();
        $scormNames = ScormTraining::whereIn('id', $scormIds)->pluck('name')->toArray();

        return $scormNames;
    }

    public function onTimeTrainingCompleted(): int
    { 
        $onTimeTrainings = 0;
        $completedTrainings = TrainingAssignedUser::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('completed', 1)
            ->get();
        if ($completedTrainings->isNotEmpty()) {
            foreach ($completedTrainings as $training) {
                if ($training->completion_date && $training->training_due_date) {
                    $completionDate = \Carbon\Carbon::parse($training->completion_date);
                    $dueDate = \Carbon\Carbon::parse($training->training_due_date);
                    if ($completionDate->lessThanOrEqualTo($dueDate)) {
                        $onTimeTrainings++;
                    }
                }
            }
        }

        return $onTimeTrainings;
    }

    public function averageTrainingCompletionTime(): string
    {
        $totalDays = 0;
        $completedCount = 0;

        $completedTrainings = TrainingAssignedUser::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('completed', 1)
            ->get();

        foreach ($completedTrainings as $training) {
            if ($training->assigned_date && $training->completion_date) {
                $assignedDate = \Carbon\Carbon::parse($training->assigned_date);
                $completionDate = \Carbon\Carbon::parse($training->completion_date);
                $totalDays += $assignedDate->diffInDays($completionDate);
                $completedCount++;
            }
        }

        if ($completedCount === 0) {
            return 'N/A';
        }

        $averageDays = round($totalDays / $completedCount);
        return $averageDays . ' days';
    }

    public function onTimeScormCompleted(): int
    {
        $onTimeScorms = 0;
        $completedScorms = ScormAssignedUser::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('completed', 1)
            ->get();
        if ($completedScorms->isNotEmpty()) {
            foreach ($completedScorms as $scorm) {
                if ($scorm->completion_date && $scorm->scorm_due_date) {
                    $completionDate = \Carbon\Carbon::parse($scorm->completion_date);
                    $dueDate = \Carbon\Carbon::parse($scorm->scorm_due_date);
                    if ($completionDate->lessThanOrEqualTo($dueDate)) {
                        $onTimeScorms++;
                    }
                }
            }
        }

        return $onTimeScorms;
    }

    public function averageScormCompletionTime(): string
    {
        $totalDays = 0;
        $completedCount = 0;

        $completedScorms = ScormAssignedUser::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('completed', 1)
            ->get();

        foreach ($completedScorms as $scorm) {
            if ($scorm->assigned_date && $scorm->completion_date) {
                $assignedDate = \Carbon\Carbon::parse($scorm->assigned_date);
                $completionDate = \Carbon\Carbon::parse($scorm->completion_date);
                $totalDays += $assignedDate->diffInDays($completionDate);
                $completedCount++;
            }
        }

        if ($completedCount === 0) {
            return 'N/A';
        }

        $averageDays = round($totalDays / $completedCount);
        return $averageDays . ' days';
    }

    public function totalCertificates(): int
    {
        $totalCertificates = TrainingAssignedUser::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('certificate_id', '!=', null)
            ->count() + ScormAssignedUser::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('certificate_id', '!=', null)
            ->count();

        return $totalCertificates;
    }

    public function certifiedTrainings(): int
    {
        $trainingIds = TrainingAssignedUser::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('certificate_id', '!=', null)
            ->pluck('training')
            ->toArray();
        $trainingNames = TrainingModule::whereIn('id', $trainingIds)->pluck('name')->toArray();

        return $trainingNames;
    }

    public function certifiedScorm(): int
    {
        $scormIds = ScormAssignedUser::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('certificate_id', '!=', null)
            ->pluck('scorm')
            ->toArray();
        $scormNames = ScormTraining::whereIn('id', $scormIds)->pluck('name')->toArray();

        return $scormNames;
    }

}
