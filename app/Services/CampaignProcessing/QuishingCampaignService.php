<?php

namespace App\Services\CampaignProcessing;

use Carbon\Carbon;
use App\Models\Company;
use App\Models\QuishingCamp;
use App\Models\QuishingActivity;
use App\Models\QuishingLiveCamp;
use App\Models\SenderProfile;
use App\Models\OutlookDmiToken;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\ErrorCorrectionLevel;
use Illuminate\Support\Facades\Log;

/**
 * Service class to handle quishing campaign processing logic
 */
class QuishingCampaignService
{
    /**
     * Process individual quishing live campaign - send quishing email with QR code
     *
     * @param QuishingLiveCamp $liveCamp
     * @return void
     */
    public function processLiveCampaign(QuishingLiveCamp $liveCamp): void
    {
        // Get template and website
        $quishingTemplate = $liveCamp->templateData()->first();
        if ($quishingTemplate === null || $quishingTemplate->website === null) {
            return;
        }

        $phishingWebsite = $quishingTemplate->website()->first();
        $websiteUrl = getWebsiteUrl($phishingWebsite, $liveCamp, 'qsh');

        // Get QR code link
        $qrcodeLink = $this->getQRlink($liveCamp->user_email, $websiteUrl, $liveCamp->id);

        // Check if the campaign has sender profile
        if ($liveCamp->sender_profile !== null) {
            $senderProfile = SenderProfile::find($liveCamp->sender_profile);
        } else {
            $senderProfile = $quishingTemplate->senderProfile()->first();
        }

        $mailData = $this->prepareMailBody(
            $liveCamp,
            $senderProfile,
            $quishingTemplate,
            $qrcodeLink
        );

        // Send mail
        $mailSent = sendPhishingMail($mailData);

        if ($mailSent) {
            QuishingActivity::where('campaign_live_id', $liveCamp->id)
                ->update(['email_sent_at' => now()]);
            echo "Mail sent to {$liveCamp->user_email} \n";
            $liveCamp->sent = '1';
            $liveCamp->save();
        }
    }

    /**
     * Prepare email body with QR code and personalization
     *
     * @param QuishingLiveCamp $campaign
     * @param SenderProfile $senderProfile
     * @param mixed $quishingMaterial
     * @param string $qrcodeUrl
     * @return array|false
     */
    private function prepareMailBody($campaign, $senderProfile, $quishingMaterial, $qrcodeUrl)
    {
        $mailBody = file_get_contents(env('CLOUDFRONT_URL') . $quishingMaterial->file);

        // If failed to open stream
        if ($mailBody === false) {
            echo "Failed to open stream for mail body file: " . $quishingMaterial->file . "\n";
            return false;
        }

        $companyName = Company::where('company_id', $campaign->company_id)->value('company_name');

        // Replace QR code placeholder
        $mailBody = str_replace(
            '{{qr_code}}',
            '<img src="' . $qrcodeUrl . '" alt="qr_code" width="300" height="300">' .
                '<input type="hidden" id="campaign_id" value="' . $campaign->campaign_id . '">' .
                '<input type="hidden" id="campaign_type" value="quishing">',
            $mailBody
        );

        // Handle language translations
        if ($campaign->quishing_lang !== 'en' && $campaign->quishing_lang !== 'am') {
            $mailBody = str_replace('{{user_name}}', '<p id="user_name"></p>', $mailBody);
            $mailBody = str_replace('{{company_name}}', '<p id="company_name"></p>', $mailBody);
            $mailBody = changeEmailLang($mailBody, $campaign->quishing_lang);
            $mailBody = str_replace('<p id="user_name"></p>', $campaign->user_name, $mailBody);
            $mailBody = str_replace('<p id="company_name"></p>', $companyName, $mailBody);
        } else if ($campaign->quishing_lang == 'am') {
            $mailBody = str_replace('{{user_name}}', '<p id="user_name"></p>', $mailBody);
            $mailBody = str_replace('{{company_name}}', '<p id="company_name"></p>', $mailBody);
            $mailBody = translateHtmlToAmharic($mailBody);
            $mailBody = str_replace('<p id="user_name"></p>', $campaign->user_name, $mailBody);
            $mailBody = str_replace('<p id="company_name"></p>', $companyName, $mailBody);
        } else {
            $mailBody = str_replace('{{user_name}}', $campaign->user_name, $mailBody);
            $mailBody = str_replace('{{company_name}}', $companyName, $mailBody);
        }

        $mailData = [
            'email' => $campaign->user_email,
            'from_name' => $senderProfile->from_name,
            'email_subject' => $quishingMaterial->email_subject,
            'mailBody' => $mailBody,
            'from_email' => $senderProfile->from_email,
            'sendMailHost' => $senderProfile->host,
            'sendMailUserName' => $senderProfile->username,
            'sendMailPassword' => $senderProfile->password,
            'company_id' => $campaign->company_id,
            'campaign_id' => $campaign->campaign_id,
            'campaign_type' => 'quishing'
        ];

        return $mailData;
    }

