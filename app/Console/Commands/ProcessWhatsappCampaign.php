<?php

namespace App\Console\Commands;

use stdClass;
use App\Models\Company;
use Illuminate\Support\Str;
use App\Models\WaLiveCampaign;
use App\Models\PhishingWebsite;
use Illuminate\Console\Command;
use App\Models\WhatsappActivity;
use App\Models\WhatsappCampaign;
use Illuminate\Support\Facades\DB;
use App\Models\WhatsAppCampaignUser;
use Illuminate\Support\Facades\Http;
use Netflie\WhatsAppCloudApi\WhatsAppCloudApi;
use Netflie\WhatsAppCloudApi\Message\Template\Component;

class ProcessWhatsappCampaign extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-whatsapp-campaign';

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
        // $this->checkScheduledCampaigns();
        $this->sendWhatsapp();
    }

    private function sendWhatsapp()
    {
        //getting companies
        $companies = Company::where('service_status', 1)->where('approved', 1)->get();

        if ($companies->isEmpty()) {
            return;
        }

        foreach ($companies as $company) {
            
            setCompanyTimezone($company->company_id);

            $campaigns = WaLiveCampaign::where('sent', 0)->where('company_id', $company->company_id)->take(5)->get();
            if ($campaigns && $company->whatsappConfig) {
                // $whatsapp_cloud_api = new WhatsAppCloudApi([
                //     'from_phone_number_id' => $company->whatsappConfig->from_phone_id,
                //     'access_token' => $company->whatsappConfig->access_token,
                // ]);
                foreach ($campaigns as $campaign) {


                    $component_header = [];

                    $user_name = [
                        'type' => 'text',
                        'text' => $campaign->user_name,
                    ];

                    $website = PhishingWebsite::find($campaign->phishing_website);

                    if (!$website) {
                        echo "Website not found \n";
                        continue;
                    }

                    $website_link = [
                        'type' => 'text',
                        'text' => $this->getWebsiteUrl($website, $campaign),
                    ];

                    $variables = json_decode($campaign->variables, true);

                    array_unshift($variables, $user_name);
                    array_push($variables, $website_link);

                    $component_buttons = [];

                    // $components = new Component($component_header, $variables, $component_buttons);




                    // if ($campaign->components !== 'null') {
                    //     $component_header = [];

                    //     $component_name = [
                    //         'type' => 'text',
                    //         'text' => $campaign->user_name,
                    //     ];

                    //     $component_link = [
                    //         'type' => 'text',
                    //         'text' => "https://" . Str::random(3) . "." . env('PHISHING_WEBSITE_DOMAIN') . "/c/" . base64_encode($campaign->id),
                    //     ];

                    //     $component_body = json_decode($campaign->components, true);

                    //     array_unshift($component_body, $component_name);
                    //     array_push($component_body, $component_link);

                    //     $component_buttons = [];

                    //     $components = new Component($component_header, $component_body, $component_buttons);
                    // } else {
                    //     $components = null;
                    // }

                    try {
                        // $res = $whatsapp_cloud_api->sendTemplate($campaign->user_whatsapp, $campaign->template_name, $campaign->template_language, $components);
                        // print_r($res);
                        $response = Http::withToken($company->whatsappConfig->access_token) // Set Bearer Token
                            ->withoutVerifying() // Disable SSL verification
                            ->post(
                                'https://graph.facebook.com/v22.0/' . $company->whatsappConfig->from_phone_id . '/messages',
                                [
                                    "messaging_product" => "whatsapp",
                                    "to" => $campaign->user_phone,
                                    "type" => "template",
                                    "template" => [
                                        "name" => $campaign->template_name,
                                        "language" => [
                                            "code" => 'en'
                                        ],
                                        "components" => [
                                            [
                                                "type" => "body",
                                                "parameters" => $variables
                                            ]
                                        ]
                                    ]
                                ]
                            );

                        // Get the response
                        $data = $response->json();

                        if ($response->successful()) {
                            $campaign->sent = 1;
                            $campaign->save();
                            echo "WhatsApp message sent to " . $campaign->user_name . "\n";

                            WhatsappActivity::where('campaign_live_id', $campaign->id)->update(['whatsapp_sent_at' => now()]);
                        } else {
                            echo json_encode($response->body());
                        }
                    } catch (\Exception $th) {
                        echo $th->getMessage();
                    }
                }
            }
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
            'wsh' => base64_encode($campaign->id)
        ];

        // Build query string and final URL
        $queryString = http_build_query($params);
        $websiteFilePath = $baseUrl . '?' . $queryString;

        return $websiteFilePath;
    }
}
