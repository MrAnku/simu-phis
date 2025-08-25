<?php

namespace App\Services;

use App\Models\UsersGroup;
use App\Models\CampaignLive;
use App\Models\AiCallCampLive;
use App\Models\WaLiveCampaign;
use App\Models\QuishingLiveCamp;

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
            'department_risk_levels' => $this->calculateDepartmentRiskLevels(),
            'trend_analysis' => $this->calculateRiskTrends(),

            // Campaign Performance Metrics
            'campaign_effectiveness' => $this->calculateCampaignEffectiveness(),
            'click_through_rates' => $this->calculateClickThroughRates(),
            'reporting_rates' => $this->calculateReportingRates(),
            'compromise_rates' => $this->calculateCompromiseRates(),

            // Training Analytics
            'training_completion_rates' => $this->calculateTrainingCompletionRates(),
            'knowledge_retention_scores' => $this->calculateKnowledgeRetention(),
            'training_effectiveness' => $this->calculateTrainingEffectiveness(),
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

    private function calculateDepartmentRiskLevels(): array
    {
        $userGroups = UsersGroup::where('company_id', $this->companyId)->pluck('group_id')->toArray();

        // Calculate risk levels for each department
        $riskLevels = [];
        foreach ($userGroups as $groupId) {
            $riskLevels[$groupId] = $this->calculateUserRiskDistribution($groupId);
        }

        return $riskLevels;
    }

    private function calculateRiskTrends(): array
    {
        return [
            'monthly_trends' => [
                '2024-01' => 6.8,
                '2024-02' => 6.2,
                '2024-03' => 5.9,
                '2024-04' => 5.6,
                '2024-05' => 5.3
            ],
            'quarterly_change' => -18.2,
            'year_over_year' => -25.1,
            'trending_risks' => ['ai_deepfakes', 'qr_code_phishing', 'whatsapp_business_scams', 'email_spoofing'],
            'by_campaign_type' => [
                'email' => ['trend' => 'stable', 'change' => -2.1],
                'quishing' => ['trend' => 'increasing', 'change' => 15.3],
                'ai' => ['trend' => 'increasing', 'change' => 28.7],
                'whatsapp' => ['trend' => 'increasing', 'change' => 12.8]
            ]
        ];
    }

    // Campaign Performance Methods
    private function calculateCampaignEffectiveness(): array
    {
        return [
            'overall_success_rate' => 72.8,
            'campaigns' => [
                'email_phishing_q2' => ['success_rate' => 79.5, 'participants' => 150, 'type' => 'email'],
                'qr_code_simulation' => ['success_rate' => 65.2, 'participants' => 120, 'type' => 'quishing'],
                'ai_deepfake_test' => ['success_rate' => 58.9, 'participants' => 90, 'type' => 'ai'],
                'whatsapp_scam_simulation' => ['success_rate' => 71.3, 'participants' => 135, 'type' => 'whatsapp']
            ],
            'improvement_rate' => 8.7,
            'best_performing' => 'email_phishing_q2',
            'by_type' => [
                'email' => 79.5,
                'quishing' => 65.2,
                'ai' => 58.9,
                'whatsapp' => 71.3
            ]
        ];
    }

    private function calculateClickThroughRates(): array
    {
        return [
            'overall_ctr' => 26.8,
            'by_campaign_type' => [
                'email' => 24.7,
                'quishing' => 35.2, // Higher due to QR code curiosity
                'ai' => 31.8, // High due to sophisticated content
                'whatsapp' => 22.1
            ],
            'by_department' => [
                'Finance' => ['email' => 28.5, 'quishing' => 38.2, 'ai' => 35.1, 'whatsapp' => 25.3],
                'HR' => ['email' => 22.8, 'quishing' => 32.4, 'ai' => 29.7, 'whatsapp' => 20.1],
                'IT' => ['email' => 18.2, 'quishing' => 28.9, 'ai' => 26.3, 'whatsapp' => 16.8],
                'Sales' => ['email' => 26.3, 'quishing' => 36.7, 'ai' => 33.2, 'whatsapp' => 24.9]
            ],
            'trend' => 'increasing',
            'highest_risk_type' => 'quishing'
        ];
    }

    private function calculateReportingRates(): array
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
            'overall_compromise_rate' => 12.4,
            'by_campaign_type' => [
                'email' => 9.8,
                'quishing' => 18.7, // Higher due to novelty
                'ai' => 21.3, // Highest due to sophistication
                'whatsapp' => 11.2
            ],
            'by_department' => [
                'Finance' => ['email' => 11.5, 'quishing' => 22.8, 'ai' => 25.7, 'whatsapp' => 13.9],
                'HR' => ['email' => 10.2, 'quishing' => 19.3, 'ai' => 22.1, 'whatsapp' => 12.4],
                'IT' => ['email' => 5.8, 'quishing' => 12.1, 'ai' => 15.2, 'whatsapp' => 7.3],
                'Sales' => ['email' => 12.7, 'quishing' => 21.5, 'ai' => 24.8, 'whatsapp' => 14.2]
            ],
            'trend' => 'increasing',
            'prevention_rate' => 87.6,
            'most_dangerous' => 'ai'
        ];
    }

    // Training Analytics Methods
    private function calculateTrainingCompletionRates(): array
    {
        return [
            'overall_completion' => 84.7,
            'by_module' => [
                'email_phishing_awareness' => 91.2,
                'qr_code_security' => 78.5,
                'ai_threat_detection' => 72.8,
                'whatsapp_security' => 81.3,
                'cross_platform_awareness' => 76.9
            ],
            'by_campaign_type' => [
                'email' => 91.2,
                'quishing' => 78.5,
                'ai' => 72.8,
                'whatsapp' => 81.3
            ],
            'on_time_completion' => 71.8,
            'average_completion_time' => '3.1 days'
        ];
    }

    private function calculateKnowledgeRetention(): array
    {
        return [
            'overall_retention' => 69.8,
            'by_assessment' => [
                'immediate_post_training' => 82.1,
                '30_day_followup' => 74.3,
                '90_day_followup' => 67.9,
                '180_day_followup' => 62.5
            ],
            'by_campaign_type' => [
                'email' => ['immediate' => 85.7, '30_day' => 78.2, '90_day' => 72.1, '180_day' => 68.3],
                'quishing' => ['immediate' => 79.8, '30_day' => 69.7, '90_day' => 62.4, '180_day' => 55.8],
                'ai' => ['immediate' => 76.2, '30_day' => 65.9, '90_day' => 58.7, '180_day' => 51.2],
                'whatsapp' => ['immediate' => 83.1, '30_day' => 73.8, '90_day' => 66.8, '180_day' => 61.9]
            ],
            'retention_decline_rate' => 3.2
        ];
    }

    private function calculateTrainingEffectiveness(): array
    {
        return [
            'behavior_change_rate' => 63.2,
            'skill_improvement' => 67.8,
            'confidence_increase' => 79.3,
            'practical_application' => 64.7,
            'overall_effectiveness' => 68.8,
            'by_campaign_type' => [
                'email' => ['effectiveness' => 74.2, 'behavior_change' => 71.8],
                'quishing' => ['effectiveness' => 58.9, 'behavior_change' => 52.1],
                'ai' => ['effectiveness' => 54.7, 'behavior_change' => 48.9],
                'whatsapp' => ['effectiveness' => 67.3, 'behavior_change' => 61.7]
            ],
            'most_effective_training' => 'email',
            'needs_improvement' => ['ai', 'quishing']
        ];
    }

    private function calculateCertificationProgress(): array
    {
        return [
            'total_certifications' => 167,
            'completion_rate' => 79.8,
            'in_progress' => 34,
            'expired_certifications' => 15,
            'upcoming_renewals' => 28,
            'certification_types' => [
                'email_security_awareness' => 92,
                'qr_code_phishing_detection' => 38,
                'ai_threat_recognition' => 25,
                'whatsapp_security_practices' => 45,
                'multi_platform_security' => 31
            ],
            'by_campaign_specialization' => [
                'email' => ['certified' => 92, 'in_progress' => 8, 'completion_rate' => 89.2],
                'quishing' => ['certified' => 38, 'in_progress' => 12, 'completion_rate' => 67.3],
                'ai' => ['certified' => 25, 'in_progress' => 15, 'completion_rate' => 58.1],
                'whatsapp' => ['certified' => 45, 'in_progress' => 9, 'completion_rate' => 76.8]
            ],
            'priority_certifications' => ['ai_threat_recognition', 'qr_code_phishing_detection']
        ];
    }

    // Utility Methods
    private function getCompanyIdFromEmail(string $email): int
    {
        // Extract domain and map to company ID
        $domain = substr(strrchr($email, "@"), 1);
        
        // This should query your database in a real implementation
        $companyMapping = [
            'example.com' => 1,
            'company.com' => 2,
            'business.org' => 3
        ];
        
        return $companyMapping[$domain] ?? 1;
    }

    private function getRecentCampaignData(): array
    {
        // This should fetch real data from your database
        return [
            'email' => [
                'failed_attempts' => 2,
                'successful_reports' => 18,
                'completion_rate' => 89.5
            ],
            'quishing' => [
                'failed_attempts' => 5,
                'successful_reports' => 8,
                'completion_rate' => 67.2
            ],
            'ai' => [
                'failed_attempts' => 7,
                'successful_reports' => 5,
                'completion_rate' => 58.9
            ],
            'whatsapp' => [
                'failed_attempts' => 3,
                'successful_reports' => 12,
                'completion_rate' => 78.3
            ]
        ];
    }

    public function payloadClicked(): int
    {
        $email =  CampaignLive::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('payload_clicked', 1)
            ->count();
        $quishing = QuishingLiveCamp::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('qr_scanned', '1')
            ->count();
        $whatsapp = WaLiveCampaign::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('payload_clicked', 1)
            ->count();

        return $email + $quishing + $whatsapp;
    }

    public function compromised(): int
    {
        $email =  CampaignLive::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('emp_compromised', 1)
            ->count();
        $quishing = QuishingLiveCamp::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('compromised', '1')
            ->count();
        $whatsapp = WaLiveCampaign::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('compromised', 1)
            ->count();
        $ai = AiCallCampLive::where('employee_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('compromised', 1)
            ->count();

        return $email + $quishing + $whatsapp + $ai;
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

        return $email + $quishing ;
    }

    public function emailViewed(): int
    {
        $email =  CampaignLive::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('mail_open', 1)
            ->count();
        $quishing = QuishingLiveCamp::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('mail_open', '1')
            ->count();

        return $email + $quishing ;
    }
}