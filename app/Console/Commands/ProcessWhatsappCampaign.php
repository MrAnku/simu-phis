<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Users;
use App\Models\Company;
use App\Models\UsersGroup;
use App\Models\WaCampaign;
use Illuminate\Support\Str;
use App\Models\WaLiveCampaign;
use App\Models\PhishingWebsite;
use Illuminate\Console\Command;
use App\Models\WhatsappActivity;
use App\Models\BlueCollarEmployee;
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
        $this->checkScheduledCampaigns();
        $this->sendWhatsapp();
        $this->checkCompletedCampaigns();
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
                        'text' => getWebsiteUrl($website, $campaign, 'wsh'),
                    ];

                    $variables = json_decode($campaign->variables, true);

                    array_unshift($variables, $user_name);
                    array_push($variables, $website_link);


                    try {

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

    private function checkScheduledCampaigns()
    {
        //getting companies
        $companies = Company::where('service_status', 1)->where('approved', 1)->get();

        if ($companies->isEmpty()) {
            return;
        }

        foreach ($companies as $company) {

            setCompanyTimezone($company->company_id);

            $campaigns = WaCampaign::where('status', 'pending')
                ->where('company_id', $company->company_id)
                ->get();

            if ($campaigns) {
                foreach ($campaigns as $campaign) {
                    $launchTime = Carbon::parse($campaign->launch_time);
                    $currentDateTime = Carbon::now();

                    if ($launchTime->lessThan($currentDateTime)) {

                        $this->makeCampaignLive($campaign);

                        $campaign->update(['status' => 'running']);
                    }
                }
            }
        }
    }

    private function makeCampaignLive($campaign)
    {
        try {
            //check if the selected users group has users has whatsapp number
            if ($campaign->employee_type == 'normal') {
                if (!atLeastOneUserWithWhatsapp($campaign->users_group, $campaign->company_id)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('No employees with WhatsApp number found in the selected division.'),
                    ], 422);
                }
            }

            if ($campaign->employee_type == 'normal') {
                $userIdsJson = UsersGroup::where('group_id', $campaign->users_group)->value('users');
                $userIds = json_decode($userIdsJson, true);
                $users = Users::whereIn('id', $userIds)->get();
            }

            if ($campaign->employee_type == 'bluecollar') {

                $users = BlueCollarEmployee::where('group_id', $campaign->users_group)->get();
            }


            if ($users->isEmpty()) {
                echo "No users found for the campaign.\n";
            }

            foreach ($users as $user) {

                if (!$user->whatsapp) {
                    continue;
                }
                $camp_live = WaLiveCampaign::create([
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->campaign_name,
                    'campaign_type' => $campaign->campaign_type,
                    'employee_type' => $campaign->employee_type,
                    'user_name' => $user->user_name,
                    'user_id' => $user->id,
                    'user_email' => $user->user_email ?? null,
                    'user_phone' => $user->whatsapp,
                    'phishing_website' => $campaign->phishing_website,
                    'training_module' => $this->getTraining($campaign),
                    'scorm_training' => $this->getScormTraining($campaign),
                    'training_assignment' => $campaign->campaign_type == 'phishing' ? null : $campaign->training_assignment,

                    'days_until_due' => $campaign->campaign_type == 'phishing' ? null : $campaign->days_until_due,
                    'training_lang' => $campaign->campaign_type == 'phishing' ? null : $campaign->training_lang,
                    'training_type' => $campaign->campaign_type == 'phishing' ? null : $campaign->training_type,
                    'template_name' => $campaign->template_name,
                    'variables' => $campaign->variables,
                    'company_id' => $campaign->company_id,
                ]);

                WhatsappActivity::create([
                    'campaign_id' => $camp_live->campaign_id,
                    'campaign_live_id' => $camp_live->id,
                    'company_id' => $camp_live->company_id,
                ]);
            }


            echo "Campaign " . $campaign->campaign_name . " has been made live.\n";
        } catch (\Exception $e) {
            echo "Error saving campaign: " . $e->getMessage() . "\n";
            return;
        }
    }

    private function getTraining($campaign)
    {
        if ($campaign->campaign_type == 'phishing') {
            return null;
        }


        $trainings = json_decode($campaign->training_module, true);
        return $trainings[array_rand($trainings)];
    }
    private function getScormTraining($campaign)
    {
        if ($campaign->campaign_type == 'phishing' || $campaign->scorm_training == null) {
            return null;
        }

        $scormTrainings = json_decode($campaign->scorm_training, true);
        return $scormTrainings[array_rand($scormTrainings)];
    }

    private function checkCompletedCampaigns()
    {
        $campaigns = WaCampaign::where('status', 'running')
            ->get();
        if ($campaigns->isEmpty()) {
            return;
        }

        foreach ($campaigns as $campaign) {
            $liveCampaigns = WaLiveCampaign::where('campaign_id', $campaign->campaign_id)
                ->where('sent', 0)
                ->count();
            if ($liveCampaigns == 0) {
                $campaign->status = 'completed';
                $campaign->save();
                echo "Campaign " . $campaign->name . " has been marked as completed.\n";
            }
        }
    }
}
