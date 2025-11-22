<?php

namespace App\Services\CampaignProcessing;

use Carbon\Carbon;
use App\Models\Company;
use App\Models\Campaign;
use App\Models\CampaignLive;
use App\Models\PhishingEmail;
use App\Models\PhishingWebsite;
use App\Models\SenderProfile;
use App\Models\EmailCampActivity;
use App\Models\OutlookDmiToken;
use Illuminate\Support\Facades\Log;
use App\Services\CampaignTrainingService;
use App\Services\PolicyAssignedService;

/**
 * Service class to handle email campaign processing logic
 */
class EmailCampaignService
{
    /**
     * Process individual campaign live record - send training, policies, or phishing email
     *
     * @param CampaignLive $liveCampaign
     * @return void
     */
    public function processLiveCampaign(CampaignLive $liveCampaign): void
    {
        // Send training if no phishing material
        if ($liveCampaign->phishing_material == null) {
            $this->sendOnlyTraining($liveCampaign);
        }

        // Assign policies if configured
        if ($liveCampaign->phishing_material == null && $liveCampaign->camp?->policies != null) {
            $this->assignPolicies($liveCampaign);
        }

        // Send phishing email if material is configured
        if ($liveCampaign->phishing_material) {
            $this->sendPhishingEmail($liveCampaign);
        }
    }

    /**
     * Send training assignment email
     *
     * @param CampaignLive $campaign
     * @return void
     */
    private function sendOnlyTraining(CampaignLive $campaign): void
    {
        $all_camp = Campaign::where('campaign_id', $campaign->campaign_id)->first();

        if ($all_camp->training_assignment == 'all') {
            $trainingModules = [];
            $scormTrainings = [];

            if ($all_camp->training_module !== null) {
                $trainingModules = json_decode($all_camp->training_module, true);
            }

            if ($all_camp->scorm_training !== null) {
                $scormTrainings = json_decode($all_camp->scorm_training, true);
            }

            $sent = CampaignTrainingService::assignTraining($campaign, $trainingModules, false, $scormTrainings);

            if ($sent) {
                echo 'Training assigned successfully to ' . $campaign->user_email . "\n";
            } else {
                echo 'Failed to assign training to ' . $campaign->user_email . "\n";
            }

            $campaign->update(['sent' => 1, 'training_assigned' => 1]);
        } else {
            // Random assignment
            $sent = CampaignTrainingService::assignTraining($campaign);

            if ($sent) {
                echo 'Training assigned successfully to ' . $campaign->user_email . "\n";
            } else {
                echo 'Failed to assign training to ' . $campaign->user_email . "\n";
            }
            $campaign->update(['sent' => 1, 'training_assigned' => 1]);
        }
    }

