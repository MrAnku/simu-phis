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

    public $pdfContent;
    public $companyName;

    public function __construct($reportData, $pdfContent)
    {
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
                'company_name' => $this->companyName
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