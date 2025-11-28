<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CreateMsspCompanyMail extends Mailable
{
    use Queueable, SerializesModels;

    public $company;
    public $pass_create_link;

    /**
     * Create a new message instance.
     */
    public function __construct($company, $pass_create_link)
    {
        $this->company = $company;
        $this->pass_create_link = $pass_create_link;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ' . env('APP_NAME', 'SimuPhish')
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.createMsspCompany',
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
