<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\AiCallCampaign;
use App\Models\AiCallCampLive;
use App\Models\BlueCollarEmployee;
use App\Models\BlueCollarScormAssignedUser;
use App\Models\BlueCollarTrainingUser;
use Illuminate\Console\Command;
use App\Models\ScormAssignedUser;
use App\Models\ScormTraining;
use Illuminate\Support\Facades\DB;
use App\Models\TrainingAssignedUser;
use App\Models\TrainingModule;
use App\Models\Users;
use App\Models\UsersGroup;
use App\Services\BlueCollarCampTrainingService;
use App\Services\BlueCollarWhatsappService;
use App\Services\CampaignTrainingService;
use Illuminate\Support\Facades\Http;
use App\Services\TrainingAssignedService;
use Illuminate\Support\Carbon;

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
                $pendingCalls = AiCallCampLive::where('company_id', $company->company_id)
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
                }
            } catch (\Exception $e) {
                echo "Something went wrong " . $e->getMessage();
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
                if ($campaign->launch_type === 'schedule' && $campaign->status === 'pending') {
                    $scheduledTime = Carbon::parse($campaign->launch_time);
                    $currentDateTime = Carbon::now();

                    if ($scheduledTime->lessThanOrEqualTo($currentDateTime)) {
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



            // $userIdsJson = UsersGroup::where('group_id', $campaign->users_group)->value('users');
            // $userIds = json_decode($userIdsJson, true);
            // $users = Users::whereIn('id', $userIds)->get();
            if ($users) {
                foreach ($users as $user) {

                    if ($user->whatsapp == null) {
                        continue;
                    }

                    $training_mods = json_decode($campaign->training_module, true);
                    $scorms = json_decode($campaign->scorm_training, true);

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

    private function analyseAicallReports()
    {
        // Retrieve the log JSON data
        $logData = DB::table('ai_call_all_logs')->where('locally_handled', 0)->get();

        if ($logData) {

            foreach ($logData as $singleLog) {

                $logJson = json_decode($singleLog->log_json, true);

                // Ensure call_id exists in the JSON
                if (isset($logJson['call']['call_id'])) {
                    $callId = $logJson['call']['call_id'];

                    // Check if call_id exists in the other table (e.g., 'other_table_name')
                    $existingRow = AiCallCampLive::where('call_id', $callId)->first();

                    if ($existingRow) {

                        if ($logJson['args']['fell_for_simulation'] == true) {

                            if ($existingRow->training_module !== null) {

                                $this->assignTraining($existingRow);
                                $existingRow->update(['training_assigned' => 1]);
                            }

                            if ($existingRow->scorm_training !== null) {

                                $this->assignTraining($existingRow);
                                $existingRow->update(['training_assigned' => 1]);
                            }
                        }

                        // If exists, update the JSON column of the found row
                        $updatedJson = json_encode($logJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                        DB::table('ai_call_camp_live')->where('id', $existingRow->id)->update([
                            // 'call_time' => $logJson['start_timestamp'] ?? null,
                            'training_assigned' => $logJson['args']['fell_for_simulation'] == true && $existingRow->training_module !== null ? 1 : 0,
                            'compromised' => $logJson['args']['fell_for_simulation'] == true ? 1 : 0,
                            'status' => 'completed',
                            'call_end_response' => $updatedJson
                        ]);


                        DB::table('ai_call_all_logs')->where('id', $singleLog->id)->update([
                            'locally_handled' => 1
                        ]);
                    }
                }
            }
        }
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
}
