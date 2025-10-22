<?php

namespace App\Console\Commands;

use App\Mail\OverallReportMail;
use App\Models\Company;
use App\Models\OverallReport;
use App\Services\Reports\OverallPlatformReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SendOverallReport extends Command
{
    protected $signature = 'app:send-overall-report';

    protected $description = 'Command description';

    public function handle()
    {
        // Fetch companies that have overall_report setting enabled (not null) in settings table
        $companies = Company::whereHas('company_settings', function ($query) {
            $query->whereNotNull('overall_report');
        })->get();

        foreach ($companies as $company) {
            if ($this->isScheduledForReport($company)) {
                $this->generateAndSendReport($company);
            }
        }
    }

    private function generateAndSendReport($company)
    {
        try {
            $overallReportService = new OverallPlatformReport();

            // prepare data and aggregates
            $data = $this->prepareDataUsingService($overallReportService, $company);

            // attach charts (donut, risk distribution, risk score donut)
            $this->attachTrainingAnalysisChart($overallReportService, $company, $data);
            $this->attachRiskDistributionChart($overallReportService, $company, $data);
            $this->attachRiskScoreChart($overallReportService, $company, $data);

            // render, save and send
            $this->renderPdfAndSend($company, $data);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            // Log error and present concise console message
            Log::error('Error generating overall report', ['company' => $company->company_id, 'exception' => $msg]);
            echo "Error generating report for company {$company->company_name}. Check logs for details.\n";
        }
    }

    private function prepareDataUsingService(OverallPlatformReport $svc, $company): array
    {
        $data = $svc->prepareReportData($company);
        $aggregates = $svc->calculateAggregates($data);
        return array_merge($data, $aggregates);
    }

    private function attachTrainingAnalysisChart(OverallPlatformReport $svc, $company, array &$data): void
    {
        try {
            $labels = ['Assigned', 'Started', 'Completed'];
            $donutData = [
                (int)($data['training_assigned'] ?? 0),
                (int)($data['totalTrainingStarted'] ?? 0),
                (int)($data['training_completed'] ?? 0),
            ];
            $chartResult = $svc->generateDonutChartImage($labels, $donutData, $company->company_id);

            if (is_array($chartResult)) {
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
    }

    private function attachRiskDistributionChart(OverallPlatformReport $svc, $company, array &$data): void
    {
        try {
            $riskLabels = ['High Risk', 'Moderate Risk', 'Low Risk'];
            $riskData = [
                (int)($data['riskAnalysis']['in_high_risk'] ?? 0),
                (int)($data['riskAnalysis']['in_moderate_risk'] ?? 0),
                (int)($data['riskAnalysis']['in_low_risk'] ?? 0),
            ];
            $riskColors = ['#ef4444', '#fb923c', '#10b981'];
            $riskChart = $svc->generateDonutChartImage($riskLabels, $riskData, $company->company_id . '_risk', $riskColors);

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
    }

    private function attachRiskScoreChart(OverallPlatformReport $svc, $company, array &$data): void
    {
        try {
            $score = isset($data['riskScore']) && is_numeric($data['riskScore']) ? round(floatval($data['riskScore']), 2) : 0;
            $score = max(0, min(100, $score));

            $riskLabels = ['Risk', 'Remainder'];
            $riskData = [(float)$score, (float)(100 - $score)];
            $riskColors = ['#ef4444', '#e2e8f0'];

            $riskChartOptions = [
                'responsive' => false,
                'maintainAspectRatio' => false,
                'cutout' => '70%',
                'layout' => ['padding' => 0],
                'plugins' => [
                    'legend' => ['display' => false],
                    'tooltip' => ['enabled' => false]
                ],
                'animation' => false,
                'elements' => ['arc' => ['borderWidth' => 0]]
            ];

            $riskDonut = $svc->generateDonutChartImage($riskLabels, $riskData, $company->company_id . '_riskdonut', $riskColors, $riskChartOptions, 280, 280);
            if (is_array($riskDonut)) {
                $data['riskDonutImage'] = $riskDonut['public_url'] ?? null;
                $data['riskDonutImageLocal'] = $riskDonut['local_file'] ?? null;
                $data['riskDonutImageBase64'] = $riskDonut['base64'] ?? null;

                // Mirror to legacy gauge keys so template doesn't break
                $data['riskGaugeImage'] = $data['riskDonutImage'];
                $data['riskGaugeImageLocal'] = $data['riskDonutImageLocal'];
                $data['riskGaugeImageBase64'] = $data['riskDonutImageBase64'];
            } else {
                $data['riskDonutImage'] = null;
                $data['riskDonutImageLocal'] = null;
                $data['riskDonutImageBase64'] = null;
                $data['riskGaugeImage'] = null;
                $data['riskGaugeImageLocal'] = null;
                $data['riskGaugeImageBase64'] = null;
            }
        } catch (\Exception $e) {
            Log::error('Failed generating risk donut for company ' . $company->company_id, ['exception' => $e->getMessage()]);
            $data['riskDonutImage'] = null;
            $data['riskDonutImageLocal'] = null;
            $data['riskDonutImageBase64'] = null;
            $data['riskGaugeImage'] = null;
            $data['riskGaugeImageLocal'] = null;
            $data['riskGaugeImageBase64'] = null;
        }
    }

    private function renderPdfAndSend($company, array $data): void
    {
        // Render the view to HTML first so we can inspect/verify embedding (debugging)
        $html = view('new-overall-report', $data)->render();

        // Log whether base64 exists and HTML contains a data URI (concise)
        try {
            $base64Len = isset($data['donutChartImageBase64']) ? strlen($data['donutChartImageBase64']) : 0;
        } catch (\Throwable $e) {
            $base64Len = 0;
        }
        Log::info("Donut base64 length at render", ['company' => $company->company_id, 'len' => $base64Len, 'has_data_uri' => (strpos($html, 'data:image/png;base64,') !== false)]);

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

        // Save report and send email
        $this->saveReport($company, $pdfContent);
        $this->sendReportEmail($company, $data, $pdfContent);
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

    private function isScheduledForReport($company): bool
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

        // Check if it's exactly the time for the next report
        $currentDate = Carbon::now();
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
}
