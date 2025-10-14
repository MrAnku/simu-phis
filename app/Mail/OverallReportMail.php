<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class OverallReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reportData;
    public $pdfContent;
    public $companyName;

    public function __construct($reportData, $pdfContent)
    {
        $this->reportData = $reportData;
        $this->pdfContent = $pdfContent;
        $this->companyName = $reportData['company_name'];
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Overall Platform Report",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails/overall-report-email',
            with: [
                'company_name' => $this->companyName,
                'total_users' => $this->reportData['total_users'],
                'campaigns_sent' => $this->reportData['campaigns_sent'],
                'emails_sent' => $this->reportData['emails_sent'],
                'payload_clicked' => $this->reportData['payload_clicked'],
                'click_rate' => $this->reportData['click_rate'],
                'training_assigned' => $this->reportData['training_assigned'],
                'training_completed' => $this->reportData['training_completed'],
                // 'total_Policies' => $this->reportData['total_Policies'],
                'assigned_Policies' => $this->reportData['assigned_policies'],
                'acceptance_Policies' => $this->reportData['accepted_policies'],
                'riskScore' => $this->reportData['riskScore'],
                'blue_collar_employees' => $this->reportData['blue_collar_employees'],
                'email_camp_data' => $this->reportData['email_camp_data'],
                'quish_camp_data' => $this->reportData['quish_camp_data'],
                'wa_camp_data' => $this->reportData['wa_camp_data'],
                'ai_camp_data' => $this->reportData['ai_camp_data'],
                'most_compromised_employees' => $this->reportData['most_compromised_employees'],
                'most_clicked_emp' => $this->reportData['most_clicked_emp'],
                'phish_clicks_weekly' => $this->reportData['phish_clicks_weekly'],
                'avg_scores' => $this->reportData['avg_scores'],
                'riskAnalysis' => $this->reportData['riskAnalysis'],
                'certifiedUsers' => $this->reportData['certifiedUsers'],
                'totalTrainingStarted' => $this->reportData['totalTrainingStarted'],
                'totalBadgesAssigned' => $this->reportData['totalBadgesAssigned'],
                'trainingStatusDistribution' => $this->reportData['trainingStatusDistribution'],
                'wa_events_over_time' => $this->reportData['wa_events_over_time'],
            ]
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, "overall-report.pdf")
                ->withMime('application/pdf'),
        ];
    }
}