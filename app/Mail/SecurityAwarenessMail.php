<?php

namespace App\Mail;

use App\Models\AwarenessEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Models\Users;

class SecurityAwarenessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $memberEmail;
    public $memberName;
    /**
     * Data passed to the view
     *
     * @var array
     */
    public $contentData = [];
    /**
     * Template subject (if found)
     * @var string|null
     */
    protected $templateSubject;

    /**
     * Create a new message instance.
     */
    public function __construct($memberEmail, $memberName)
    {
        $this->memberEmail = $memberEmail;
        $this->memberName = $memberName;

        $this->getRandomTemp();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->templateSubject ?? 'Security Awareness Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.security-awareness-mail',
            with: [
                'contentData' => $this->contentData,
                'subject' => $this->templateSubject ?? 'Security Awareness Mail',
            ],
        );
    }

    private function getRandomTemp()
    {
        // Get the random template from DB
        $template = AwarenessEmailTemplate::inRandomOrder()->first();

        if ($template) {

            // Stored body is HTML. Replace simple placeholder for user name if available.
            $body = $template->body;

            if (!empty($this->memberEmail) && !empty($this->memberName)) {
                $memberName = $this->memberName;
                // Replace common placeholder variants with member name
                $body = str_replace('{{user_name}}', $memberName, $body);
            }

            // Keep contentData as array with 'body' key so view access remains stable.
            $this->contentData = $body;
            $this->templateSubject = $template->subject;
        } else {
            // No template found — use a default HTML fallback similar to templates
            $this->templateSubject = 'Security Tips: Keep Your Inbox Safe';
            $body = '<p>Dear {{user_name}},</p>' .
                '<p>We wanted to share some helpful tips to keep your email communication safe and secure. These simple practices will help you navigate your inbox confidently.</p>' .
                '<p><strong>Check the Sender Address:</strong><br>' .
                'Take a moment to verify the sender\'s email address before clicking any links. This simple step can prevent many security issues.</p>' .
                '<p><strong>Verify Unusual Emails:</strong><br>' .
                'If an email seems unusual or unexpected, it\'s okay to verify with the sender through a different channel like phone or instant message.</p>' .
                '<p><strong>Forward Suspicious Emails:</strong><br>' .
                'Our IT team is always here to help. Feel free to forward any questionable emails to us for verification.</p>' .
                '<p><strong>Separate Personal and Work:</strong><br>' .
                'Keep personal and work information separate in your communications to maintain security boundaries.</p>' .
                '<p>Remember, staying safe online is simply about being mindful. You\'re doing great!</p>' .
                '<p>Warm regards,<br>simUphish Team</p>';

            // If we have member name, replace placeholder
            if (!empty($this->memberName)) {
                $body = str_replace(['{{user_name}}','{user_name}','[[user_name]]'], $this->memberName, $body);
            }

            $this->contentData = $body;
        }
        // No placeholder replacement: render the stored HTML exactly as saved in DB.
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
