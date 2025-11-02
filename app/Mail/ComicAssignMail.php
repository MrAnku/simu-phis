<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComicAssignMail extends Mailable
{
    use Queueable, SerializesModels;

    public $userName;
    public $companyName;
    public $logo;
    public $learningPortalUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($userName, $companyName, $logo, $learningPortalUrl)
    {
        $this->userName = $userName;
        $this->companyName = $companyName;
        $this->logo = $logo;
        $this->learningPortalUrl = $learningPortalUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Comic Assigned...',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.comic_assign',
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
