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
        $this->companyName = env('APP_NAME');
        $this->companyLogo = env('CLOUDFRONT_URL') . "/assets/images/simu-logo-dark.png";
        $this->learnDomain = env('SIMUPHISH_LEARNING_URL');

        $isWhitelabeled = new CheckWhitelabelService($this->mailData['company_id']);
        if ($isWhitelabeled->isCompanyWhitelabeled()) {
            $whiteLableData = $isWhitelabeled->getWhiteLabelData();
            $this->companyName = $whiteLableData->company_name;
            $this->companyLogo = env('CLOUDFRONT_URL') . $whiteLableData->dark_logo;
            $this->learnDomain = "https://" . $whiteLableData->learn_domain;
            $isWhitelabeled->updateSmtpConfig();
        }else{
            $isWhitelabeled->clearSmtpConfig();
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
