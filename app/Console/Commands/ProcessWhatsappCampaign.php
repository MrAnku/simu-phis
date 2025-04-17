<?php

namespace App\Console\Commands;

use stdClass;
use App\Models\Company;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
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
        //getting companies
        $companies = Company::where('service_status', 1)->where('approved', true)->get();

        if (!$companies) {
            return;
          }

        foreach ($companies as $company) {
            $campaigns = WhatsAppCampaignUser::where('status', 'pending')->where('company_id', $company->company_id)->take(5)->get();
            if ($campaigns && $company->whatsappConfig) {
                $whatsapp_cloud_api = new WhatsAppCloudApi([
                    'from_phone_number_id' => $company->whatsappConfig->from_phone_id,
                    'access_token' => $company->whatsappConfig->access_token,
                ]);
                foreach ($campaigns as $campaign) {
                    if ($campaign->components !== 'null') {
                        $component_header = [];

                        $component_name = [
                            'type' => 'text',
                            'text' => $campaign->user_name,
                        ];

                        $component_link = [
                            'type' => 'text',
                            'text' => "https://" . Str::random(3) . "." . env('PHISHING_WEBSITE_DOMAIN') . "/c/" . base64_encode($campaign->id),
                        ];

                        $component_body = json_decode($campaign->components, true);

                        array_unshift($component_body, $component_name);
                        array_push($component_body, $component_link);

                        $component_buttons = [];

                        $components = new Component($component_header, $component_body, $component_buttons);
                    } else {
                        $components = null;
                    }

                    try {
                        // $res = $whatsapp_cloud_api->sendTemplate($campaign->user_whatsapp, $campaign->template_name, $campaign->template_language, $components);
                        // print_r($res);
                        $response = Http::withToken($company->whatsappConfig->access_token) // Set Bearer Token
                            ->withoutVerifying() // Disable SSL verification
                            ->post(
                                'https://graph.facebook.com/v22.0/' . $company->whatsappConfig->from_phone_id . '/messages',
                                [
                                    "messaging_product" => "whatsapp",
                                    "to" => $campaign->user_whatsapp,
                                    "type" => "template",
                                    "template" => [
                                        "name" => $campaign->template_name,
                                        "language" => [
                                            "code" => $campaign->template_language
                                        ],
                                        "components" => [
                                            [
                                                "type" => "body",
                                                "parameters" => $component_body
                                            ]
                                        ]
                                    ]
                                ]
                            );

                        // Get the response
                        $data = $response->json();

                        if ($response->successful()) {
                            $campaign->status = 'sent';
                            $campaign->save();
                            echo "WhatsApp message sent to " . $campaign->user_name . "\n";
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
}