    /**
     * Generate QR code and return its URL
     *
     * @param string $email
     * @param string $redirectUrl
     * @param int $campLiveId
     * @return string
     */
    private function getQRlink($email, $redirectUrl, $campLiveId): string
    {
        $qrCode = new QrCode(
            data: $redirectUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );

        // Convert QR Code to PNG
        $writer = new PngWriter();
        $qrCodeImage = $writer->write($qrCode);
        $fileName = uniqid() . '.png'; // Unique filename

        $storagePath = storage_path('app/qrcodes');
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
        $filePath = $storagePath . '/' . $fileName;
        file_put_contents($filePath, $qrCodeImage->getString());

        // Get Public URL
        $qrCodeUrl = asset('qrcodes/' . $fileName . '?eid=' . $campLiveId);

        return $qrCodeUrl;
    }

    /**
     * Send mail conditionally based on email domain (Outlook vs others)
     * Kept for future use with Outlook DMI token integration
     *
     * @param array $mailData
     * @param QuishingLiveCamp $campaign
     * @param int $company_id
     * @return void
     */
    public function sendMailConditionally(array $mailData, QuishingLiveCamp $campaign, int $company_id): void
    {
        $sent = false;
        // Check user email domain is outlook email
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

        QuishingActivity::where('campaign_live_id', $campaign->id)
            ->update(['email_sent_at' => now()]);
    }

    /**
     * Relaunch recurring quishing campaigns (weekly/monthly/quarterly)
     *
     * @return void
     */
    public function relaunchRecurringCampaigns(): void
    {
        $completedRecurring = QuishingCamp::where('status', 'completed')
            ->whereIn('email_freq', ['weekly', 'monthly', 'quarterly'])
            ->get();

        foreach ($completedRecurring as $recurr) {
            try {
                if (!empty($recurr->launch_date)) {
                    $lastLaunch = Carbon::parse($recurr->launch_date)->startOfDay();
                } else {
                    Log::error("ProcessQuishing: no launch_date for campaign {$recurr->campaign_id}");
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
                        Log::error("ProcessQuishing: failed to parse expire_after for campaign {$recurr->campaign_id}: " . $e->getMessage());
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

                    echo "Relaunching Quishing campaign: {$recurr->campaign_name}\n";
                    $this->resetLiveCampaigns($recurr->campaign_id, $nextLaunch);
                }
            } catch (\Exception $e) {
                Log::error("Error relaunching quishing campaign {$recurr->campaign_id}: " . $e->getMessage());
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
        $liveRows = QuishingLiveCamp::where('campaign_id', $campaignId)->get();

        foreach ($liveRows as $live) {
            try {
                // Preserve the existing time-of-day for each send_time, only update the date
                $currentSend = Carbon::parse($live->send_time);
                $newSend = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $nextLaunch->toDateString() . ' ' . $currentSend->format('H:i:s')
                );

                $live->update([
                    'sent' => '0',
                    'mail_open' => '0',
                    'qr_scanned' => '0',
                    'compromised' => '0',
                    'email_reported' => '0',
                    'training_assigned' => '0',
                    'send_time' => $newSend,
                ]);
            } catch (\Exception $e) {
                // Fallback: if parsing fails, use nextLaunch at startOfDay
                try {
                    $live->update([
                        'sent' => '0',
                        'mail_open' => '0',
                        'qr_scanned' => '0',
                        'compromised' => '0',
                        'email_reported' => '0',
                        'training_assigned' => '0',
                        'send_time' => $nextLaunch->copy()->startOfDay(),
                    ]);
                } catch (\Exception $e) {
                    Log::error("Failed to reset quishing live campaign {$live->id}: " . $e->getMessage());
                }
            }
        }
    }
}
