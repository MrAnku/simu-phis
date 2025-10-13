<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\ApiAivishingReportController;
use App\Http\Controllers\Api\ApiDashboardController;
use App\Http\Controllers\Api\ApiQuishingReportController;
use App\Http\Controllers\Api\ApiWhatsappReportController;
use App\Models\Company;
use App\Models\Users;
use App\Models\Campaign;
use App\Models\CampaignLive;
use App\Models\TrainingAssignedUser;
use App\Models\AssignedPolicy;
use App\Mail\OverallReportMail;
use App\Models\BlueCollarEmployee;
use App\Models\Policy;
use App\Models\QuishingLiveCamp;
use App\Models\TprmCampaignLive;
use App\Models\TrainingModule;
use App\Models\WaLiveCampaign;
use App\Services\CompanyReport;
use App\Services\EmployeeReport;
use App\Services\Reports\OverallNormalEmployeeReport;
use App\Services\Simulations\EmailCampReport;
use App\Services\Simulations\QuishingCampReport;
use App\Services\Simulations\VishingCampReport;
use App\Services\Simulations\WhatsappCampReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SimpleMonthlyReport extends Command
{
    protected $signature = 'report:monthly';
    protected $description = 'Generate overall monthly reports for all companies and send via email';

    public function handle()
    {
        // Get all companies
        $companies = Company::all();

        foreach ($companies as $company) {
            $this->generateAndSendReport($company);
        }

        echo "All monthly reports sent successfully!";
    }

    private function generateAndSendReport($company)
    {
        try {
            $companyId = $company->company_id;

            $companyReport = new CompanyReport($companyId);
            $emailCampReport = new EmailCampReport($companyId);
            $quishingCampReport = new QuishingCampReport($companyId);
            $waCampReport = new WhatsappCampReport($companyId);
            $aiVishReport = new VishingCampReport($companyId);
            $overallReport = new OverallNormalEmployeeReport($companyId);
            $dashController = new ApiDashboardController();
            $waController = new ApiWhatsappReportController();
            $qrController = new ApiQuishingReportController();
            $aiController = new ApiAivishingReportController();

            // Get basic metrics with error handling
            $data = [
                'company_name' => $company->company_name ?? 'Unknown Company',
                'total_users' => Users::where('company_id', $companyId)->count() + BlueCollarEmployee::where('company_id', $companyId)->count(),
                'blue_collar_employees' => BlueCollarEmployee::where('company_id', $companyId)->count(),

                'email_camp_data' => [
                    'email_campaign' => $companyReport->emailCampaigns() ?? 0,
                    'email_sent' => $emailCampReport->emailSent() ?? 0,
                    'email_viewed' => $emailCampReport->emailViewed() ?? 0,
                    'payload_clicked' => $emailCampReport->payloadClicked() ?? 0,
                    'email_reported' => $emailCampReport->emailReported() ?? 0,
                    'compromised' => $emailCampReport->compromised() ?? 0,
                    'total_attempts' => $emailCampReport->totalAttempts() ?? 0
                ],

                'quish_camp_data' => [
                    'quishing_campaign' => $companyReport->quishingCampaigns() ?? 0,
                    'email_sent' => $quishingCampReport->emailSent() ?? 0,
                    'email_viewed' => $quishingCampReport->emailViewed() ?? 0,
                    'qr_scanned' => $quishingCampReport->qrScanned() ?? 0,
                    'email_reported' => $quishingCampReport->emailReported() ?? 0,
                    'compromised' => $quishingCampReport->compromised() ?? 0,
                    'total_attempts' => $quishingCampReport->totalAttempts() ?? 0
                ],

                'wa_camp_data' => [
                    'whatsapp_campaign' => $companyReport->whatsappCampaigns() ?? 0,
                    'message_sent' => $waCampReport->messageSent() ?? 0,
                    'message_viewed' => $waCampReport->messageViewed() ?? 0,
                    'link_clicked' => $waCampReport->linkClicked() ?? 0,
                    'compromised' => $waCampReport->compromised() ?? 0,
                    'total_attempts' => $waCampReport->totalAttempts() ?? 0
                ],

                'ai_camp_data' => [
                    'ai_vishing' => $companyReport->aiCampaigns() ?? 0,
                    'calls_sent' => $aiVishReport->callsSent() ?? 0,
                    'calls_received' => $aiVishReport->callsReceived() ?? 0,
                    'compromised' => $aiVishReport->compromised() ?? 0,
                    'completed_calls' => $aiVishReport->completedCalls() ?? 0,
                    'total_attempts' => $aiVishReport->totalAttempts() ?? 0
                ],

                'training_assigned' => $companyReport->totalTrainingAssigned() ?? 0,
                'training_completed' => $companyReport->completedTraining() ?? 0,
                'total_Policies' => Policy::where('company_id', $companyId)->count(),
                'assigned_Policies' => AssignedPolicy::where('company_id', $companyId)->count(),
                'acceptance_Policies' => AssignedPolicy::where('company_id', $companyId)->where('accepted', 1)->count(),
                'riskScore' => $companyReport->calculateOverallRiskScore(),
                'most_compromised_employees' => $overallReport->mostCompromisedEmployees(),
                'most_clicked_emp' => $overallReport->mostClickedEmployees(),
                'phish_clicks_weekly' => $dashController->clicksInWeekDays(null, null, $companyId),
                'avg_scores' => $overallReport->scoreAverage(),
                'riskAnalysis' => $overallReport->riskAnalysis(),
                'certifiedUsers' => $companyReport->certifiedUsers(),
                'totalTrainingStarted' => $companyReport->totalTrainingStarted(),
                'totalBadgesAssigned' => $companyReport->totalBadgesAssigned(),
                'trainingStatusDistribution' => $this->getTrainingStatusDistribution($companyId),
                'wa_events_over_time' => $waController->eventsOverTime(null, null, $companyId),
                'qr_events_over_time' => $qrController->eventsOverTime(null, null, $companyId),
                'ai_events_over_time' => $aiController->eventsOverTime(null, null, $companyId),

            ];

            // Calculate aggregate metrics for backward compatibility
            $totalEmailsSent = ($data['email_camp_data']['email_sent'] ?? 0) + ($data['quish_camp_data']['email_sent'] ?? 0);
            $totalPayloadClicked = ($data['email_camp_data']['payload_clicked'] ?? 0) + ($data['quish_camp_data']['qr_scanned'] ?? 0) + ($data['wa_camp_data']['link_clicked'] ?? 0);
            $totalCampaigns = ($data['email_camp_data']['email_campaign'] ?? 0) + ($data['quish_camp_data']['quishing_campaign'] ?? 0) +
                ($data['wa_camp_data']['whatsapp_campaign'] ?? 0) + ($data['ai_camp_data']['ai_vishing'] ?? 0);

            // Calculate total compromised accounts from all campaign types
            $totalCompromised = ($data['email_camp_data']['compromised'] ?? 0) +
                ($data['quish_camp_data']['compromised'] ?? 0) +
                ($data['wa_camp_data']['compromised'] ?? 0) +
                ($data['ai_camp_data']['compromised'] ?? 0);

            // Calculate risk text based on risk score
            $riskScore = $data['riskScore'] ?? 0;
            $riskText = 'Unknown';
            if ($riskScore <= 30) {
                $riskText = 'Low Risk';
            } elseif ($riskScore <= 60) {
                $riskText = 'Moderate Risk';
            } elseif ($riskScore <= 80) {
                $riskText = 'High Risk';
            } else {
                $riskText = 'Critical Risk';
            }

            // Add calculated fields
            $data['campaigns_sent'] = $totalCampaigns;
            $data['emails_sent'] = $totalEmailsSent;
            $data['payload_clicked'] = $totalPayloadClicked;
            $data['totalCompromised'] = $totalCompromised;
            $data['riskText'] = $riskText;
            $data['click_rate'] = $companyReport->clickRate();

            // print_r($data['wa_events_over_time']); // Debug WA data
            // print_r($data['qr_events_over_time']); // Debug QR data  
            // print_r($data['ai_events_over_time']); // Debug AI data
            // return;

            // Generate PDF
            $pdf = Pdf::loadView('new-overall-report', $data);
            $pdfContent = $pdf->output();

            // Send email with PDF attachment
            $this->sendReportEmail($company, $data, $pdfContent);
        } catch (\Exception $e) {
            echo "Error generating report for company {$company->company_name}: " . $e->getMessage() . "\n";
        }
    }

    private function sendReportEmail($company, $data, $pdfContent)
    {
        // Get email from company table
        $email = $company->email;

        if (!$email) {
            echo "No email found for company: {$company->company_name}";
            return;
        }

        // Send email using Mailable class
        Mail::to($email)->send(new OverallReportMail($data, $pdfContent));

        echo "Report sent to: {$email}";
    }

    /**
     * Get training status distribution for the company based on fetchTrainingReporting pattern
     */
    private function getTrainingStatusDistribution($companyId)
    {
        try {
            // Get all training assignments for the company users
            $totalAssignedTrainings = TrainingAssignedUser::where('company_id', $companyId)->count();

            // Count completed trainings (highest priority)
            $completedTrainings = TrainingAssignedUser::where('company_id', $companyId)
                ->where('completed', 1)->count();

            // Count overdue trainings (not completed and due date passed)
            $overdueTrainings = TrainingAssignedUser::where('company_id', $companyId)
                ->where('completed', 0)
                ->where('training_due_date', '<=', Carbon::now())
                ->count();

            // Count in progress trainings (started but not completed and not overdue)
            $inProgressTrainings = TrainingAssignedUser::where('company_id', $companyId)
                ->where('completed', 0)
                ->where('training_started', 1)
                ->count();

            // Count not started trainings (not started, not completed, and not overdue)
            $notStartedTrainings = TrainingAssignedUser::where('company_id', $companyId)
                ->where('completed', 0)
                ->where('training_started', 0)
                ->count();


            $total =  $completedTrainings + $inProgressTrainings + $notStartedTrainings + $overdueTrainings;
            $completedRate = $total > 0 ? ($completedTrainings / $total) * 100 : 0;
            $inProgressRate = $total > 0 ? ($inProgressTrainings / $total) * 100 : 0;
            $notStartedRate = $total > 0 ? ($notStartedTrainings / $total) * 100 : 0;
            $overdueRate = $total > 0 ? ($overdueTrainings / $total) * 100 : 0;

            // Calculate percentages
            if ($totalAssignedTrainings > 0) {
                $completedPercentage = round(($completedTrainings / $totalAssignedTrainings) * 100, 1);
                $inProgressPercentage = round(($inProgressTrainings / $totalAssignedTrainings) * 100, 1);
                $notStartedPercentage = round(($notStartedTrainings / $totalAssignedTrainings) * 100, 1);
                $overduePercentage = round(($overdueTrainings / $totalAssignedTrainings) * 100, 1);
                
                // Ensure percentages add up to 100% by adjusting the largest category
                $totalPercentage = $completedPercentage + $inProgressPercentage + $notStartedPercentage + $overduePercentage;
                if ($totalPercentage != 100) {
                    $diff = 100 - $totalPercentage;
                    $largest = max($completedPercentage, $inProgressPercentage, $notStartedPercentage, $overduePercentage);
                    if ($largest == $overduePercentage) {
                        $overduePercentage += $diff;
                    } elseif ($largest == $inProgressPercentage) {
                        $inProgressPercentage += $diff;
                    } elseif ($largest == $notStartedPercentage) {
                        $notStartedPercentage += $diff;
                    } else {
                        $completedPercentage += $diff;
                    }
                }
            } else {
                $completedPercentage = $inProgressPercentage = $notStartedPercentage = $overduePercentage = 0;
            }

            return [
                'total_trainings' => $totalAssignedTrainings,
                'completed' => $completedTrainings,
                'in_progress' => $inProgressTrainings,
                'not_started' => $notStartedTrainings,
                'overdue' => $overdueTrainings,
                'completed_percentage' => $completedPercentage,
                'in_progress_percentage' => $inProgressPercentage,
                'not_started_percentage' => $notStartedPercentage,
                'overdue_percentage' => $overduePercentage,
                'completed_rate' => round($completedRate, 1),
                'in_progress_rate' => round($inProgressRate, 1),
                'not_started_rate' => round($notStartedRate, 1),
                'overdue_rate' => round($overdueRate, 1)
            ];

        } catch (\Exception $e) {
            // Return default values if there's an error
            return [
                'total_trainings' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'not_started' => 0,
                'overdue' => 0,
                'completed_percentage' => 0,
                'in_progress_percentage' => 0,
                'not_started_percentage' => 0,
                'overdue_percentage' => 0
            ];
        }
    }
}
