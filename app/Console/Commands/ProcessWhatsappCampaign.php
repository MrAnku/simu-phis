<?php

namespace App\Console\Commands;

use stdClass;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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

        $user = DB::table('whatsapp_camp_users')->where('status', 'pending')->first();

        if ($user) {
            $tokenAndUrl = $this->getTokenAndUrl($user->company_id);

            if ($tokenAndUrl !== null) {
                $url = $tokenAndUrl->url . '/sendtemplatemessage';

               
                $payload = [
                    "token" => $tokenAndUrl->token,
                    "phone" => $user->user_whatsapp,
                    "template_name" => $user->template_name,
                    "template_language" => $user->template_language,
                    "components" => $this->createFinalComponent($user)
                ];

                // Make the POST request
                $response = Http::withOptions(['verify' => false])->post($url, $payload);

                // Handle the response
                if ($response->successful()) {
                    // Success handling
                    $res = $response->json();
                    if ($res['status'] == 'success') {
                        echo "message sent";
                        DB::table('whatsapp_camp_users')->where('id', $user->id)->update(['status' => 'sent']);
                    } else {
                        echo "message not sent";
                        DB::table('whatsapp_camp_users')->where('id', $user->id)->update(['status' => 'failed']);
                    }
                } else {
                    // Error handling
                    //    echo $response->status();
                    echo "campaign failed";
                }
            }
        }
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
            $newParameter2->text = "https://".env('PHISHING_WEBSITE_DOMAIN')."/c/" . base64_encode($user->id);
            $newParameter2->type = "text";

            // Append the new parameter to the parameters array
            $newComponents[0]->parameters[] = $newParameter2;
            return $newComponents;
        } else {
            return [];
        }
    }

    private function getTokenAndUrl($company_id)
    {
        $company = Company::where('company_id', $company_id)->first();
        $tokenUrl = DB::table('partner_whatsapp_api')->where('partner_id', $company->partner_id)->first();

        if ($tokenUrl) {
            return $tokenUrl;
        } else {
            return null;
        }
    }
}
