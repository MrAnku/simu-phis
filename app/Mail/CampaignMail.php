<?php

namespace App\Mail;

use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;

class CampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public $mailData;

    /**
     * Create a new message instance.
     */
    public function __construct($mailData)
    {
        //
        $this->mailData = $mailData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                $this->mailData['from_email'], 
                $this->mailData['from_name']
            ),
            subject: $this->mailData['email_subject'],
            replyTo: [new Address(Str::random(6) . '@suspend.page')],
        );
    }

    /**
     * Get the message content definition.
     */
    // public function content(): Content
    // {
    //     return new Content(
    //         html: $this->mailData['mailBody'],
    //     );
    // }

    public function build()
    {
        return $this->from($this->mailData['from_email'], $this->mailData['from_name'])
            ->subject($this->mailData['email_subject'])
            ->html($this->mailData['mailBody']);
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
