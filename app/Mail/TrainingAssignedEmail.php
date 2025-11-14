<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Models\WhiteLabelledSmtp;
use Illuminate\Support\Facades\App;
use App\Models\WhiteLabelledCompany;
use App\Models\CustomTrainingEmail;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class TrainingAssignedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $mailData;
    public $trainingNames;
    private $customTemplateData;

    /**
     * Create a new message instance.
     */
    public function __construct($mailData, $trainingNames)
    {
        $this->mailData = $mailData;
        $this->trainingNames = $trainingNames;

        $language = checkNotificationLanguage($mailData['company_id']);
        if ($language !== 'en') {
            App::setLocale($language);
        }

        // Check for custom template
        $this->customTemplateData = $this->processCustomTemplate();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Use custom subject if available, otherwise use default
        $subject = $this->customTemplateData['subject'] ?? 
                  $this->mailData['company_name'] . ' Training';
        
        $fromName = $this->mailData['company_name'];
        $fromEmail = $this->mailData['company_email'];
        
        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Use custom template if available and enabled
        if ($this->customTemplateData && $this->customTemplateData['is_custom']) {
            return new Content(
                htmlString: $this->customTemplateData['content']
            );
        }

        // Fallback to default blade template
        return new Content(
            view: 'emails.trainingmail',
        );
    }

    /**
     * Process custom training email template with shortcode replacement
     */
    private function processCustomTemplate(): ?array
    {
        try {
            // Check if company has an active custom template
            $customTemplate = CustomTrainingEmail::getActiveTemplateForCompany($this->mailData['company_id']);
            
            if (!$customTemplate) {
                return null; // No custom template, use default
            }

            // Get template content from S3
            $templateContent = $customTemplate->getTemplateContent();
            
            if (!$templateContent) {
                Log::warning('Custom training template found but content could not be retrieved from S3', [
                    'company_id' => $this->mailData['company_id'],
                    'template_id' => $customTemplate->id
                ]);
                return null; // Fallback to default template
            }

            // Prepare training names as formatted list
            $trainingList = $this->formatTrainingList($this->trainingNames->toArray());

            // Replace shortcodes in template content
            $processedContent = $this->replaceShortcodes($templateContent, [
                '{{user_name}}' => $this->mailData['user_name'] ?? '',
                '{{training_link}}' => $this->mailData['learning_site'] ?? '',
                '{{assigned_trainings}}' => $trainingList,
            ]);

            return [
                'subject' => $customTemplate->email_subject,
                'content' => $processedContent,
                'is_custom' => true
            ];

        } catch (\Exception $e) {
            Log::error('Error processing custom training email template', [
                'company_id' => $this->mailData['company_id'],
                'error' => $e->getMessage()
            ]);
            return null; // Fallback to default template
        }
    }

    /**
     * Replace shortcodes in template content
     */
    private function replaceShortcodes(string $template, array $replacements): string
    {
        $content = $template;
        
        foreach ($replacements as $shortcode => $value) {
            $content = str_replace($shortcode, $value, $content);
        }
        
        return $content;
    }

    /**
     * Format training names into an HTML list
     */
    private function formatTrainingList(array $trainingNames): string
    {
        if (empty($trainingNames)) {
            return '<p>No trainings assigned</p>';
        }

        $listItems = array_map(function($training) {
            return '<li>' . htmlspecialchars($training) . '</li>';
        }, $trainingNames);

        return '<ul>' . implode('', $listItems) . '</ul>';
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
