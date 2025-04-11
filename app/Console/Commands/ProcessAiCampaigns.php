<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\AiCallCampaign;
use App\Models\AiCallCampLive;
use App\Models\TrainingModule;
use Illuminate\Console\Command;
use App\Models\NewLearnerPassword;
use Illuminate\Support\Facades\DB;
use App\Mail\TrainingAssignedEmail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\AssignTrainingWithPassResetLink;
use App\Models\TrainingAssignedUser;

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
        $pendingCalls = AiCallCampLive::where('status', 'pending')->take(5)->get();

        $url = 'https://api.retellai.com/v2/create-phone-call';

        foreach ($pendingCalls as $pendingCall) {
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
                // print_r($response->json()) ;
                $pendingCall->call_id = $response['call_id'];
                $pendingCall->call_send_response = $response->json();
                $pendingCall->status = 'waiting';
                $pendingCall->save();
            } else {
                // Handle the error, e.g., log the error or throw an exception
                echo [
                    'error' => 'Unable to fetch agents',
                    'status' => $response->status(),
                    'message' => $response->body()
                ];
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

                                $this->sendTrainingAi($existingRow);
                            }
                        }

                        // If exists, update the JSON column of the found row
                        $updatedJson = json_encode($logJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                        DB::table('ai_call_camp_live')->where('id', $existingRow->id)->update([
                            // 'call_time' => $logJson['start_timestamp'] ?? null,
                            'training_assigned' => $logJson['args']['fell_for_simulation'] == true && $existingRow->training !== null ? 1 : 0,
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

    private function sendTrainingAi($campaign)
    {
        // Check if training is already assigned to the user
        $checkAssignedUser = DB::table('training_assigned_users')
            ->where('user_email', $campaign->employee_email)
            ->where('training', $campaign->training)
            ->first();

        if ($checkAssignedUser) {
            $this->sendTrainingReminder($checkAssignedUser, $campaign);
            echo "Training reminder sent to " . $campaign->employee_email . "\n";
        } else {
            // Check if user login already exists
            $checkLoginExist = DB::table('user_login')
                ->where('login_username', $campaign->employee_email)
                ->first();

            if ($checkLoginExist) {
                $this->assignAnotherTraining($checkLoginExist, $campaign);
                echo "Another training assigned to " . $campaign->employee_email . "\n";
            } else {
                $this->assignFirstTraining($campaign);
                echo "First training assigned to " . $campaign->employee_email . "\n";
            }
        }
    }

    private function assignFirstTraining($campaign)
    {
        // Insert into training_assigned_users and user_login tables

        $res2 = DB::table('training_assigned_users')
            ->insert([
                'campaign_id' => $campaign->campaign_id,
                'user_id' => $campaign->user_id,
                'user_name' => $campaign->employee_name,
                'user_email' => $campaign->employee_email,
                'training' => $campaign->training,
                'training_lang' => $campaign->training_lang,
                'training_type' => $campaign->training_type,
                'assigned_date' => now()->toDateString(),
                'training_due_date' => now()->addDays(14)->toDateString(),
                'company_id' => $campaign->company_id
            ]);

        $learnSiteAndLogo = $this->checkWhitelabeled($campaign->company_id);

        $token = encrypt($campaign->employee_email);

        // $token = Hash::make($campaign->user_email);
        $learning_dashboard_link = env('SIMUPHISH_LEARNING_URL') . '/training-dashboard/' . $token;
        DB::table('learnerloginsession')
            ->insert([
                'token' => $token,
                'email' => $campaign->employee_email,
                'expiry' => now()->addHours(24), // Ensure it expires in 24 hours
                'created_at' => now(), // Ensure ordering works properly
            ]);
        $passwordGenLink = env('APP_URL') . '/learner/create-password/' . $token;

        $mailData = [
            'user_name' => $campaign->employee_name,
            'training_name' => $this->trainingModuleName($campaign->training),
            'password_create_link' => $learning_dashboard_link,
            'company_name' => $learnSiteAndLogo['company_name'],
            'company_email' => $learnSiteAndLogo['company_email'],
            'learning_site' => $learning_dashboard_link,
            'logo' => $learnSiteAndLogo['logo']
        ];

        $allAssignedTrainings = TrainingAssignedUser::with('trainingData', 'trainingGame')->where('user_email', $campaign->employee_email)->get();

        $trainingNames = $allAssignedTrainings->map(function ($training) {
            if ($training->training_type == 'games') {
                return $training->trainingGame->name;
            }
            return $training->trainingData->name;
        });

        Mail::to($campaign->employee_email)->send(new AssignTrainingWithPassResetLink($mailData, $trainingNames));

        NewLearnerPassword::create([
            'email' => $campaign->employee_email,
            'token' => $token
        ]);
        $campaign->training_assigned = 1;
        $campaign->save();
    }

    private function assignAnotherTraining($checkLoginExist, $campaign)
    {


        // Insert into training_assigned_users table
        $res2 = DB::table('training_assigned_users')
            ->insert([
                'campaign_id' => $campaign->campaign_id,
                'user_id' => $campaign->user_id,
                'user_name' => $campaign->employee_name,
                'user_email' => $campaign->employee_email,
                'training' => $campaign->training,
                'training_lang' => $campaign->training_lang,
                'training_type' => $campaign->training_type,
                'assigned_date' => now()->toDateString(),
                'training_due_date' => now()->addDays(14)->toDateString(),
                'company_id' => $campaign->company_id
            ]);

        $learnSiteAndLogo = $this->checkWhitelabeled($campaign->company_id);
        $token = encrypt($campaign->employee_email);

        // $token = Hash::make($campaign->user_email);
        $learning_dashboard_link = env('SIMUPHISH_LEARNING_URL') . '/training-dashboard/' . $token;
        DB::table('learnerloginsession')
            ->insert([
                'token' => $token,
                'email' => $campaign->employee_email,
                'expiry' => now()->addHours(24), // Ensure it expires in 24 hours
                'created_at' => now(), // Ensure ordering works properly
            ]);
        $mailData = [
            'user_name' => $campaign->employee_name,
            'training_name' => $this->trainingModuleName($campaign->training),
            'login_email' => $checkLoginExist->login_username,
            'login_pass' => $checkLoginExist->login_password,
            'company_name' => $learnSiteAndLogo['company_name'],
            'company_email' => $learnSiteAndLogo['company_email'],
            'learning_site' =>  $learning_dashboard_link,
            'logo' => $learnSiteAndLogo['logo']
        ];

        $allAssignedTrainings = TrainingAssignedUser::with('trainingData', 'trainingGame')->where('user_email', $campaign->employee_email)->get();

        $trainingNames = $allAssignedTrainings->map(function ($training) {
            if ($training->training_type == 'games') {
                return $training->trainingGame->name;
            }
            return $training->trainingData->name;
        });

        $isMailSent = Mail::to($campaign->employee_email)->send(new TrainingAssignedEmail($mailData, $trainingNames));

        $campaign->training_assigned = 1;
        $campaign->save();
    }

    private function sendTrainingReminder($checkAssignedUser, $campaign)
    {
        $checkAssignedUseremail = $checkAssignedUser->user_email;

        // Fetch user credentials
        $userCredentials = DB::table('user_login')
            ->where('login_username', $checkAssignedUseremail)
            ->first();

        $checkAssignedUserLoginEmail = $userCredentials->login_username;
        $checkAssignedUserLoginPass = $userCredentials->login_password;

        $learnSiteAndLogo = $this->checkWhitelabeled($campaign->company_id);
        $token = encrypt($campaign->employee_email);

        // $token = Hash::make($campaign->user_email);
        $learning_dashboard_link = env('SIMUPHISH_LEARNING_URL') . '/training-dashboard/' . $token;
        DB::table('learnerloginsession')
            ->insert([
                'token' => $token,
                'email' => $campaign->employee_email,
                'expiry' => now()->addHours(24), // Ensure it expires in 24 hours
                'created_at' => now(), // Ensure ordering works properly
            ]);
        $mailData = [
            'user_name' => $campaign->employee_name,
            'training_name' => $this->trainingModuleName($campaign->training),
            'login_email' => $checkAssignedUserLoginEmail,
            'login_pass' => $checkAssignedUserLoginPass,
            'company_name' => $learnSiteAndLogo['company_name'],
            'company_email' => $learnSiteAndLogo['company_email'],
            'learning_site' => $learning_dashboard_link,
            'logo' => $learnSiteAndLogo['logo']
        ];

        $allAssignedTrainings = TrainingAssignedUser::with('trainingData', 'trainingGame')->where('user_email', $campaign->employee_email)->get();

        $trainingNames = $allAssignedTrainings->map(function ($training) {
            if ($training->training_type == 'games') {
                return $training->trainingGame->name;
            }
            return $training->trainingData->name;
        });

        $isMailSent = Mail::to($campaign->employee_email)->send(new TrainingAssignedEmail($mailData, $trainingNames));

        $campaign->training_assigned = 1;
        $campaign->save();
    }

    private function checkWhitelabeled($company_id)
    {
        $company = Company::with('partner')->where('company_id', $company_id)->first();

        $partner_id = $company->partner->partner_id;
        $company_email = $company->email;

        $isWhitelabled = DB::table('white_labelled_partner')
            ->where('partner_id', $partner_id)
            ->where('approved_by_admin', 1)
            ->first();

        if ($isWhitelabled) {
            return [
                'company_email' => $company_email,
                'learn_domain' => $isWhitelabled->learn_domain,
                'company_name' => $isWhitelabled->company_name,
                'logo' => env('APP_URL') . '/storage/uploads/whitelabeled/' . $isWhitelabled->dark_logo
            ];
        }

        return [
            'company_email' => env('MAIL_FROM_ADDRESS'),
            'learn_domain' => 'learn.simuphish.com',
            'company_name' => 'simUphish',
            'logo' => env('APP_URL') . '/assets/images/simu-logo-dark.png'
        ];
    }

    private function trainingModuleName($moduleid)
    {
        $training = TrainingModule::find($moduleid);
        return $training->name;
    }
}
