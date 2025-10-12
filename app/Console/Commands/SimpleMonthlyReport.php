<?php

namespace App\Console\Commands;

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
use App\Models\WaLiveCampaign;
use App\Services\CompanyReport;
use App\Services\Reports\OverallNormalEmployeeReport;
use App\Services\Simulations\EmailCampReport;
use App\Services\Simulations\QuishingCampReport;
use App\Services\Simulations\VishingCampReport;
use App\Services\Simulations\WhatsappCampReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

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

            // print_r($data);
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
}
