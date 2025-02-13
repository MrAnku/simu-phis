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
        $companies = Company::where('service_status', 1)->get();

        foreach ($companies as $company) {
            $campaigns = WhatsAppCampaignUser::where('status', 'pending')->take(5)->get();
            if($campaigns && $company->whatsappConfig){
                $whatsapp_cloud_api = new WhatsAppCloudApi([
                    'from_phone_number_id' => $company->whatsappConfig->from_phone_id,
                    'access_token' => $company->whatsappConfig->access_token,
                ]);
                foreach($campaigns as $campaign){
                    $component_header = [];

                    $component_name = [
                        'type' => 'text',
                        'text' => $campaign->user_name,
                    ];

                    $component_link = [
                        'type' => 'text',
                        'text' => "https://".Str::random(3).".".env('PHISHING_WEBSITE_DOMAIN')."/c/" . base64_encode($campaign->id),
                    ];
        
                    $component_body = $campaign->components !== 'null' ? json_decode($campaign->components, true) : [];

                    array_unshift($component_body, $component_name);
                    array_push($component_body, $component_link);
            
                    $component_buttons = [];
            
                    $components = new Component($component_header, $component_body, $component_buttons);
            
                    $res = $whatsapp_cloud_api->sendTemplate($campaign->user_whatsapp, $campaign->template_name, $campaign->template_language, $components);
                    // print_r($res);
                    $campaign->status = 'sent';
                    $campaign->save();
                }
                
        
                
            }
                
        }

        // $user = DB::table('whatsapp_camp_users')->where('status', 'pending')->first();

        // if ($user) {
        //     $token = $this->getToken($user->company_id);

        //     if ($token !== null) {
        //         $url = env("WHATSAPP_API_URL") . '/sendtemplatemessage';

               
        //         $payload = [
        //             "token" => $token->token,
        //             "phone" => $user->user_whatsapp,
        //             "template_name" => $user->template_name,
        //             "template_language" => $user->template_language,
        //             "components" => $this->createFinalComponent($user)
        //         ];

        //         // Make the POST request
        //         $response = Http::withOptions(['verify' => false])->post($url, $payload);

        //         // Handle the response
        //         if ($response->successful()) {
        //             // Success handling
        //             $res = $response->json();
        //             if ($res['status'] == 'success') {
        //                 echo "message sent";
        //                 DB::table('whatsapp_camp_users')->where('id', $user->id)->update(['status' => 'sent']);
        //             } else {
        //                 echo "message not sent";
        //                 DB::table('whatsapp_camp_users')->where('id', $user->id)->update(['status' => 'failed']);
        //             }
        //         } else {
        //             // Error handling
        //             //    echo $response->status();
        //             echo "campaign failed";
        //         }
        //     }
        // }
    }

   

    private function createFinalComponent($user){
        if ($user->components !== 'null') {
            $newComponents = json_decode($user->components);

            // Create a new stdClass object for the new parameter
            $newParameter1 = new stdClass();
            $newParameter1->text = $user->user_name;
            $newParameter1->type = "text";

            // Prepend the new parameter to the parameters array
            array_unshift($newComponents[0]->parameters, $newParameter1);

            // Append another new parameter
            $newParameter2 = new stdClass();
            $randomString1 = Str::random(3);
            $newParameter2->text = "https://".$randomString1.".".env('PHISHING_WEBSITE_DOMAIN')."/c/" . base64_encode($user->id);
            $newParameter2->type = "text";

            // Append the new parameter to the parameters array
            $newComponents[0]->parameters[] = $newParameter2;
            return $newComponents;
        } else {
            return [];
        }
    }

    private function getToken($company_id)
    {
        $company = Company::where('company_id', $company_id)->first();
        $token = DB::table('partner_whatsapp_api')->where('partner_id', $company->partner_id)->first();

        if ($token) {
            return $token;
        } else {
            return null;
        }
    }
}
