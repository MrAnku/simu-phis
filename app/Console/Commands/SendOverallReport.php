<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\ApiAivishingReportController;
use App\Http\Controllers\Api\ApiDashboardController;
use App\Http\Controllers\Api\ApiQuishingReportController;
use App\Http\Controllers\Api\ApiWhatsappReportController;
use App\Mail\OverallReportMail;
use App\Models\BlueCollarEmployee;
use App\Models\Company;
use App\Models\OverallReport;
use App\Models\TrainingAssignedUser;
use App\Models\Users;
use App\Services\CompanyReport;
use App\Services\Reports\OverallNormalEmployeeReport;
use App\Services\Simulations\EmailCampReport;
use App\Services\Simulations\QuishingCampReport;
use App\Services\Simulations\VishingCampReport;
use App\Services\Simulations\WhatsappCampReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendOverallReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-overall-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Fetch companies that have overall_report setting enabled (not null) in settings table
        $companies = Company::whereHas('company_settings', function ($query) {
            $query->whereNotNull('overall_report');
        })->get();

        foreach ($companies as $company) {
            if ($this->shouldGenerateReport($company)) {
                $this->generateAndSendReport($company);
            }
        }
    }

    private function generateAndSendReport($company)
    {
        try {
            $data = $this->prepareReportData($company);

            $aggregates = $this->calculateAggregates($data);
            $riskText = $this->getRiskText($data['riskScore'] ?? 0);
            $data = array_merge($data, $aggregates, ['riskText' => $riskText]);

            // Generate donut chart image server-side and include in view data
            try {
                $labels = ['Assigned', 'Started', 'Completed'];
                $donutData = [
                    (int)($data['training_assigned'] ?? 0),
                    (int)($data['totalTrainingStarted'] ?? 0),
                    (int)($data['training_completed'] ?? 0),
                ];
                $chartResult = $this->generateDonutChartImage($labels, $donutData, $company->company_id);
                if (is_array($chartResult)) {
                    // public URL for browser previews, local file path (file:///) for dompdf, and base64 for embedding
                    $data['donutChartImage'] = $chartResult['public_url'] ?? null;
                    $data['donutChartImageLocal'] = $chartResult['local_file'] ?? null;
                    $data['donutChartImageBase64'] = $chartResult['base64'] ?? null;
                } else {
                    $data['donutChartImage'] = null;
                    $data['donutChartImageLocal'] = null;
                }
            } catch (\Exception $e) {
                // don't fail the whole report if chart generation fails
                $data['donutChartImage'] = null;
                $data['donutChartImageLocal'] = null;
            }

            // Generate Risk Distribution donut server-side (High, Moderate, Low) with matching colors
            try {
                $riskLabels = ['High Risk', 'Moderate Risk', 'Low Risk'];
                $riskData = [
                    (int)($data['riskAnalysis']['in_high_risk'] ?? 0),
                    (int)($data['riskAnalysis']['in_moderate_risk'] ?? 0),
                    (int)($data['riskAnalysis']['in_low_risk'] ?? 0),
                ];
                // Colors: High=red, Moderate=orange, Low=green
                $riskColors = ['#ef4444', '#fb923c', '#10b981'];
                $riskChart = $this->generateDonutChartImage($riskLabels, $riskData, $company->company_id . '_risk', $riskColors);
                if (is_array($riskChart)) {
                    $data['riskChartImage'] = $riskChart['public_url'] ?? null;
                    $data['riskChartImageLocal'] = $riskChart['local_file'] ?? null;
                    $data['riskChartImageBase64'] = $riskChart['base64'] ?? null;
                } else {
                    $data['riskChartImage'] = null;
                    $data['riskChartImageLocal'] = null;
                }
            } catch (\Exception $e) {
                $data['riskChartImage'] = null;
                $data['riskChartImageLocal'] = null;
            }

            // Render the view to HTML first so we can inspect/verify embedding (debugging)
            $html = view('new-overall-report', $data)->render();

            // Log whether base64 exists and HTML contains a data URI
            try {
                $base64Len = isset($data['donutChartImageBase64']) ? strlen($data['donutChartImageBase64']) : 0;
            } catch (\Throwable $e) {
                $base64Len = 0;
            }
            Log::info("Donut base64 length at render", ['company' => $company->company_id, 'len' => $base64Len, 'has_data_uri' => (strpos($html, 'data:image/png;base64,') !== false)]);

            // Save the rendered HTML to local storage for inspection
            try {
                $debugFilename = 'reports/debug_overall_report_' . $company->company_id . '_' . time() . '.html';
                Storage::disk('local')->put($debugFilename, $html);
                Log::info('Saved debug HTML for report render', ['file' => storage_path('app/' . $debugFilename)]);
            } catch (\Exception $e) {
                Log::warning('Failed to save debug HTML', ['msg' => $e->getMessage()]);
            }

            // Load the exact rendered HTML into dompdf to ensure what we inspect is what dompdf receives
            $pdf = Pdf::loadHTML($html);
            // Allow dompdf to fetch remote images if needed
            try {
                $pdf->setOptions([
                    'isRemoteEnabled' => true,
                    'isHtml5ParserEnabled' => true,
                    'isPhpEnabled' => true,
                ]);
            } catch (\Throwable $e) {
                // ignore if the underlying PDF wrapper doesn't support setOptions here
            }

            $pdfContent = $pdf->output();

            // Save a debug copy of the generated PDF locally for inspection
            try {
                $debugPdfName = 'reports/debug_overall_report_' . $company->company_id . '_' . time() . '.pdf';
                Storage::disk('local')->put($debugPdfName, $pdfContent);
                Log::info('Saved debug PDF for inspection', ['file' => storage_path('app/' . $debugPdfName)]);
            } catch (\Exception $e) {
                Log::warning('Failed to save debug PDF locally', ['msg' => $e->getMessage()]);
            }

            // Save report and send email
            $this->saveReport($company, $pdfContent);
            $this->sendReportEmail($company, $data, $pdfContent);
        } catch (\Exception $e) {
            echo "Error generating report for company {$company->company_name}: " . $e->getMessage() . "\n";
        }
    }

    private function prepareReportData($company)
    {
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

        $reportFrequency = $company->company_settings->overall_report ?? 'monthly';

        return [
            'company_name' => $company->company_name,
            'report_frequency' => ucfirst($reportFrequency),
            'report_generated_at' => now()->format('Y-m-d H:i:s'),
            'total_users' => Users::where('company_id', $companyId)->count() + BlueCollarEmployee::where('company_id', $companyId)->count(),
            'blue_collar_employees' => BlueCollarEmployee::where('company_id', $companyId)->count(),
            'email_camp_data' => [
                'email_campaign' => $companyReport->emailCampaigns() ?? 0,
                'email_sent' => $emailCampReport->emailSent() ?? 0,
                'payload_clicked' => $emailCampReport->payloadClicked() ?? 0,
                'email_reported' => $emailCampReport->emailReported() ?? 0,
                'compromised' => $emailCampReport->compromised() ?? 0,
                'total_attempts' => $emailCampReport->totalAttempts() ?? 0
            ],
            'quish_camp_data' => [
                'quishing_campaign' => $companyReport->quishingCampaigns() ?? 0,
                'email_sent' => $quishingCampReport->emailSent() ?? 0,
                'qr_scanned' => $quishingCampReport->qrScanned() ?? 0,
                'email_reported' => $quishingCampReport->emailReported() ?? 0,
                'compromised' => $quishingCampReport->compromised() ?? 0,
                'total_attempts' => $quishingCampReport->totalAttempts() ?? 0
            ],
            'wa_camp_data' => [
                'whatsapp_campaign' => $companyReport->whatsappCampaigns() ?? 0,
                'message_viewed' => $waCampReport->messageViewed() ?? 0,
                'link_clicked' => $waCampReport->linkClicked() ?? 0,
                'compromised' => $waCampReport->compromised() ?? 0,
                'total_attempts' => $waCampReport->totalAttempts() ?? 0
            ],
            'ai_camp_data' => [
                'ai_vishing' => $companyReport->aiCampaigns() ?? 0,
                'compromised' => $aiVishReport->compromised() ?? 0,
                'total_attempts' => $aiVishReport->totalAttempts() ?? 0,
                'reported_calls' => $aiVishReport->reportedCalls() ?? 0,
            ],
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
            'trainingStatusDistribution' => $this->getTrainingStatusDistribution($companyId),
            'wa_events_over_time' => $waController->eventsOverTime(null, null, $companyId),
            'qr_events_over_time' => $qrController->eventsOverTime(null, null, $companyId),
            'ai_events_over_time' => $aiController->eventsOverTime(null, null, $companyId),
        ];
    }

    private function calculateAggregates($data)
    {
        $totalEmailsSent = ($data['email_camp_data']['email_sent'] ?? 0) + ($data['quish_camp_data']['email_sent'] ?? 0);
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
            'emails_sent' => $totalEmailsSent,
            'payload_clicked' => $totalPayloadClicked,
            'totalCompromised' => $totalCompromised,
            'total_threats' => $totalThreats,
            'click_rate' => $data['click_rate'] ?? null,
        ];
    }

    private function getRiskText($riskScore)
    {
        if ($riskScore <= 30) {
            return 'Low Risk';
        } elseif ($riskScore <= 60) {
            return 'Moderate Risk';
        } elseif ($riskScore <= 80) {
            return 'High Risk';
        } else {
            return 'Critical Risk';
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

        echo "Report sent to: {$email}\n";
    }

    private function saveReport($company, $pdfContent)
    {
        $relativePath = '/reports/' . $company->company_id . '/' . uniqid() . '.pdf';

        // Save using Storage
        Storage::disk('s3')->put($relativePath, $pdfContent);
        $report_path = Storage::disk('s3')->path($relativePath);

        OverallReport::create([
            'company_id' => $company->company_id,
            'report_path' => '/' . $report_path,
        ]);

        echo "Report saved for: {$company->email}\n";
    }

    private function shouldGenerateReport($company): bool
    {
        // Get the company's report frequency setting
        $reportFrequency = $company->company_settings->overall_report ?? null;

        if (!$reportFrequency) {
            return false; // No setting found
        }

        // Check if there's a previous report for this company
        $lastReport = OverallReport::where('company_id', $company->company_id)
            ->orderBy('id', 'desc')
            ->first();

        // If no previous report exists, generate report (first time)
        if (!$lastReport) {
            echo "No previous report found for {$company->company_name}. Generating first report.\n";
            return true;
        }

        // Calculate when next report should be generated based on frequency
        $lastReportDate = Carbon::parse($lastReport->created_at);

        $nextReportDate = $this->getNextReportDate($lastReportDate, $reportFrequency);

        // Check if it's exactly the time for the next report (using static date for testing)
        $currentDate = '2025-10-24'; // Static date for testing
        $currentDate = Carbon::parse($currentDate);
        // $currentDate = Carbon::now();
        $shouldGenerate = $currentDate->isSameDay($nextReportDate);

        if ($shouldGenerate) {
            echo "{$reportFrequency} report for {$company->company_name}. Last report: {$lastReportDate->format('Y-m-d')}\n";
        } else {
            echo "Not time yet for {$company->company_name}. Next report due: {$nextReportDate->format('Y-m-d')}\n";
        }

        return $shouldGenerate;
    }

    private function getNextReportDate(Carbon $lastReportDate, string $frequency): Carbon
    {
        return match (strtolower($frequency)) {
            'weekly' => $lastReportDate->copy()->addWeek(),
            'monthly' => $lastReportDate->copy()->addMonth(),
            'quarterly' => $lastReportDate->copy()->addMonths(3),
            'semi_annually' => $lastReportDate->copy()->addMonths(6),
            'annually' => $lastReportDate->copy()->addYear(),
            default => $lastReportDate->copy()->addMonth(), // Default to monthly
        };
    }

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

    /**
     * Generate a donut chart image using QuickChart and save to storage/public/reports
     * Returns public URL or null on failure
     */
    private function generateDonutChartImage(array $labels, array $data, $companyId, array $colors = null)
    {
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
            'options' => [
                'plugins' => [
                    'legend' => ['display' => false]
                ]
            ]
        ];

        $payload = ['chart' => json_encode($chartConfig), 'width' => 600, 'height' => 400, 'format' => 'png', 'backgroundColor' => 'transparent'];

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

                    // Local filesystem absolute path (use storage path) and file:// URL for dompdf local loading
                    $localPath = Storage::disk('public')->path($filename);
                    $localFileUrl = 'file://' . $localPath;

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
}
