<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CreateSubAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public $company;
    public $companyName;
    public $companyLogo;
    public $portalDomain;
    public $pass_create_link;

    /**
     * Create a new message instance.
     */
    public function __construct($company, $companyName, $companyLogo, $portalDomain, $pass_create_link)
    {
        $this->company = $company;
        $this->companyName = $companyName;
        $this->companyLogo = $companyLogo;
        $this->portalDomain = $portalDomain;
        $this->pass_create_link = $pass_create_link;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ' . $this->companyName . ' as Sub Admin',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.createSubAdmin',
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
