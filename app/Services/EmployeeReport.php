<?php

namespace App\Services;

use App\Models\Users;
use App\Models\Policy;
use App\Models\UsersGroup;
use App\Models\CampaignLive;
use App\Models\ScormTraining;
use App\Models\AiCallCampLive;
use App\Models\AssignedPolicy;
use App\Models\TrainingModule;
use App\Models\WaLiveCampaign;
use App\Models\QuishingLiveCamp;
use App\Models\ScormAssignedUser;
use App\Models\TrainingAssignedUser;

class EmployeeReport
{
    private string $email;
    private string $companyId;
    private array $dateRange; //array

    public function __construct(string $email, string $companyId, array $dateRange = [])
    {
        $this->email = $email;
        $this->companyId = $companyId;
        $this->dateRange = $dateRange;
    }

    // public function generateReport(): array
    // {
    //     return [
    //         // Risk Assessment Metrics
    //         'overall_risk_score' => $this->calculateOverallRiskScore(),
    //         'user_risk_distribution' => $this->calculateUserRiskDistribution(),

    //         // Campaign Performance Metrics
    //         'click_through_rates' => $this->calculateClickThroughRates(),
    //         'email_report_rates' => $this->calculateEmailReportRate(),
    //         'compromise_rates' => $this->calculateCompromiseRates(),

    //         // Training Analytics
    //         'training_completion_rates' => $this->calculateTrainingCompletionRates(),
    //         // 'knowledge_retention_scores' => $this->calculateKnowledgeRetention(),
    //         // 'training_effectiveness' => $this->calculateTrainingEffectiveness(),
    //         'certification_progress' => $this->calculateCertificationProgress(),

    //     ];
    // }

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



    public function calculateSecurityScore(): float
    {
        $trainingCompletionRate = $this->trainingCompletionRate();
        $policiesAcceptanceRate = $this->policiesAssigned() > 0 ?
            ($this->policiesAccepted() / $this->policiesAssigned()) * 100 : 0;
        $emailReportRate = $this->totalSimulations() > 0 ?
            ($this->emailReported() / $this->totalSimulations()) * 100 : 0;
        $riskScore = $this->calculateOverallRiskScore();

        // Security score considers positive security behaviors
        $securityScore = (
            ($trainingCompletionRate * 0.4) +  // 40% weight for training completion
            ($policiesAcceptanceRate * 0.3) +  // 30% weight for policy compliance
            ($emailReportRate * 0.2) +         // 20% weight for reporting threats
            ((100 - $riskScore) * 0.1)         // 10% weight for avoiding compromise
        );

        return round(min(100, max(0, $securityScore)), 2);
    }


    public function payloadClicked($email = null): int
    {
        $email =  CampaignLive::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->where('payload_clicked', 1)
            ->count();
        $quishing = QuishingLiveCamp::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->where('qr_scanned', '1')
            ->count();
        $whatsapp = WaLiveCampaign::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->where('payload_clicked', 1)
            ->count();
        $ai = AiCallCampLive::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->where('compromised', 1)
            ->count();

        return $email + $quishing + $whatsapp + $ai;
    }

    public function compromised($email = null): int
    {
        $email =  CampaignLive::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->where('emp_compromised', 1)
            ->count();
        $quishing = QuishingLiveCamp::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->where('compromised', '1')
            ->count();
        $whatsapp = WaLiveCampaign::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->where('compromised', 1)
            ->count();
        $ai = AiCallCampLive::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->where('compromised', 1)
            ->count();

        return $email + $quishing + $whatsapp + $ai;
    }

    public function compromiseRate(): float
    {
        $totalUsers = $this->totalSimulations();
        $compromisedUsers = $this->compromised();

        return $totalUsers > 0 ? round(($compromisedUsers / $totalUsers) * 100, 2) : 0;
    }

    public function emailReported(): int
    {
        $email =  CampaignLive::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->where('email_reported', 1)
            ->count();
        $quishing = QuishingLiveCamp::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->where('email_reported', '1')
            ->count();

        return $email + $quishing;
    }

    public function emailViewed($email = null): int
    {
        $email =  CampaignLive::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->where('mail_open', 1)
            ->count();
        $quishing = QuishingLiveCamp::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->where('mail_open', '1')
            ->count();

        return $email + $quishing;
    }

