<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Mail\CampaignMail;
use Endroid\QrCode\QrCode;
use Illuminate\Support\Str;
use App\Models\QuishingCamp;
use App\Models\SenderProfile;
use App\Models\OutlookDmiToken;
use Endroid\QrCode\Color\Color;
use Illuminate\Console\Command;
use App\Models\QuishingActivity;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
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


            $quishingCampaigns = $company->quishingLiveCamps()->where('sent', '0')->get();
            if ($quishingCampaigns->isEmpty()) {
                continue;
            }
            foreach ($quishingCampaigns as $campaign) {
                //get website url
                $quishingTemplate = $campaign->templateData()->first();
                if ($quishingTemplate->website !== null && $quishingTemplate->sender_profile !== null) {
                    $phishingWebsite = $quishingTemplate->website()->first();
                    $websiteUrl = $this->getWebsiteUrl($phishingWebsite, $campaign);

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
                    $mailSent = $this->sendMail($mailData);
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
                if ($this->sendMail($mailData)) {

                    $activity = QuishingActivity::where('campaign_live_id', $campaign->id)->update(['email_sent_at' => now()]);

                    echo "Email sent to: " . $campaign->user_email . "\n";
                } else {
                    echo "Email not sent to: " . $campaign->user_email . "\n";
                }
            }
        } else {
            echo "Non-Outlook email detected: " . $campaign->user_email . "\n";
            if ($this->sendMail($mailData)) {

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

            $mailBody = $this->changeEmailLang($mailBody, $campaign->quishing_lang);
        }

        if ($campaign->quishing_lang == 'am') {

            $mailBody = $this->translateHtmlToAmharic($mailBody);
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

    private function translateHtmlToAmharic(string $htmlContent): ?string
    {
        $apiKey = env('OPENAI_API_KEY');
        $endpoint = 'https://api.openai.com/v1/chat/completions';

        // Step 1: Split HTML into chunks (e.g., by <div> or <p>)
        $chunks = preg_split('/(?=<div|<p|<section|<article|<table|<ul|<ol|<h[1-6])/i', $htmlContent, -1, PREG_SPLIT_NO_EMPTY);

        $translatedChunks = [];

        foreach ($chunks as $index => $chunk) {
            $messages = [
                [
                    "role" => "system",
                    "content" => "You are a professional translator. Translate only the visible text in the HTML into Amharic. Do not alter the structure, tags, attributes, or inline styles."
                ],
                [
                    "role" => "user",
                    "content" => "Translate this HTML into Amharic, keeping the HTML unchanged:\n\n$chunk"
                ]
            ];

            try {
                $response = Http::timeout(60)
                    ->retry(3, 5000)
                    ->withHeaders([
                        'Authorization' => "Bearer {$apiKey}",
                        'Content-Type'  => 'application/json',
                    ])->post($endpoint, [
                        'model' => 'gpt-4o',
                        'messages' => $messages,
                        'temperature' => 0.2,
                        'max_tokens' => 2048,
                    ]);

                if ($response->successful()) {
                    $translatedChunk = $response->json()['choices'][0]['message']['content'] ?? '';
                    $translatedChunks[] = $translatedChunk;
                } else {
                    \Log::error("Chunk $index failed", ['status' => $response->status(), 'body' => $response->body()]);
                    $translatedChunks[] = $chunk; // fallback to original
                }

                // Sleep to avoid hitting rate limits
                sleep(1);
            } catch (\Exception $e) {
                \Log::error("Chunk $index exception", ['error' => $e->getMessage()]);
                $translatedChunks[] = $chunk; // fallback to original
            }
        }

        // Step 3: Combine all translated chunks
        return implode('', $translatedChunks);
    }

    private function sendMail($mailData)
    {

        // Set mail configuration dynamically
        config([
            'mail.mailers.smtp.host' => $mailData['sendMailHost'],
            'mail.mailers.smtp.username' => $mailData['sendMailUserName'],
            'mail.mailers.smtp.password' => $mailData['sendMailPassword'],
        ]);


        try {
            Mail::to($mailData['email'])->send(new CampaignMail($mailData));
            return true;
        } catch (\Exception $e) {

            return false;
        }
    }

    private function getWebsiteUrl($phishingWebsite, $campaign)
    {
        // Generate random parts
        $randomString1 = Str::random(6);
        $randomString2 = Str::random(10);
        $slugName = Str::slug($phishingWebsite->name);

        // Construct the base URL
        $baseUrl = "https://{$randomString1}.{$phishingWebsite->domain}/{$randomString2}";

        // Define query parameters
        $params = [
            'v' => 'r',
            'c' => Str::random(10),
            'p' => $phishingWebsite->id,
            'l' => $slugName,
            'token' => $campaign->id,
            'usrid' => $campaign->user_id,
            'qsh' => base64_encode($campaign->id)
        ];

        // Build query string and final URL
        $queryString = http_build_query($params);
        $websiteFilePath = $baseUrl . '?' . $queryString;

        return $websiteFilePath;
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

    public function changeEmailLang($emailBody, $email_lang)
    {
        $tempFile = tmpfile();
        fwrite($tempFile, $emailBody);
        $meta = stream_get_meta_data($tempFile);
        $tempFilePath = $meta['uri'];

        $response = Http::withoutVerifying()
            ->timeout(60)
            ->attach('file', file_get_contents($tempFilePath), 'email.html')
            ->post('https://translate.sparrow.host/translate_file', [
                'source' => 'en',
                'target' => $email_lang,
            ]);

        fclose($tempFile);

        if ($response->failed()) {
            echo 'Failed to fetch translation: ' . $response->body();
            return $emailBody;
        }

        $responseData = $response->json();
        $translatedUrl = $responseData['translatedFileUrl'] ?? null;

        if (!$translatedUrl) {
            echo 'No translated URL found in response.';
            return $emailBody;
        }

        $translatedUrl = str_replace('http://', 'https://', $translatedUrl);

        $translatedContent = file_get_contents($translatedUrl);


        return $translatedContent;
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
