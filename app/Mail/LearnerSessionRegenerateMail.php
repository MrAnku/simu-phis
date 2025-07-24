<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\App;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class LearnerSessionRegenerateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $mailData;
    public $trainingModules;

    /**
     * Create a new message instance.
     */
    public function __construct($mailData, $trainingModules)
    {
        $this->mailData = $mailData;
        $this->trainingModules = $trainingModules;

        $language = checkNotificationLanguage($mailData['company_id']);
        if ($language !== 'en') {
            App::setLocale($language);
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Training Session Regenerated',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.learner_session_regenerate_mail',
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
