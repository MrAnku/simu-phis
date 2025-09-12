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
                    ->take(1)
                    ->get();

                $url = 'https://callapi3.sparrowhost.net/call';

                foreach ($pendingCalls as $pendingCall) {

                    if ($this->isRetellAgent($pendingCall->agent_id)) {
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
                $users = Users::whereIn('id', $userIds)->get();
            }

            if ($campaign->employee_type == 'bluecollar') {

                $users = BlueCollarEmployee::where('group_id', $campaign->users_group)->get();
            }



            // $userIdsJson = UsersGroup::where('group_id', $campaign->users_group)->value('users');
            // $userIds = json_decode($userIdsJson, true);
            // $users = Users::whereIn('id', $userIds)->get();
            if ($users) {
                foreach ($users as $user) {

                    if ($user->whatsapp == null) {
                        continue;
                    }

                    AiCallCampLive::create([
                        'campaign_id' => $campaign->campaign_id,
                        'campaign_name' => $campaign->campaign_name,
                        'employee_type' => $campaign->employee_type,
                        'user_id' => $user->id,
                        'user_name' => $user->user_name,
                        'user_email' => $user->user_email,
                        'training_module' => $campaign->training_module ?? null,
                        'scorm_training' => $campaign->scorm_training ?? null,
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

        $trainingAssignedService = new TrainingAssignedService();

        // Blue collar logic
        if ($campaign->employee_type === 'bluecollar') {

            $user_phone = ltrim($campaign->to_mobile, '+');
            // Assign normal training
            if ($campaign->training_module !== null) {
                $assignedTrainingModule = BlueCollarTrainingUser::where('user_whatsapp', $user_phone)
                    ->where('training', $campaign->training_module)
                    ->first();

                // $assignedTrainingModule = BlueCollarTrainingUser::where('user_email', $campaign->user_email)
                //     ->where('training', $campaign->training)
                //     ->first();

                if (!$assignedTrainingModule) {
                    $campData = [
                        'campaign_id' => $campaign->campaign_id,
                        'user_id' => $campaign->user_id,
                        'user_name' => $campaign->user_name,
                        'user_whatsapp' => $user_phone,
                        'training' => $campaign->training_module,
                        'training_lang' => $campaign->training_lang,
                        'training_type' => $campaign->training_type,
                        'assigned_date' => now()->toDateString(),
                        'training_due_date' => now()->addDays($campaign->days_until_due)->toDateString(),
                        'company_id' => $campaign->company_id
                    ];

                    $trainingAssigned = $trainingAssignedService->assignNewBlueCollarTraining($campData);

                    $module = TrainingModule::find($campaign->training_module);
                    audit_log(
                        $campaign->company_id,
                        $campaign->user_email,
                        null,
                        'TRAINING_ASSIGNED',
                        "{$module->name} has been assigned to {$campaign->user_email}",
                        'bluecollar'
                    );

                    if ($trainingAssigned['status'] == true) {
                        echo $trainingAssigned['msg'];
                    } else {
                        echo 'Failed to assign training to ' . $campaign->user_email;
                    }
                } else {
                    $assignedTrainingModule->update([
                        'training_due_date' => now()->addDays($campaign->days_until_due)->toDateString(),
                        'training_lang' => $campaign->training_lang,
                        'training_type' => $campaign->training_type,
                        'assigned_date' => now()->toDateString()
                    ]);
                }
            }

            // Assign SCORM training
            if ($campaign->scorm_training !== null) {
                $assignedTrainingModule = BlueCollarScormAssignedUser::where('user_whatsapp', $user_phone)
                    ->where('scorm', $campaign->scorm_training)
                    ->first();


                if (!$assignedTrainingModule) {
                    $campData = [
                        'campaign_id' => $campaign->campaign_id,
                        'user_id' => $campaign->user_id,
                        'user_name' => $campaign->user_name,
                        'user_whatsapp' => $user_phone,
                        'scorm' => $campaign->scorm_training,
                        'assigned_date' => now()->toDateString(),
                        'scorm_due_date' => now()->addDays($campaign->days_until_due)->toDateString(),
                        'company_id' => $campaign->company_id
                    ];

                    $trainingAssigned = $trainingAssignedService->assignNewBlueCollarScormTraining($campData);

                    $scorm = ScormTraining::find($campaign->scorm_training);
                    audit_log(
                        $campaign->company_id,
                        $campaign->user_email,
                        null,
                        'SCORM_ASSIGNED',
                        "{$scorm->name} has been assigned to {$campaign->user_email}",
                        'bluecollar'
                    );
                    if ($trainingAssigned['status'] == true) {
                        echo $trainingAssigned['msg'];
                    } else {
                        echo 'Failed to assign training to ' . $campaign->user_email;
                    }
                }
            }

            $trainingNames = self::getAllTrainingNames($user_phone); // returns a collection

            // Convert to comma-separated string (or keep as array if you prefer)
            $trainingNamesString = $trainingNames->implode(', ');

            // Prepare data object/array
            $data = (object)[
                'user_phone' => $user_phone,
                'user_name' => $campaign->user_name,
                'training_names' => $trainingNamesString
            ];

            $blueCollarWhatsappService = new BlueCollarWhatsappService($campaign->company_id);

            $whatsapp_response = $blueCollarWhatsappService->sendTrainingAssign($data);

            if ($whatsapp_response->successful()) {
                return true;
            } else {
                return false;
            }
        } else {
            // Normal employee logic (existing)
            $sent = CampaignTrainingService::assignTraining($campaign);

            if ($sent) {
                echo 'Training assigned successfully to ' . $campaign->user_email . "\n";
            } else {
                echo 'Failed to assign training to ' . $campaign->user_email . "\n";
            }
            $campaign->update(['sent' => 1, 'training_assigned' => 1]);
        }
    }

    private static function getAllTrainingNames($user_phone)
    {
        $allAssignedTrainings = BlueCollarTrainingUser::with('trainingData', 'trainingGame')->where('user_whatsapp', $user_phone)->get();

        $scormTrainings = BlueCollarScormAssignedUser::with('scormTrainingData')->where('user_whatsapp', $user_phone)->get();

        $trainingNames = collect();
        $scormNames = collect();

        if ($allAssignedTrainings->isNotEmpty()) {
            $trainingNames = $allAssignedTrainings->map(function ($training) {
                if ($training->training_type == 'games') {
                    return $training->trainingGame->name;
                }
                return $training->trainingData->name;
            });
        }


        if ($scormTrainings->isNotEmpty()) {
            $scormNames = $scormTrainings->map(function ($training) {

                return $training->scormTrainingData->name;
            });
        }

        $trainingNames = $trainingNames->merge($scormNames)->filter();
        return $trainingNames;
    }
}
