<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BreachAlertAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public $company;
    public $employee;
    public $breachData;
    /**
     * Create a new message instance.
     */
    public function __construct($company, $employee, $breachData)
    {
        $this->company = $company;
        $this->employee = $employee;
        $this->breachData = $breachData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Email Breach found for - ' . $this->employee->user_email,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.breach_admin_mail',
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
