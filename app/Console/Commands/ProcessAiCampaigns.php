<?php

namespace App\Console\Commands;

use App\Models\Users;
use App\Models\Company;
use App\Models\UsersGroup;
use App\Models\AiCallCampaign;
use App\Models\AiCallCampLive;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use App\Models\BlueCollarEmployee;
use Illuminate\Support\Facades\Http;
use App\Services\PolicyAssignedService;
use App\Services\CampaignTrainingService;
use App\Services\BlueCollarCampTrainingService;
use Illuminate\Support\Facades\Log;

class ProcessAiCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-ai-campaigns';

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
        $this->checkCallStatus();
        $this->processScheduledCampaigns();
        $this->processAiCalls();
        // $this->analyseAicallReports();
        $this->checkAllAiCallsHandled();
        $this->checkCompletedCampaigns();
    }

    private function processAiCalls()
    {
        $companies = Company::where('approved', 1)
            ->where('role', null)
            ->where('service_status', 1)
            ->get();

        if ($companies->isEmpty()) {
            return;
        }
        foreach ($companies as $company) {
            try {
                $runningCampaigns = AiCallCampaign::where('company_id', $company->company_id)
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
                    $pendingCalls = AiCallCampLive::where('campaign_id', $camp->campaign_id)
                        ->where('send_time', '<=', $currentDateTime->toDateTimeString())
                        ->where(function ($q) {
                            $q->where('status', 'pending')
                                ->orWhere('status', 'no-answer')
                                ->orWhere('status', 'failed')
                                ->orWhere('status', 'busy')
                                ->orWhere('status', 'canceled');
                        })
                        ->get();



                    $url = 'https://callapi3.sparrowhost.net/call';

                    foreach ($pendingCalls as $pendingCall) {
                        try {
                            if ($this->isRetellAgent($pendingCall->agent_id)) {
                                continue;
                            }

                            if ($pendingCall->calls_sent >= 3) {

                                continue;
                            }

                            setCompanyTimezone($pendingCall->company_id);

                            // Make the HTTP request
                            $requestBody = [
                                "user_id" => extractIntegers($pendingCall->company_id),
                                "agent_id" => $pendingCall->agent_id,
                                "twilio_account_sid" => env('TWILIO_ACCOUNT_SID'),
                                "twilio_auth_token" => env('TWILIO_AUTH_TOKEN'),
                                "twilio_phone_number" => env('TWILIO_PHONE_NUMBER'),
                                "recipient_phone_number" => $pendingCall->to_mobile
                            ];

                            $response = Http::post($url, $requestBody);

                            // Check for a successful response
                            if ($response->successful()) {
                                $callResponse = $response->json();
                                // // Return the response data
                                $pendingCall->call_id = $callResponse['call_sid'];
                                $pendingCall->call_send_response = json_encode($callResponse, true);
                                $pendingCall->status = 'waiting';
                                $pendingCall->save();
                                $pendingCall->increment('calls_sent');
                                echo $response->body() . "\n";

                                // Mark the AiCallCampaign as completed after call is initiated
                                AiCallCampaign::where('campaign_id', $pendingCall->campaign_id)
                                    ->update(['status' => 'completed']);
                            } else {
                                // Handle the error, e.g., log the error or throw an exception
                                echo "Call Failed: " . $response->body() . "\n";
                            }
                        } catch (\Exception $e) {
                            echo "Something went wrong " . $e->getMessage();
                            continue;
                        }
                    }
                }
            } catch (\Exception $e) {
                echo "Something went wrong " . $e->getMessage();
                continue;
            }
        }
    }

    private function processScheduledCampaigns()
    {
        $companies = Company::where('approved', 1)
            ->where('role', null)
            ->where('service_status', 1)
            ->get();

        if ($companies->isEmpty()) {
            return;
        }

        foreach ($companies as $company) {
            setCompanyTimezone($company->company_id);

            // Fetch campaigns for this company
            $campaigns = AiCallCampaign::where('company_id', $company->company_id)
                ->where('status', 'pending')
                ->get();

            foreach ($campaigns as $campaign) {
                // Scheduled campaign: insert into live table only when launch_time passes
                if ($campaign->launch_type === 'scheduled' && $campaign->status === 'pending') {
                    $scheduleDate = Carbon::parse($campaign->schedule_date);

                    $currentDateTime = Carbon::now();

                    if ($scheduleDate->lte($currentDateTime)) {
                        // Insert record in live table
                        $this->makeCampaignLive($campaign->campaign_id);
                        $campaign->update(['status' => 'running']);
                    } else {
                        echo 'Campaign : ' . $campaign->campaign_name . ' is not yet scheduled to go live.' . "\n";
                    }
                }
            }
        }
    }

    private function makeCampaignLive($campaignid)
    {
        $campaign = AiCallCampaign::where('campaign_id', $campaignid)->first();

        if ($campaign) {

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

            $startTime = Carbon::parse($campaign->start_time);
            $endTime = Carbon::parse($campaign->end_time);

            // Convert both to timestamps (seconds)
            $min = $startTime->timestamp;
            $max = $endTime->timestamp;

            if ($users) {
                foreach ($users as $user) {

                    if ($user->whatsapp == null) {
                        continue;
                    }

                    $training_mods = json_decode($campaign->training_module, true);
                    $scorms = json_decode($campaign->scorm_training, true);

                    // Generate a random timestamp each time
                    $randomTimestamp = mt_rand($min, $max);
                    setCompanyTimezone($campaign->company_id);
                    $timeZone = config('app.timezone');

                    // Convert it back to readable datetime
                    $randomSendTime = Carbon::createFromTimestamp($randomTimestamp, $timeZone);

                    AiCallCampLive::create([
                        'campaign_id' => $campaign->campaign_id,
                        'campaign_name' => $campaign->campaign_name,
                        'employee_type' => $campaign->employee_type,
                        'user_id' => $user->id,
                        'user_name' => $user->user_name,
                        'user_email' => $user->user_email,
                        'training_module' => (empty($training_mods) ? null :  $training_mods[array_rand($training_mods)]),
                        'scorm_training' => (empty($scorms) ? null :  $scorms[array_rand($scorms)]),
                        'training_lang' => $campaign->training_lang ?? null,
                        'training_type' => $campaign->training_type ?? null,
                        'from_mobile' => $campaign->phone_no,
                        'to_mobile' => "+" . $user->whatsapp,
                        'agent_id' => $campaign->ai_agent,
                        'status' => 'pending',
                        'company_id' => $campaign->company_id,
                        'send_time' => $randomSendTime,
                    ]);
                    // Audit log
                    audit_log(
                        $campaign->company_id,
                        $campaign->user_email,
                        $user->whatsapp,
                        'AI_CAMPAIGN_SIMULATED',
                        "The campaign ‘{$campaign->campaign_name}’ has been sent to {$user->whatsapp}",
                        'normal'
                    );
                }

                echo "Campaign is live \n";
            }
        }
    }

    private function checkCallStatus()
    {
        $companies = Company::where('approved', 1)
            ->where('role', null)
            ->where('service_status', 1)
            ->get();

        if ($companies->isEmpty()) {
            return;
        }
        foreach ($companies as $company) {
            try {
                $placedCalls = AiCallCampLive::where('company_id', $company->company_id)
                    ->whereNotIn('status', ['pending', 'completed'])
                    ->get();
                if ($placedCalls->isEmpty()) {
                    continue;
                }

                foreach ($placedCalls as $placedCall) {
                    if ($this->isRetellAgent($placedCall->agent_id)) {
                        continue;
                    }

                    $status = $this->checkStatus($placedCall->call_id);
                    if ($status == null) {
                        continue;
                    }
                    if ($status !== 'completed') {
                        $placedCall->update(['status' => $status]);
                        continue;
                    }

                    // this will execute after the call completion

                    $compromised = $this->checkEmployeeCompromised($placedCall->call_id);
                    if ($compromised) {
                        audit_log(
                            $placedCall->company_id,
                            $placedCall->user_email,
                            null,
                            'EMPLOYEE_COMPROMISED',
                            "{$placedCall->user_email} compromised in AI call campaign '{$placedCall->campaign_name}'",
                            'normal'
                        );
                        if ($placedCall->training_module !== null || $placedCall->scorm_training !== null) {

                            $this->assignTraining($placedCall);
                            $placedCall->update(['training_assigned' => 1]);
                        }

                        $placedCall->update(['compromised' => 1]);
                    }
                    $placedCall->update([
                        'status' => 'completed',
                        'call_end_response' => json_encode(['call_ended' => true])
                    ]);
                }
            } catch (\Exception $e) {
                echo "Something went wrong " . $e->getMessage();
            }
        }
    }

    private function checkStatus($callId)
    {
        try {
            $response = Http::get('https://callapi3.sparrowhost.net/call/call_info/' . $callId);

            if ($response->successful()) {
                $data = $response->json();
                return $data['status'];
            }
        } catch (\Exception $e) {
            echo "Something went wrong " . $e->getMessage();
            return null;
        }
    }

    private function checkEmployeeCompromised($callId)
    {
        try {
            $response = Http::get('https://callapi3.sparrowhost.net/call/detect_vishing/' . $callId);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['vishing_detected'] == true) {
                    return true;
                }
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    private function isRetellAgent($agentId)
    {
        if (strpos($agentId, 'agent_') === 0) {
            return true;
        }
        return false;
    }



    private function checkAllAiCallsHandled()
    {
        $campaigns = AiCallCampaign::where('status', 'running')->get();
        if ($campaigns->isEmpty()) {
            return;
        }

        foreach ($campaigns as $campaign) {
            $liveCampaigns = $campaign->individualCamps()
                ->where('status', '!=', 'completed')
                ->get();

            if ($liveCampaigns->isEmpty()) {

                $campaign->update(['status' => 'completed']);
            }
        }
    }

    private function assignTraining($campaign)
    {
        setCompanyTimezone($campaign->company_id);

        $all_camp = AiCallCampaign::where('campaign_id', $campaign->campaign_id)->first();

        $trainingModules = [];
        $scormTrainings = [];

        if ($all_camp->training_module !== null) {
            $trainingModules = json_decode($all_camp->training_module, true);
        }

        if ($all_camp->scorm_training !== null) {
            $scormTrainings = json_decode($all_camp->scorm_training, true);
        }

        if ($campaign->employee_type == 'normal') {
            if ($all_camp->training_assignment == 'all') {
                $sent = CampaignTrainingService::assignTraining($campaign, $trainingModules, false, $scormTrainings);
            } else {
                $sent = CampaignTrainingService::assignTraining($campaign);
            }

            if ($campaign->camp?->policies != null) {
                try {
                    $policyService = new PolicyAssignedService(
                        $campaign->campaign_id,
                        $campaign->user_name,
                        $campaign->user_email,
                        $campaign->company_id
                    );

                    $policyService->assignPolicies($campaign->camp?->policies);
                } catch (\Exception $e) {
                }
            }

            if ($sent) {
                $campaign->update(['sent' => 1, 'training_assigned' => 1]);
            }
        } else {
            if ($all_camp->training_assignment == 'all') {

                $sent = BlueCollarCampTrainingService::assignBlueCollarTraining($campaign, $trainingModules, $scormTrainings);
            } else {
                $sent = BlueCollarCampTrainingService::assignBlueCollarTraining($campaign);
            }

            if ($sent) {
                $campaign->update(['sent' => 1, 'training_assigned' => 1]);
            }
        }
    }

    private function checkCompletedCampaigns()
    {
        $campaigns = AiCallCampaign::where('status', 'running')
            ->get();
        if (!$campaigns) {
            return;
        }

        foreach ($campaigns as $campaign) {
            $liveCampaigns = AiCallCampLive::where('campaign_id', $campaign->campaign_id)
                ->where('calls_sent', 0)
                ->count();
            if ($liveCampaigns == 0) {
                $campaign->status = 'completed';
                $campaign->save();
                echo "Campaign " . $campaign->name . " has been marked as completed.\n";
            }
        }

        // Relaunch completed recurring whatsapp campaigns (weekly/monthly/quarterly)
        $completedRecurring = AiCallCampaign::where('status', 'completed')
            ->whereIn('call_freq', ['weekly', 'monthly', 'quarterly'])
            ->get();

        foreach ($completedRecurring as $recurr) {
            try {
                try {
                    if (!empty($recurr->launch_date)) {
                        // launch_date stores only date; use start of day as last launch
                        $lastLaunch = Carbon::parse($recurr->launch_date)->startOfDay();
                    } else {
                        Log::error("ProcessAiCamp: no launch_date for campaign {$recurr->campaign_id}");
                        continue;
                    }
                } catch (\Exception $e) {
                    Log::error("ProcessAiCamp: failed to parse launch_date for campaign {$recurr->campaign_id} - " . $e->getMessage());
                    continue;
                }

                $nextLaunch = $lastLaunch->copy();

                switch ($recurr->call_freq) {
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
                        Log::error("ProcessAiCamp: failed to parse expire_after for campaign {$recurr->campaign_id} - " . $e->getMessage());
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

                    echo "Relaunching ai call campaign of id {$recurr->campaign_id}\n";

                    // reset live rows for this campaign
                    $liveRows = AiCallCampLive::where('campaign_id', $recurr->campaign_id)->get();
                    foreach ($liveRows as $live) {
                        try {
                            try {
                                $currentSend = Carbon::parse($live->send_time);
                                $newSend = Carbon::createFromFormat('Y-m-d H:i:s', $nextLaunch->toDateString() . ' ' . $currentSend->format('H:i:s'));
                            } catch (\Exception $e) {
                                // fallback: if parsing fails, use nextLaunch at startOfDay
                                $newSend = $nextLaunch->copy()->startOfDay();
                            }

                            $live->update([
                                'calls_sent' => 0,
                                'training_assigned' => 0,
                                'compromised' => 0,
                                'call_send_response' => null,
                                'call_end_response' => null,
                                'call_report' => null,
                                'status' => 'pending',
                                'send_time' => $newSend,
                            ]);
                        } catch (\Exception $e) {
                            Log::error("ProcessWhatsapp: failed to reset whatsapp {$live->id} for campaign {$recurr->campaign_id} - " . $e->getMessage());
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("ProcessQuishing: error while relaunching campaign {$recurr->campaign_id} - " . $e->getMessage());
                continue;
            }
        }
    }
}
