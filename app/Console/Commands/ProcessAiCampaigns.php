<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\AiCallCampaign;
use App\Models\AiCallCampLive;
use Illuminate\Console\Command;
use App\Models\ScormAssignedUser;
use Illuminate\Support\Facades\DB;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\Http;
use App\Services\TrainingAssignedService;

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
                    ->where('call_id', '!=', null)
                    ->where('status', '!=', 'pending')
                    ->where('status', '!=', 'completed')
                    ->get();
                if ($placedCalls->isEmpty()) {
                    return;
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
                        if ($placedCall->training !== null || $placedCall->scorm_training !== null) {

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

                            if ($existingRow->training !== null) {

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
                            'training_assigned' => $logJson['args']['fell_for_simulation'] == true && $existingRow->training !== null ? 1 : 0,
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
        $campaigns = AiCallCampaign::where('status', 'pending')->get();
        if ($campaigns->isEmpty()) {
            return;
        }

        foreach ($campaigns as $campaign) {
            $liveCampaigns = $campaign->individualCamps()
                ->where('status', 'pending')
                ->orWhere('status', 'waiting')
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

        if ($campaign->training !== null) {
            $assignedTrainingModule = TrainingAssignedUser::where('user_email', $campaign->employee_email)
                ->where('training', $campaign->training)
                ->first();

            if (!$assignedTrainingModule) {
                //call assignNewTraining from service method
                $campData = [
                    'campaign_id' => $campaign->campaign_id,
                    'user_id' => $campaign->user_id,
                    'user_name' => $campaign->employee_name,
                    'user_email' => $campaign->employee_email,
                    'training' => $campaign->training,
                    'training_lang' => $campaign->training_lang,
                    'training_type' => $campaign->training_type,
                    'assigned_date' => now()->toDateString(),
                    'training_due_date' => now()->addDays($campaign->days_until_due)->toDateString(),
                    'company_id' => $campaign->company_id
                ];

                $trainingAssigned = $trainingAssignedService->assignNewTraining($campData);

                if ($trainingAssigned['status'] == true) {
                    echo $trainingAssigned['msg'];
                } else {
                    echo 'Failed to assign training to ' . $campaign->employee_email;
                }
            } else {
                $assignedTrainingModule->update(
                    [
                        'training_due_date' => now()->addDays($campaign->days_until_due)->toDateString(),
                        'training_lang' => $campaign->training_lang,
                        'training_type' => $campaign->training_type,
                        'assigned_date' => now()->toDateString()
                    ]
                );
            }
        }

        if ($campaign->scorm_training !== null) {
            $assignedTrainingModule = ScormAssignedUser::where('user_email', $campaign->employee_email)
                ->where('scorm', $campaign->scorm_training)
                ->first();

            if (!$assignedTrainingModule) {
                //call assignNewTraining from service method
                $campData = [
                    'campaign_id' => $campaign->campaign_id,
                    'user_id' => $campaign->user_id,
                    'user_name' => $campaign->employee_name,
                    'user_email' => $campaign->employee_email,
                    'scorm' => $campaign->scorm_training,
                    'assigned_date' => now()->toDateString(),
                    'scorm_due_date' => now()->addDays($campaign->days_until_due)->toDateString(),
                    'company_id' => $campaign->company_id
                ];

                $trainingAssigned = $trainingAssignedService->assignNewScormTraining($campData);

                if ($trainingAssigned['status'] == true) {
                    echo $trainingAssigned['msg'];
                } else {
                    echo 'Failed to assign training to ' . $campaign->employee_email;
                }
            }
        }

        //send mail to user
        $campData = [
            'user_name' => $campaign->employee_name,
            'user_email' => $campaign->employee_email,
            'company_id' => $campaign->company_id
        ];
        $isMailSent = $trainingAssignedService->sendTrainingEmail($campData);

        if ($isMailSent['status'] == true) {
            echo $isMailSent['msg'];
        } else {
            echo 'Failed to send mail to ' . $campaign->employee_email;
        }
    }
}
