<?php

namespace App\Mail\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyManagementMail extends Mailable
{
    use Queueable, SerializesModels;

    public $company;
    public $type;
    public $subject;
    public $view;
    public $ccMailAddress;

    /**
     * Create a new message instance.
     */
    public function __construct($company, $type = 'default', $ccMailAddress)
    {
        $this->company = $company;
        $this->type = $type;
        $this->ccMailAddress = $ccMailAddress;
        if($type === 'license_expired') {
            $this->subject = "License Expiration Notification";
            $this->view = 'emails.admin.license_expired';
        }
        if($type === 'need_support') {
            $this->subject = "Need Support?";
            $this->view = 'emails.admin.need_support';
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
            cc: [$this->ccMailAddress, $this->company->partner?->email]
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: $this->view,
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
