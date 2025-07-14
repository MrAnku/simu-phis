<?php

namespace App\Console\Commands;

use Plivo\RestClient;
use App\Models\Company;
use Illuminate\Support\Str;
use App\Models\PhishingWebsite;
use Illuminate\Console\Command;
use App\Models\SmishingCampaign;
use App\Models\SmishingTemplate;
use Illuminate\Support\Facades\Http;

class ProcessSmishing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-smishing';

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
            $smishingCampaigns = $company->smishingLiveCamps()->where('sent', 0)->get();
            if ($smishingCampaigns->isEmpty()) {
                continue;
            }
            $translatedSmsBody = null;
            foreach ($smishingCampaigns as $campaign) {
                try {

                    $client = new RestClient(
                        env('PLIVO_AUTH_ID'),
                        env('PLIVO_AUTH_TOKEN')
                    );

                    $website = PhishingWebsite::find($campaign->website_id);

                    if(!$website){
                        echo "Website not found \n";
                        continue;
                    }

                    $redirectUrl = getWebsiteUrl($website, $campaign, 'smi');

                    $template = SmishingTemplate::find($campaign->template_id);
                    if(!$template){
                        echo "SMS Template not found \n";
                        continue;
                    }

                    if($translatedSmsBody == null && 
                    $campaign->template_lang !== 'en'){
                        $translatedSmsBody = $this->changeSmsLang($template->message, $campaign->template_lang);
                    }

                    if($translatedSmsBody !== null){
                        $finalTemplate = str_replace(
                            ['{{user_name}}', '{{redirect_url}}'],
                            [$campaign->user_name, $redirectUrl],
                            $translatedSmsBody
                        );
                    }else{
                        $finalTemplate = str_replace(
                            ['{{user_name}}', '{{redirect_url}}'],
                            [$campaign->user_name, $redirectUrl],
                            $template->message
                        );
                    }

                    
        
                    $client->messages->create(
                        [
                            "src" => env('PLIVO_MOBILE_NUMBER'),
                            "dst" => "+" . $campaign->user_phone,
                            "text"  => $finalTemplate
                        ]
                    );

                    echo "SMS sent to {$campaign->user_phone} \n";
                    $campaign->sent = 1;
                    $campaign->save();

                }  catch (\Plivo\Exceptions\PlivoRestException $e) {
                    // Handle the Plivo exception
                    echo "Failed to send SMS: " . $e->getMessage() . "\n";

                } catch (\Exception $e) {
                    // Handle the exception
                    echo "An error occurred: " . $e->getMessage() . "\n";
                }
                
            }
        }

        $this->checkCompletedCampaigns();
    }

    public function changeSmsLang($smsBody, $lang)
    {
        $apiKey = env('OPENAI_API_KEY');
        $apiEndpoint = "https://api.openai.com/v1/completions";

        $prompt = "Translate the following text content to {$lang}:\n\n{$smsBody}";

        $requestBody = [
            'model' => 'gpt-3.5-turbo-instruct',
            'prompt' => $prompt,
            'max_tokens' => 1500,
            'temperature' => 0.7,
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $apiKey,
        ])->post($apiEndpoint, $requestBody);

        if ($response->failed()) {
            echo 'Failed to fetch translation' . json_encode($response->body());
            return $smsBody;
        }
        $responseData = $response->json();
        $translatedMailBody = $responseData['choices'][0]['text'] ?? null;

        return $translatedMailBody;
    }


    private function checkCompletedCampaigns()
    {
        $campaigns = SmishingCampaign::where('status', 'running')->get();
        if ($campaigns->isEmpty()) {
            return;
        }
        foreach ($campaigns as $campaign) {
            $campaignLive = $campaign->campLive()->where('sent', 0)->count();
            if ($campaignLive == 0) {
                $campaign->status = 'completed';
                $campaign->save();
            }
        }
    }
}
