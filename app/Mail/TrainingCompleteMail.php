<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Contracts\Queue\ShouldQueue;

class TrainingCompleteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $mailData;
    public $pdfContent;
    /**
     * Create a new message instance.
     */
    public function __construct($mailData, $pdfContent)
    {
        $this->mailData = $mailData;
        $this->pdfContent = $pdfContent;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Training Complete Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.training-complete-mail',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
   public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, 'certificate.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