    public function totalSimulations($email = null): int
    {
        $email =  CampaignLive::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();
        $quishing = QuishingLiveCamp::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();
        $whatsapp = WaLiveCampaign::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();
        $ai = AiCallCampLive::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
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
                    $totalAi = AiCallCampLive::where('user_email', $this->email)
                        ->where('company_id', $this->companyId)
                        ->count();
                    $compromisedAi = AiCallCampLive::where('user_email', $this->email)
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
                + AiCallCampLive::where('user_email', $this->email)
                ->where('company_id', $this->companyId)
                ->count();

            $totalClicks = $this->payloadClicked() + $this->compromised();

            return $totalCampaigns > 0 ? round(($totalClicks / $totalCampaigns) * 100) : 0;
        }
    }

    public function policiesAcceptedNames(): array
    {
        $assignedPolicies = AssignedPolicy::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('accepted', 1)
            ->pluck('policy')
            ->toArray();
        if (empty($assignedPolicies)) {
            return [];
        }
        return Policy::whereIn('id', $assignedPolicies)->pluck('policy_name')->toArray();
    }

    public function assignedTrainings($email = null): int
    {
        $assignedTrainings = TrainingAssignedUser::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();
        $assignedScorms = ScormAssignedUser::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();

        return $assignedTrainings + $assignedScorms;
    }

    public function startedTrainings($email = null): int
    {
        $startedTrainings = TrainingAssignedUser::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->where('training_started', 1)
            ->where('completed', 0)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();

        $scormStarted = ScormAssignedUser::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->where('scorm_started', 1)
            ->where('completed', 0)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();

        return $startedTrainings + $scormStarted;
    }

    public function trainingInProgress($email = null): int
    {
        $inprogressTrainings = TrainingAssignedUser::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->where('training_started', 1)
            ->where('completed', 0)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();
        $inprogressScorms = ScormAssignedUser::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->where('scorm_started', 1)
            ->where('completed', 0)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();

        return $inprogressTrainings + $inprogressScorms;
    }

    public function trainingCompleted($email = null): int
    {
        $completedTrainings = TrainingAssignedUser::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->where('completed', 1)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();
        $completedScorms = ScormAssignedUser::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->where('completed', 1)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();

        return $completedTrainings + $completedScorms;
    }

    public function certifiedTrainings($email = null): int
    {
        $completedTrainings = TrainingAssignedUser::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->where('completed', 1)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();
        $completedScorms = ScormAssignedUser::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->where('completed', 1)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();

        return $completedTrainings + $completedScorms;
    }

    public function overdueTrainings($email = null): int
    {
        $overdueTrainings = TrainingAssignedUser::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->where('completed', 0)
            ->where('training_due_date', '<', now())
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();
        $overdueScorms = ScormAssignedUser::where('user_email', $email ?? $this->email)
            ->where('company_id', $this->companyId)
            ->where('completed', 0)
            ->where('scorm_due_date', '<', now())
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();

        return $overdueTrainings + $overdueScorms;
    }

    public function trainingCompletionRate(): float
    {
        $assignedTrainings = $this->assignedTrainings();
        $completedTraining = $this->trainingCompleted();

        return $assignedTrainings > 0 ? round(($completedTraining / $assignedTrainings) * 100, 2) : 0;
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

    public function totalIgnored(): int
    {
        $totalSimulations = $this->totalSimulations();
        $emailViewed = $this->emailViewed();
        $payloadClicked = $this->payloadClicked();
        $compromised = $this->compromised();
        $emailReported = $this->emailReported();

        return $totalSimulations - ($emailViewed + $payloadClicked + $compromised + $emailReported);
    }

    public function ignoreRate(): float
    {
        $totalSimulations = $this->totalSimulations();
        $ignored = $this->totalIgnored();

        return $totalSimulations > 0 ? round(($ignored / $totalSimulations) * 100, 2) : 0;
    }

    public function assignedGames(): int
    {
        return TrainingAssignedUser::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('training_type', 'games')
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })->count();
    }

    public function averageGameScore(): float
    {
        $avg = TrainingAssignedUser::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('training_type', 'games')
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->avg('personal_best') ?? 0;

        return round((float) $avg, 2);
    }

    public function averageGamePlayTime(): string
    {
        $avg = TrainingAssignedUser::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('training_type', 'games')
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->avg('game_time') ?? 0;

        $timeInSeconds = round((float) $avg, 2);

        return round($timeInSeconds / 60, 2) . " minutes";
    }

    public function policiesAssigned(): int
    {
        return AssignedPolicy::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();
    }
    public function policiesAccepted(): int
    {
        return AssignedPolicy::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('accepted', 1)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();
    }
    public function policyQuizResponded(): int
    {
        return AssignedPolicy::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('json_quiz_response', '!=', null)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();
    }

    public function outstandingTrainings(): int
    {
        $outstandingTrainings = TrainingAssignedUser::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('completed', 1)
            ->where('personal_best', '>=', 90)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();
            
        $outstandingScorms = ScormAssignedUser::where('user_email', $this->email)
            ->where('company_id', $this->companyId)
            ->where('completed', 1)
            ->where('personal_best', '>=', 90)
            ->when(!empty($this->dateRange), function ($query) {
                return $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();

        return $outstandingTrainings + $outstandingScorms;
    }
}
