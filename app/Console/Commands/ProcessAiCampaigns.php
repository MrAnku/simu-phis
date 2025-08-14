<?php

namespace App\Console\Commands;

use App\Models\AiCallCampaign;
use App\Models\AiCallCampLive;
use App\Models\ScormAssignedUser;
use Illuminate\Console\Command;
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
        $this->processAiCalls();
        $this->analyseAicallReports();
        $this->checkAllAiCallsHandled();
    }

    private function processAiCalls()
    {
        $pendingCalls = AiCallCampLive::where('status', 'pending')->take(1)->get();

        $url = 'https://api.retellai.com/v2/create-phone-call';

        foreach ($pendingCalls as $pendingCall) {


            setCompanyTimezone($pendingCall->company_id);

            // Make the HTTP request

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('RETELL_API_KEY'),
            ])
                ->withOptions(['verify' => false])
                ->post($url, [
                    'from_number' => $pendingCall->from_mobile,
                    'to_number' => $pendingCall->to_mobile,
                    'override_agent_id' => $pendingCall->agent_id
                ]);

            // Check for a successful response
            if ($response->successful()) {
                // Return the response data
                $pendingCall->call_id = $response['call_id'];
                $pendingCall->call_send_response = $response->json();
                $pendingCall->status = 'waiting';
                $pendingCall->save();
            } else {
                // Handle the error, e.g., log the error or throw an exception
                echo "Unable to fetch agents: " . $response->body() . "\n";
            }
        }
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

        if ($campaigns->isNotEmpty()) {

            foreach ($campaigns as $campaign) {
                $liveCampaigns = AiCallCampLive::where(['campaign_id' => $campaign->campaign_id, 'status' => 'pending'])->get();

                if ($liveCampaigns->isEmpty()) {

                    AiCallCampaign::where('campaign_id', $campaign->campaign_id)->update(['status' => 'completed']);
                }
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
        }else{
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

                DB::table('scorm_assigned_users')
                    ->insert($campData);

                echo 'Scorm assigned successfully to ' . $campaign->employee_email . "\n";

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
