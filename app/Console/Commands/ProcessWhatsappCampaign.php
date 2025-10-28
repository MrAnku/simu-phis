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
use Illuminate\Support\Facades\Log;

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
        $companies = Company::where('service_status', 1)
            ->where('approved', 1)
            ->where('role', null)
            ->get();

        if ($companies->isEmpty()) {
            return;
        }

        foreach ($companies as $company) {

            setCompanyTimezone($company->company_id);

            $runningCampaigns = WaCampaign::where('company_id', $company->company_id)
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
                $dueLiveCamps = WaLiveCampaign::where('campaign_id', $camp->campaign_id)
                    ->where('sent', '0')
                    ->where('send_time', '<=', $currentDateTime->toDateTimeString())
                    ->take(5)->get();

                if ($dueLiveCamps && $company->whatsappConfig) {
                    foreach ($dueLiveCamps as $campaign) {

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
                            continue;
                        }
                    }
                }
            }
        }
    }

    private function checkScheduledCampaigns()
    {
        //getting companies
        $companies = Company::where('service_status', 1)
            ->where('approved', 1)
            ->where('role', null)
            ->get();

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
                    $scheduleDate = Carbon::parse($campaign->schedule_date);
                    $currentDateTime = Carbon::now();

                    if ($scheduleDate->lte($currentDateTime)) {

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
                if ($campaign->selected_users == null) {
                    $users = Users::whereIn('id', $userIds)->get();
                } else {
                    $users = Users::whereIn('id', json_decode($campaign->selected_users, true))->get();
                }
            }

            if ($campaign->employee_type == 'bluecollar') {

                if ($campaign->selected_users == null) {
                    $users = BlueCollarEmployee::where('group_id', $campaign->users_group)->get();
                } else {
                    $users = BlueCollarEmployee::whereIn('id', json_decode($campaign->selected_users, true))->get();
                }
            }


            if ($users->isEmpty()) {
                echo "No users found for the campaign.\n";
            }

            $startTime = Carbon::parse($campaign->start_time);
            $endTime = Carbon::parse($campaign->end_time);

            // Convert both to timestamps (seconds)
            $min = $startTime->timestamp;
            $max = $endTime->timestamp;

            foreach ($users as $user) {

                if (!$user->whatsapp) {
                    continue;
                }

                // Generate a random timestamp each time
                $randomTimestamp = mt_rand($min, $max);
                setCompanyTimezone($campaign->company_id);
                $timeZone = config('app.timezone');

                // Convert it back to readable datetime
                $randomSendTime = Carbon::createFromTimestamp($randomTimestamp, $timeZone);

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
                    'send_time' => $randomSendTime
                ]);

                WhatsappActivity::create([
                    'campaign_id' => $camp_live->campaign_id,
                    'campaign_live_id' => $camp_live->id,
                    'company_id' => $camp_live->company_id,
                ]);

                // Audit log
                audit_log(
                    $camp_live->company_id,
                    $user->user_email ?? null,
                    $user->whatsapp ?? null,
                    'WHATSAPP_CAMPAIGN_SIMULATED',
                    "The campaign ‘{$campaign->campaign_name}’ has been sent to " . ($user->user_email ?? $user->whatsapp),
                    $campaign->employee_type
                );
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
        if (!$campaigns) {
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

        // Relaunch completed recurring whatsapp campaigns (weekly/monthly/quarterly)
        $completedRecurring = WaCampaign::where('status', 'completed')
            ->whereIn('msg_freq', ['weekly', 'monthly', 'quarterly'])
            ->get();

        foreach ($completedRecurring as $recurr) {
            try {
                // parse last launch_time
                try {
                    if (!empty($recurr->launch_date)) {
                        // launch_date stores only date; use start of day as last launch
                        $lastLaunch = Carbon::parse($recurr->launch_date)->startOfDay();
                    } else {
                        Log::error("ProcessWhatsapp: no launch_date for campaign {$recurr->campaign_id}");
                        continue;
                    }
                } catch (\Exception $e) {
                    Log::error("ProcessWhatsapp: failed to parse launch_date for campaign {$recurr->campaign_id} - " . $e->getMessage());
                    continue;
                }

                $nextLaunch = $lastLaunch->copy();

                switch ($recurr->msg_freq) {
                    case 'weekly':
                        $nextLaunch->addWeek();
                        break;
                    case 'monthly':
                        $nextLaunch->addMonth();
                        break;
                    case 'quarterly':
                        $nextLaunch->addMonths(3);
                        break;
                    default:
                        continue 2;
                }

                // check expiry
                if ($recurr->expire_after !== null) {
                    try {
                        $expireAt = Carbon::parse($recurr->expire_after);
                    } catch (\Exception $e) {
                        Log::error("ProcessQuishing: failed to parse expire_after for campaign {$recurr->campaign_id} - " . $e->getMessage());
                        $recurr->update(['status' => 'completed']);
                        continue;
                    }

                    if ($nextLaunch->greaterThanOrEqualTo($expireAt)) {
                        continue;
                    }
                }

                if (Carbon::now()->greaterThanOrEqualTo($nextLaunch)) {
                    $recurr->update([
                        'launch_date' => $nextLaunch->toDateString(),
                        'status' => 'running',
                    ]);

                    echo "Relaunching whatsapp campaign {$recurr->campaign_id} (freq: {$recurr->msg_freq}) for date {$nextLaunch->toDateString()}\n";

                    // reset live rows for this campaign
                    $liveRows = WaLiveCampaign::where('campaign_id', $recurr->campaign_id)->get();
                    $resetCount = 0;
                    foreach ($liveRows as $live) {
                        try {
                            // Preserve the existing time-of-day for each send_time, only update the date to nextLaunch
                            try {
                                $currentSend = Carbon::parse($live->send_time);
                                $newSend = Carbon::createFromFormat('Y-m-d H:i:s', $nextLaunch->toDateString() . ' ' . $currentSend->format('H:i:s'));
                            } catch (\Exception $e) {
                                // fallback: if parsing fails, use nextLaunch at startOfDay
                                $newSend = $nextLaunch->copy()->startOfDay();
                            }

                            $live->update([
                                'sent' => 0,
                                'payload_clicked' => 0,
                                'compromised' => 0,
                                'training_assigned' => 0,
                                'send_time' => $newSend,
                            ]);
                            $resetCount++;
                        } catch (\Exception $e) {
                            Log::error("ProcessWhatsapp: failed to reset whatsapp {$live->id} for campaign {$recurr->campaign_id} - " . $e->getMessage());
                        }
                    }

                    echo "Reset {$resetCount} live rows for campaign {$recurr->campaign_id}\n";
                }
            } catch (\Exception $e) {
                Log::error("ProcessQuishing: error while relaunching campaign {$recurr->campaign_id} - " . $e->getMessage());
                continue;
            }
        }
    }
}
