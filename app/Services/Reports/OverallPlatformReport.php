<?php

namespace App\Services\Reports;

use App\Http\Controllers\Api\ApiAivishingReportController;
use App\Http\Controllers\Api\ApiDashboardController;
use App\Http\Controllers\Api\ApiQuishingReportController;
use App\Http\Controllers\Api\ApiWhatsappReportController;
use App\Models\BlueCollarEmployee;
use App\Models\TrainingAssignedUser;
use App\Models\Users;
use App\Services\CompanyReport;
use App\Services\Simulations\EmailCampReport;
use App\Services\Simulations\QuishingCampReport;
use App\Services\Simulations\VishingCampReport;
use App\Services\Simulations\WhatsappCampReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OverallPlatformReport
{

    public function getTrainingStatusDistribution($companyId)
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

            return [
                'total_trainings' => $totalAssignedTrainings,
                'completed' => $completedTrainings,
                'in_progress' => $inProgressTrainings,
                'not_started' => $notStartedTrainings,
                'overdue' => $overdueTrainings,
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
                'overdue' => 0
            ];
        }
    }

    public function generateDonutChartImage(array $labels, array $data, $companyId, array $colors = null, array $chartOptions = null, $width = 600, $height = 400)
    {
        $defaultOptions = [
            'plugins' => [
                'legend' => ['display' => false]
            ],
            // ensure full circle by default
        ];

        // Merge chart options if provided (shallow merge is fine for our simple use)
        if (is_array($chartOptions)) {
            $options = array_merge($defaultOptions, $chartOptions);
        } else {
            $options = $defaultOptions;
        }

        $chartConfig = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $data,
                    // Match legend: Assigned (blue), Started (orange), Completed (green) by default
                    'backgroundColor' => $colors ?? ['#3498db', '#f39c12', '#2ecc71'],
                    'borderWidth' => 0,
                ]]
            ],
            'options' => $options,
        ];

        $payload = ['chart' => json_encode($chartConfig), 'width' => $width, 'height' => $height, 'format' => 'png', 'backgroundColor' => 'transparent'];

        $attempts = 0;
        $maxAttempts = 3;
        $lastException = null;

        while ($attempts < $maxAttempts) {
            try {
                $attempts++;
                $response = Http::timeout(10)->post('https://quickchart.io/chart', $payload);

                if ($response->successful() && strlen($response->body()) > 0) {
                    $bytes = $response->body();
                    $filename = 'reports/donut_' . $companyId . '_' . time() . '.png';
                    // Save binary bytes to public storage
                    Storage::disk('public')->put($filename, $bytes);

                    // Public URL
                    $publicUrl = asset('storage/' . $filename);

                    // Local filesystem absolute path (use storage path)
                    $localPath = Storage::disk('public')->path($filename);
                    // Normalize path for file:// URL (Windows needs file:///C:/path format)
                    $normalized = str_replace('\\', '/', $localPath);
                    if (preg_match('#^[A-Za-z]:/#', $normalized)) {
                        // Windows absolute path like C:/...
                        $localFileUrl = 'file:///' . $normalized;
                    } else {
                        // Unix-like absolute path
                        $localFileUrl = 'file://' . $normalized;
                    }

                    $base64 = base64_encode($bytes);

                    Log::info("Generated donut chart for company {$companyId}", ['attempts' => $attempts, 'file' => $filename, 'localPath' => $localPath]);

                    return [
                        'public_url' => $publicUrl,
                        'local_file' => $localFileUrl,
                        'filename' => $filename,
                        'base64' => $base64,
                    ];
                }

                // Non-successful response: log and retry
                Log::warning("QuickChart returned non-success status for company {$companyId}", ['status' => $response->status(), 'attempt' => $attempts]);
            } catch (\Exception $e) {
                $lastException = $e;
                Log::warning("QuickChart request failed (attempt {$attempts}) for company {$companyId}: {$e->getMessage()}");
                // brief backoff before retrying
                sleep(1 * $attempts);
            }
        }

        if ($lastException) {
            Log::error("Failed to generate donut chart for company {$companyId}", ['exception' => $lastException->getMessage()]);
        } else {
            Log::error("Failed to generate donut chart for company {$companyId}: unknown error after {$maxAttempts} attempts");
        }

        return null;
    }

    public function prepareReportData($company)
    {
        $companyId = $company->company_id;
        $companyReport = new CompanyReport($companyId);
        $overallReport = new OverallNormalEmployeeReport($companyId);
        $dashController = new ApiDashboardController();
        $waController = new ApiWhatsappReportController();
        $qrController = new ApiQuishingReportController();
        $aiController = new ApiAivishingReportController();
        $overallReportService = new OverallPlatformReport();

        $userCount = Users::where('company_id', $companyId)->count();
        $blueCollarCount = BlueCollarEmployee::where('company_id', $companyId)->count();
        $totalUsers = $userCount + $blueCollarCount;

        return [
            'company_name' => $company->company_name,
            'total_users' => $totalUsers,
            'blue_collar_employees' => $blueCollarCount,
            'email_camp_data' => $this->getEmailCampData($companyId),
            'quish_camp_data' => $this->getQuishCampData($companyId),
            'wa_camp_data' => $this->getWhatsAppCampData($companyId),
            'ai_camp_data' => $this->getAiCampData($companyId),
            'training_assigned' => $companyReport->totalTrainingAssigned() ?? 0,
            'training_completed' => $companyReport->completedTraining() ?? 0,
            'training_completion_rate' => $companyReport->trainingCompletionRate() ?? 0,
            'pending_training' => $companyReport->pendingTraining() ?? 0,
            'training_pending_rate' => $companyReport->trainingPendingRate() ?? 0,
            'assigned_policies' => $companyReport->totalPoliciesAssigned() ?? 0,
            'accepted_policies' => $companyReport->acceptedPolicies() ?? 0,
            'accepted_policies_rate' => $companyReport->acceptedPoliciesRate() ?? 0,
            'not_accepted_policies' => $companyReport->notAcceptedPolicies() ?? 0,
            'not_accepted_policies_rate' => $companyReport->notAcceptedPoliciesRate() ?? 0,
            'riskScore' => $companyReport->calculateOverallRiskScore(),
            'most_compromised_employees' => $overallReport->mostCompromisedEmployees(),
            'most_clicked_emp' => $overallReport->mostClickedEmployees(),
            'phish_clicks_weekly' => $dashController->clicksInWeekDays(null, null, $companyId),
            'avg_scores' => $overallReport->scoreAverage(),
            'riskAnalysis' => $overallReport->riskAnalysis(),
            'certifiedUsers' => $companyReport->certifiedUsers(),
            'totalTrainingStarted' => $companyReport->totalTrainingStarted(),
            'totalBadgesAssigned' => $companyReport->totalBadgesAssigned() ?? 0,
            'trainingStatusDistribution' => $overallReportService->getTrainingStatusDistribution($companyId),
            'wa_events_over_time' => $waController->eventsOverTime(null, null, $companyId),
            'qr_events_over_time' => $qrController->eventsOverTime(null, null, $companyId),
            'ai_events_over_time' => $aiController->eventsOverTime(null, null, $companyId),
        ];
    }

    private function getEmailCampData($companyId)
    {
        $emailCampReport = new EmailCampReport($companyId);
        $companyReport = new CompanyReport($companyId);

        return [
            'email_campaign' => $companyReport->emailCampaigns() ?? 0,
            'email_sent' => $emailCampReport->emailSent() ?? 0,
            'payload_clicked' => $emailCampReport->payloadClicked() ?? 0,
            'email_reported' => $emailCampReport->emailReported() ?? 0,
            'compromised' => $emailCampReport->compromised() ?? 0,
            'total_attempts' => $emailCampReport->totalAttempts() ?? 0
        ];
    }

    private function getQuishCampData($companyId)
    {
        $quishingCampReport = new QuishingCampReport($companyId);
        $companyReport = new CompanyReport($companyId);

        return [
            'quishing_campaign' => $companyReport->quishingCampaigns() ?? 0,
            'email_sent' => $quishingCampReport->emailSent() ?? 0,
            'qr_scanned' => $quishingCampReport->qrScanned() ?? 0,
            'email_reported' => $quishingCampReport->emailReported() ?? 0,
            'compromised' => $quishingCampReport->compromised() ?? 0,
            'total_attempts' => $quishingCampReport->totalAttempts() ?? 0
        ];
    }

    private function getWhatsAppCampData($companyId)
    {
        $waCampReport = new WhatsappCampReport($companyId);
        $companyReport = new CompanyReport($companyId);

        return [
            'whatsapp_campaign' => $companyReport->whatsappCampaigns() ?? 0,
            'message_viewed' => $waCampReport->messageViewed() ?? 0,
            'link_clicked' => $waCampReport->linkClicked() ?? 0,
            'compromised' => $waCampReport->compromised() ?? 0,
            'total_attempts' => $waCampReport->totalAttempts() ?? 0
        ];
    }

    private function getAiCampData($companyId)
    {
        $aiVishReport = new VishingCampReport($companyId);
        $companyReport = new CompanyReport($companyId);

        return [
            'ai_vishing' => $companyReport->aiCampaigns() ?? 0,
            'compromised' => $aiVishReport->compromised() ?? 0,
            'total_attempts' => $aiVishReport->totalAttempts() ?? 0,
            'reported_calls' => $aiVishReport->reportedCalls() ?? 0,
        ];
    }

    public function calculateAggregates($data)
    {
        $totalPayloadClicked = ($data['email_camp_data']['payload_clicked'] ?? 0) + ($data['quish_camp_data']['qr_scanned'] ?? 0) + ($data['wa_camp_data']['link_clicked'] ?? 0);
        $totalCampaigns = ($data['email_camp_data']['email_campaign'] ?? 0) + ($data['quish_camp_data']['quishing_campaign'] ?? 0) +
            ($data['wa_camp_data']['whatsapp_campaign'] ?? 0) + ($data['ai_camp_data']['ai_vishing'] ?? 0);
        $totalThreats = ($data['email_camp_data']['total_attempts'] ?? 0) +
            ($data['quish_camp_data']['total_attempts'] ?? 0) +
            ($data['wa_camp_data']['total_attempts'] ?? 0) +
            ($data['ai_camp_data']['total_attempts'] ?? 0);
        $totalCompromised = ($data['email_camp_data']['compromised'] ?? 0) +
            ($data['quish_camp_data']['compromised'] ?? 0) +
            ($data['wa_camp_data']['compromised'] ?? 0) +
            ($data['ai_camp_data']['compromised'] ?? 0);

        return [
            'campaigns_sent' => $totalCampaigns,
            'payload_clicked' => $totalPayloadClicked,
            'totalCompromised' => $totalCompromised,
            'total_threats' => $totalThreats,
            'click_rate' => $data['click_rate'] ?? null,
        ];
    }
}
