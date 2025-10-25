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
use App\Models\QuishingLiveCamp;
use App\Models\Users;
use App\Models\UsersGroup;
use Carbon\Carbon;
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
        $companies = Company::where('service_status', 1)
            ->where('approved', 1)
            ->where('role', null)
            ->get();

        if ($companies->isEmpty()) {
            return;
        }
        foreach ($companies as $company) {
            try {
                setCompanyTimezone($company->company_id);

                $campaigns = QuishingCamp::where('status', 'pending')
                    ->where('company_id', $company->company_id)
                    ->get();

                if ($campaigns) {
                    foreach ($campaigns as $campaign) {
                        $scheduleDate = Carbon::parse($campaign->schedule_date);

                        $currentDate = Carbon::today();

                        if ($scheduleDate->lte($currentDate)) {

                            $this->makeCampaignLive($campaign->campaign_id);

                            $campaign->update(['status' => 'running']);
                        }
                    }
                }
               
                $runningCampaigns = QuishingCamp::where('company_id', $company->company_id)
                    ->where('status', 'running')
                    ->get();

                if ($runningCampaigns->isEmpty()) {
                    continue;
                }

                foreach ($runningCampaigns as $camp) {
                    $campaignTimezone = $camp->time_zone ?: $company->company_settings->time_zone;

                    // Set process timezone to campaign timezone so Carbon::now() returns campaign-local time
                    date_default_timezone_set($campaignTimezone);
                    config(['app.timezone' => $campaignTimezone]);

                    $currentDateTime = Carbon::now();

                    // fetch due live campaigns for this campaign using campaign-local now
                    $dueLiveCamps = QuishingLiveCamp::where('campaign_id', $camp->campaign_id)
                        ->where('sent', '0')
                        ->where('send_time', '<=', $currentDateTime->toDateTimeString())
                        ->get();

                    foreach ($dueLiveCamps as $liveCamp) {
                        try {
                            // get template and website
                            $quishingTemplate = $liveCamp->templateData()->first();
                            if ($quishingTemplate === null || $quishingTemplate->website === null) {
                                continue;
                            }

                            $phishingWebsite = $quishingTemplate->website()->first();
                            $websiteUrl = getWebsiteUrl($phishingWebsite, $liveCamp, 'qsh');

                            // get qrcode link
                            $qrcodeLink = $this->getQRlink($liveCamp->user_email, $websiteUrl, $liveCamp->id);

                            // check if the campaign has sender profile
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

                            //send mail
                            $mailSent = sendPhishingMail($mailData);

                            if ($mailSent) {
                                QuishingActivity::where('campaign_live_id', $liveCamp->id)->update(['email_sent_at' => now()]);
                                echo "Mail sent to {$liveCamp->user_email} \n";
                                $liveCamp->sent = '1';
                                $liveCamp->save();
                            } else {
                                continue;
                            }
                        } catch (\Exception $e) {
                            echo "Error: " . $e->getMessage() . "\n";
                            continue;
                        }
                    }
                }
            } catch (\Exception $e) {
                echo "Error: " . $e->getMessage() . "\n";
                continue;
            }
        }

        $this->checkCompletedCampaigns();
    }

    private function makeCampaignLive($campaignid)
    {
        $campaign = QuishingCamp::where('campaign_id', $campaignid)->first();

        // check if the group exists
        $group = UsersGroup::where('group_id', $campaign->users_group)->first();
        if (!$group) {
            echo "Group not found for campaign ID: " . $campaignid . "\n";
            return;
        }
        $userIdsJson = UsersGroup::where('group_id', $campaign->users_group)->value('users');
        if (!$userIdsJson) {
            echo "No users found in group for campaign ID: " . $campaignid . "\n";
            return;
        }
        $userIds = json_decode($userIdsJson, true);
        if ($campaign->selected_users == null) {
            $users = Users::whereIn('id', $userIds)->get();
        } else {
            $users = Users::whereIn('id', json_decode($campaign->selected_users, true))->get();
        }

        $startTime = Carbon::parse($campaign->start_time);
        $endTime = Carbon::parse($campaign->end_time);

        // Convert both to timestamps (seconds)
        $min = $startTime->timestamp;
        $max = $endTime->timestamp;


        // Check if users exist in the group
        if (!$users->isEmpty()) {
            foreach ($users as $user) {
                // Generate a random timestamp each time
                $randomTimestamp = mt_rand($min, $max);
                setCompanyTimezone($campaign->company_id);
                $timeZone = config('app.timezone');

                // Convert it back to readable datetime
                $randomSendTime = Carbon::createFromTimestamp($randomTimestamp, $timeZone);

                $camp_live = QuishingLiveCamp::create([

                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->campaign_name,
                    'user_id' => $user->id,
                    'user_name' => $user->user_name,
                    'user_email' => $user->user_email,
                    'training_module' => $this->getRandomTrainingModule($campaign),
                    'scorm_training' => $this->getRandomScormTraining($campaign),
                    'days_until_due' => $campaign->days_until_due ?? null,
                    'training_lang' => $campaign->training_lang ?? null,
                    'training_type' => $campaign->training_type ?? null,
                    'send_time' => $randomSendTime,
                    'quishing_material'  => $this->getRandomQuishingMaterial($campaign),
                    'sender_profile'     => $campaign->sender_profile ?? null,
                    'quishing_lang'      => $campaign->quishing_lang ?? null,
                    'company_id'         => $campaign->company_id,
                ]);

                QuishingActivity::create([
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_live_id' => $camp_live->id,
                    'company_id' => $campaign->company_id,
                ]);

                // Audit log
                audit_log(
                    $campaign->company_id,
                    $campaign->user_email,
                    null,
                    'QUISHING_CAMPAIGN_SIMULATED',
                    "The campaign ‘{$campaign->campaign_name}’ has been sent to {$user->user_email}",
                    'normal'
                );
            }

            // Update the campaign status to 'running'
            $campaign->update(['status' => 'running']);


            echo "Campaign is live \n";
        }
    }

    private function getRandomTrainingModule($campaign)
    {
        if ($campaign->campaign_type == "quishing" || $campaign->training_module == null) {
            return null;
        }
        $trainingModules = json_decode($campaign->training_module, true);
        return $trainingModules[array_rand($trainingModules)];
    }

    private function getRandomScormTraining($campaign)
    {
        if ($campaign->campaign_type == "quishing" || $campaign->scorm_training == null) {
            return null;
        }

        $scormTrainings = json_decode($campaign->scorm_training, true);
        return $scormTrainings[array_rand($scormTrainings)];
    }

    private function getRandomQuishingMaterial($campaign)
    {

        if ($campaign->campaign_type == "quishing") {
            return null;
        }

        $quishingMaterials = json_decode($campaign->quishing_material, true);
        return $quishingMaterials[array_rand($quishingMaterials)];
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

        $companyName = Company::where('company_id', $campaign->company_id)->value('company_name');

        // $mailBody = str_replace('{{user_name}}', $campaign->user_name, $mailBody);
        $mailBody = str_replace(
            '{{qr_code}}',
            '<img src="' . $qrcodeUrl . '" alt="qr_code" width="300" height="300">' .
                '<input type="hidden" id="campaign_id" value="' . $campaign->campaign_id . '">' .
                '<input type="hidden" id="campaign_type" value="quishing">',
            $mailBody
        );



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
