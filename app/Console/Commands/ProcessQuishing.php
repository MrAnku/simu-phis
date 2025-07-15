<?php

namespace App\Console\Commands;

use App\Models\Company;
use Endroid\QrCode\QrCode;
use App\Models\QuishingCamp;
use App\Models\SenderProfile;
use App\Models\OutlookDmiToken;
use Endroid\QrCode\Color\Color;
use Illuminate\Console\Command;
use App\Models\QuishingActivity;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\ErrorCorrectionLevel;

class ProcessQuishing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-quishing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //get all pending quishing campaigns
        $companies = Company::where('service_status', 1)->where('approved', 1)->get();

        if ($companies->isEmpty()) {
            return;
        }
        foreach ($companies as $company) {

            setCompanyTimezone($company->company_id);

            $quishingCampaigns = $company->quishingLiveCamps()
            ->where('sent', '0')
            ->get();

            if ($quishingCampaigns->isEmpty()) {
                continue;
            }
            foreach ($quishingCampaigns as $campaign) {
                //get website url
                $quishingTemplate = $campaign->templateData()->first();
                if ($quishingTemplate->website !== null && $quishingTemplate->sender_profile !== null) {
                    $phishingWebsite = $quishingTemplate->website()->first();
                    $websiteUrl = getWebsiteUrl($phishingWebsite, $campaign, 'qsh');

                    //get qrcode link
                    $qrcodeLink = $this->getQRlink($campaign->user_email, $websiteUrl, $campaign->id);

                    //check if the campaign has sender profile
                    if ($campaign->sender_profile !== null) {
                        $senderProfile = SenderProfile::find($campaign->sender_profile);
                    } else {
                        $senderProfile = $quishingTemplate->senderProfile()->first();
                    }

                    try {
                        //prepare mail body
                        $mailData = $this->prepareMailBody(
                            $campaign,
                            $senderProfile,
                            $quishingTemplate,
                            $qrcodeLink
                        );
                    } catch (\Exception $e) {
                        echo "Error: " . $e->getMessage() . "\n";
                        continue;
                    }


                    //send mail
                    $mailSent = sendPhishingMail($mailData);
                    // $this->sendMailConditionally($mailData, $campaign, $campaign->company_id);
                    if ($mailSent) {

                        QuishingActivity::where('campaign_live_id', $campaign->id)->update(['email_sent_at' => now()]);

                        echo "Mail sent to {$campaign->user_email} \n";
                        $campaign->sent = '1';
                        $campaign->save();
                    }
                }
            }
        }

        $this->checkCompletedCampaigns();
    }

    private function sendMailConditionally($mailData, $campaign, $company_id)
    {
        // check user email domain is outlook email
        $isOutlookEmail = checkIfOutlookDomain($campaign->user_email);
        if ($isOutlookEmail) {
            echo "Outlook email detected: " . $campaign->user_email . "\n";
            $accessToken = OutlookDmiToken::where('company_id', $company_id)->first();
            if ($accessToken) {
                echo "Access token found for company ID: " . $company_id . "\n";

                $sent = sendMailUsingDmi($accessToken->access_token, $mailData);
                if ($sent['success'] == true) {
                    $activity = QuishingActivity::where('campaign_live_id', $campaign->id)->update(['email_sent_at' => now()]);

                    echo "Email sent to: " . $campaign->user_email . "\n";
                } else {
                    echo "Email not sent to: " . $campaign->user_email . "\n";
                }
            } else {
                echo "No access token found for company ID: " . $company_id . "\n";
                if (sendPhishingMail($mailData)) {

                    $activity = QuishingActivity::where('campaign_live_id', $campaign->id)->update(['email_sent_at' => now()]);

                    echo "Email sent to: " . $campaign->user_email . "\n";
                } else {
                    echo "Email not sent to: " . $campaign->user_email . "\n";
                }
            }
        } else {
            echo "Non-Outlook email detected: " . $campaign->user_email . "\n";
            if (sendPhishingMail($mailData)) {

                $activity = QuishingActivity::where('campaign_live_id', $campaign->id)->update(['email_sent_at' => now()]);

                echo "Email sent to: " . $campaign->user_email . "\n";
            } else {
                echo "Email not sent to: " . $campaign->user_email . "\n";
            }
        }
    }

    private function prepareMailBody($campaign, $senderProfile, $quishingMaterial, $qrcodeUrl)
    {

        // $mailBody = Storage::disk('s3')->get($quishingMaterial->file);
        $mailBody = file_get_contents(env('CLOUDFRONT_URL') . $quishingMaterial->file);

        // if failed to open stream
        if ($mailBody === false) {
            echo "Failed to open stream for mail body file: " . $quishingMaterial->file . "\n";
            return false;
        }

        $mailBody = str_replace('{{user_name}}', $campaign->user_name, $mailBody);
        $mailBody = str_replace('{{qr_code}}', '<img src="' . $qrcodeUrl . '" alt="qr_code" width="300" height="300">', $mailBody);


        if ($campaign->quishing_lang !== 'en' && $campaign->quishing_lang !== 'am') {

            $mailBody = changeEmailLang($mailBody, $campaign->quishing_lang);
        }

        if ($campaign->quishing_lang == 'am') {

            $mailBody = translateHtmlToAmharic($mailBody);
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
        ];

        return $mailData;
    }

    private function getQRlink($email, $redirectUrl, $campLiveId)
    {
        $email = $email; // Get email from request
        $redirectUrl = $redirectUrl; // Generate unique redirect link

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



    private function checkCompletedCampaigns()
    {
        $campaigns = QuishingCamp::where('status', 'running')->get();
        if (!$campaigns) {
            return;
        }
        foreach ($campaigns as $campaign) {
            $campaignLive = $campaign->campLive()->where('sent', '0')->count();
            if ($campaignLive == 0) {
                $campaign->status = 'completed';
                $campaign->save();
            }
        }
    }
}
