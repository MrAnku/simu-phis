<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Mail\CampaignMail;
use App\Mail\QuishingMail;
use Endroid\QrCode\QrCode;
use Illuminate\Support\Str;
use App\Models\QuishingCamp;
use Endroid\QrCode\Color\Color;
use Illuminate\Console\Command;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\RoundBlockSizeMode;
use Illuminate\Support\Facades\Storage;
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
        $companies = Company::where('service_status', 1)->where('approved', true)->get();

        if (!$companies) {
            return;
        }
        foreach ($companies as $company) {
            $quishingCampaigns = $company->quishingLiveCamps()->where('sent', '0')->get();
            if (!$quishingCampaigns) {
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

                    //prepare mail body
                    $mailData = $this->prepareMailBody($campaign, $quishingTemplate->senderProfile()->first(), $quishingTemplate, $qrcodeLink);

                    //send mail
                    $mailSent = $this->sendMail($mailData);
                    if ($mailSent) {
                        echo "Mail sent to {$campaign->user_email} \n";
                        $campaign->sent = '1';
                        $campaign->save();
                    }
                }
            }
        }

        $this->checkCompletedCampaigns();
    }

    private function prepareMailBody($campaign, $senderProfile, $quishingMaterial, $qrcodeUrl)
    {

        $mailBody = Storage::path($quishingMaterial->file);

        $mailBody = file_get_contents($mailBody);

        $mailBody = str_replace('{{user_name}}', $campaign->user_name, $mailBody);
        $mailBody = str_replace('{{qr_code}}', '<img src="' . $qrcodeUrl . '" alt="qr_code" width="300" height="300">', $mailBody);


        if ($campaign->quishing_lang !== 'en') {

            $mailBody = $this->changeEmailLang($mailBody, $campaign->quishing_lang);
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
        $apiKey = env('OPENAI_API_KEY');
        $apiEndpoint = "https://api.openai.com/v1/chat/completions";
        // $fileContent = file_get_contents($tempBodyFile);

        // Optional: Trim content if it’s too long to prevent token limit issues
        // if (strlen($fileContent) > 10000) {
        //     $fileContent = substr($fileContent, 0, 10000);
        // }

        $messages = [
            [
                "role" => "system",
                "content" => "You are a professional email translator."
            ],
            [
                "role" => "user",
                "content" => "Translate the following email content to " . langName($email_lang) . " language:\n\n{$emailBody}"
            ]
        ];

        $requestBody = [
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages,
            'max_tokens' => 1500,
            'temperature' => 0.7,
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $apiKey,
        ])->timeout(60) // Avoid curl timeout error
            ->post($apiEndpoint, $requestBody);

        if ($response->failed()) {
            echo 'Failed to fetch translation: ' . $response->body();
            return $emailBody;
        }

        $responseData = $response->json();
        $translatedMailBody = $responseData['choices'][0]['message']['content'] ?? null;

        return $translatedMailBody;
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
