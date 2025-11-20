<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\App;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use App\Services\CheckWhitelabelService;
use Illuminate\Contracts\Queue\ShouldQueue;

class PolicyCampaignEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $mailData;
    public $companyName;
    public $companyLogo;
    public $learnDomain;

    /**
     * Create a new message instance.
     */
    public function __construct($mailData)
    {
        //
        $this->mailData = $mailData;

        $language = checkNotificationLanguage($mailData['company_id']);
        if ($language !== 'en') {
            App::setLocale($language);
        }
        $this->checkWhiteLabel();
    }

    private function checkWhiteLabel()
    {
       
        $branding = new CheckWhitelabelService($this->mailData['company_id']);
        $this->companyName = $branding->companyName();
        $this->companyLogo = $branding->companyDarkLogo();
        $this->learnDomain = $branding->learningPortalDomain();
        if ($branding->isCompanyWhitelabeled()) {
          
            $branding->updateSmtpConfig();
        }else{
            $branding->clearSmtpConfig();
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New policy has been assigned',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.policy-camp-mail',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