    /**
     * Assign policies to campaign user
     *
     * @param CampaignLive $campaign
     * @return void
     */
    private function assignPolicies(CampaignLive $campaign): void
    {
        try {
            $policyService = new PolicyAssignedService(
                $campaign->campaign_id,
                $campaign->user_name,
                $campaign->user_email,
                $campaign->company_id
            );

            $policyService->assignPolicies($campaign->camp->policies);
        } catch (\Exception $e) {
            echo "Error assigning policy: " . $e->getMessage() . "\n";
            Log::error("Error assigning policy: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send phishing email to campaign user
     *
     * @param CampaignLive $campaign
     * @return void
     */
    private function sendPhishingEmail(CampaignLive $campaign): void
    {
        if (!$campaign->phishing_material) {
            return;
        }

        $phishingMaterial = PhishingEmail::where('id', $campaign->phishing_material)
            ->where('website', '!=', 0)
            ->where('senderProfile', '!=', 0)
            ->first();

        if (!$phishingMaterial) {
            return;
        }

        // Determine sender profile
        if ($campaign->sender_profile !== null) {
            $senderProfile = SenderProfile::find($campaign->sender_profile);
        } else {
            $senderProfile = SenderProfile::find($phishingMaterial->senderProfile);
        }

        $website = PhishingWebsite::find($phishingMaterial->website);

        if (!$senderProfile || !$website) {
            echo "Sender profile or website is not associated with the phishing material.\n";
            return;
        }

        // Prepare email body with tracking
        $mailBody = $this->prepareMailBody($website, $phishingMaterial, $campaign);

        $mailData = [
            'email' => $campaign->user_email,
            'from_name' => $senderProfile->from_name,
            'email_subject' => $phishingMaterial->email_subject,
            'mailBody' => $mailBody,
            'from_email' => $senderProfile->from_email,
            'sendMailHost' => $senderProfile->host,
            'sendMailUserName' => $senderProfile->username,
            'sendMailPassword' => $senderProfile->password,
            'company_id' => $campaign->company_id,
            'campaign_id' => $campaign->campaign_id,
            'campaign_type' => 'email'
        ];

        if (sendPhishingMail($mailData)) {
            EmailCampActivity::where('campaign_live_id', $campaign->id)
                ->update(['email_sent_at' => now()]);

            echo "Email sent to: " . $campaign->user_email . "\n";
        } else {
            echo "Email not sent to: " . $campaign->user_email . "\n";
            throw new \Exception("Failed to send email to " . $campaign->user_email);
        }

        $campaign->update(['sent' => 1]);
    }

    /**
     * Prepare email body with tracking pixels and personalization
     *
     * @param PhishingWebsite $website
     * @param PhishingEmail $phishingMaterial
     * @param CampaignLive $campaign
     * @return string
     */
    private function prepareMailBody(PhishingWebsite $website, PhishingEmail $phishingMaterial, CampaignLive $campaign): string
    {
        $websiteUrl = getWebsiteUrl($website, $campaign);

        try {
            $mailBody = file_get_contents(env('CLOUDFRONT_URL') . $phishingMaterial->mailBodyFilePath);
        } catch (\Exception $e) {
            echo "Error fetching mail body: " . $e->getMessage() . "\n";
            throw $e;
        }

        $companyName = Company::where('company_id', $campaign->company_id)->value('company_name');

        // Replace template variables
        $mailBody = str_replace('{{website_url}}', $websiteUrl, $mailBody);
        $mailBody = str_replace(
            '{{tracker_img}}',
            '<img src="' . env('APP_URL') . '/trackEmailView/' . $campaign->id . '" alt="" width="1" height="1" style="display:none;">' .
            '<input type="hidden" id="campaign_id" value="' . $campaign->campaign_id . '">' .
            '<input type="hidden" id="campaign_type" value="email">',
            $mailBody
        );

        // Handle language translations
        if ($campaign->email_lang !== 'en' && $campaign->email_lang !== 'am') {
            $mailBody = str_replace('{{user_name}}', '<p id="user_name"></p>', $mailBody);
            $mailBody = str_replace('{{company_name}}', '<p id="company_name"></p>', $mailBody);
            $mailBody = changeEmailLang($mailBody, $campaign->email_lang);
            $mailBody = str_replace('<p id="user_name"></p>', $campaign->user_name, $mailBody);
            $mailBody = str_replace('<p id="company_name"></p>', $companyName, $mailBody);
        } else if ($campaign->email_lang == 'am') {
            $mailBody = str_replace('{{user_name}}', '<p id="user_name"></p>', $mailBody);
            $mailBody = str_replace('{{company_name}}', '<p id="company_name"></p>', $mailBody);
            $mailBody = translateHtmlToAmharic($mailBody);
            $mailBody = str_replace('<p id="user_name"></p>', $campaign->user_name, $mailBody);
            $mailBody = str_replace('<p id="company_name"></p>', $companyName, $mailBody);
        } else {
            $mailBody = str_replace('{{user_name}}', $campaign->user_name, $mailBody);
            $mailBody = str_replace('{{company_name}}', $companyName, $mailBody);
        }

        return $mailBody;
    }

    /**
     * Relaunch recurring campaigns (weekly/monthly/quarterly)
     *
     * @return void
     */
    public function relaunchRecurringCampaigns(): void
    {
        $completedRecurring = Campaign::where('status', 'completed')
            ->whereIn('email_freq', ['weekly', 'monthly', 'quarterly'])
            ->get();

        foreach ($completedRecurring as $recurr) {
            try {
                if (!empty($recurr->launch_date)) {
                    $lastLaunch = Carbon::parse($recurr->launch_date)->startOfDay();
                } else {
                    continue;
                }

                $nextLaunch = $lastLaunch->copy();

                switch ($recurr->email_freq) {
                    case 'weekly':
                        $nextLaunch->addWeek();
                        break;
                    case 'monthly':
                        $nextLaunch->addMonth();
                        break;
                    case 'quarterly':
                        $nextLaunch->addMonths(3);
                        break;
                    default:
                        continue 2;
                }

                // Check expiry
                if ($recurr->expire_after !== null) {
                    try {
                        $expireAt = Carbon::parse($recurr->expire_after);
                    } catch (\Exception $e) {
                        Log::error("Failed to parse expire_after for campaign {$recurr->campaign_id}: " . $e->getMessage());
                        $recurr->update(['status' => 'completed']);
                        continue;
                    }

                    if ($nextLaunch->greaterThanOrEqualTo($expireAt)) {
                        continue;
                    }
                }

                if (Carbon::now()->greaterThanOrEqualTo($nextLaunch)) {
                    $recurr->update([
                        'launch_date' => $nextLaunch->toDateString(),
                        'status' => 'running',
                    ]);

                    echo "Relaunching Email campaign: {$recurr->campaign_name}\n";
                    $this->resetLiveCampaigns($recurr->campaign_id, $nextLaunch);
                }
            } catch (\Exception $e) {
                Log::error("Error relaunching campaign {$recurr->campaign_id}: " . $e->getMessage());
                continue;
            }
        }
    }

    /**
     * Reset live campaign records for relaunching
     *
     * @param int $campaignId
     * @param Carbon $nextLaunch
     * @return void
     */
    private function resetLiveCampaigns(int $campaignId, Carbon $nextLaunch): void
    {
        $liveRows = CampaignLive::where('campaign_id', $campaignId)->get();

        foreach ($liveRows as $live) {
            try {
                // Preserve the existing time-of-day for each send_time, only update the date
                $currentSendTime = Carbon::parse($live->send_time);
                $newSendTime = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $nextLaunch->toDateString() . ' ' . $currentSendTime->format('H:i:s')
                );

                $live->update([
                    'sent' => 0,
                    'mail_open' => 0,
                    'payload_clicked' => 0,
                    'emp_compromised' => 0,
                    'email_reported' => 0,
                    'training_assigned' => 0,
                    'send_time' => $newSendTime,
                ]);
            } catch (\Exception $e) {
                // Fallback: if parsing fails, use nextLaunch at startOfDay
                try {
                    $live->update([
                        'sent' => 0,
                        'mail_open' => 0,
                        'payload_clicked' => 0,
                        'emp_compromised' => 0,
                        'email_reported' => 0,
                        'training_assigned' => 0,
                        'send_time' => $nextLaunch->copy()->startOfDay(),
                    ]);
                } catch (\Exception $e) {
                    Log::error("Failed to reset live campaign {$live->id}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Send mail conditionally based on email domain (Outlook vs others)
     * Kept for future use with Outlook DMI token integration
     *
     * @param array $mailData
     * @param CampaignLive $campaign
     * @param int $company_id
     * @return void
     */
    public function sendMailConditionally(array $mailData, CampaignLive $campaign, int $company_id): void
    {
        $sent = false;
        // check user email domain is outlook email
        $isOutlookEmail = checkIfOutlookDomain($campaign->user_email);
        
        if ($isOutlookEmail) {
            echo "Outlook email detected: " . $campaign->user_email . "\n";

            $accessToken = OutlookDmiToken::where('company_id', $company_id)
                ->where('created_at', '>', now()->subMinutes(60))->first();
            
            if ($accessToken) {
                echo "Access token found for company ID: " . $company_id . "\n";

                $sent = sendMailUsingDmi($accessToken->access_token, $mailData);
                if ($sent['success'] == true) {
                    $sent = true;
                } else {
                    $sent = false;
                }
            } else {
                OutlookDmiToken::where('company_id', $company_id)->delete();
                echo "Access token expired or not found for company ID: " . $company_id . "\n";
                
                if (sendPhishingMail($mailData)) {
                    $sent = true;
                } else {
                    $sent = false;
                }
            }
        } else {
            echo "Non-Outlook email detected: " . $campaign->user_email . "\n";
            
            if (sendPhishingMail($mailData)) {
                $sent = true;
            } else {
                $sent = false;
            }
        }

        if ($sent) {
            echo "Email sent successfully to: " . $campaign->user_email . "\n";
        } else {
            echo "Email not sent to: " . $campaign->user_email . "\n";
        }

        EmailCampActivity::where('campaign_live_id', $campaign->id)
            ->update(['email_sent_at' => now()]);
    }
}