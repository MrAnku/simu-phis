<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Campaign;
use App\Models\Settings;
use Illuminate\Support\Str;
use App\Models\CampaignLive;
use Illuminate\Http\Request;
use App\Models\CampaignReport;
use App\Models\PhishingWebsite;
use \App\Models\TprmCampaignLive;
use App\Models\NewLearnerPassword;
use Illuminate\Support\Facades\DB;
use App\Mail\TrainingAssignedEmail;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use App\Mail\AssignTrainingWithPassResetLink;

class ShowWebsiteController extends Controller
{
    public function index($dynamicvalue)
    {

        $queryParams = request()->query();

        // Access dynamic parameters
        $c = $queryParams['c'] ?? null;
        $p = $queryParams['p'] ?? null;
        $l = $queryParams['l'] ?? null;
        $tprm = $queryParams['1'] ?? null;

        if ($tprm == '1') {

            $visited = DB::table('tprm_websites_sessions')->where([
                'user' => $dynamicvalue,
                'session' => $c,
                'website_id' => $p,
                'website_name' => $l
            ])->first();

            if ($visited) {

                if ($visited->expiry > now()) {

                    $website = PhishingWebsite::find($p);

                    if ($website) {
                        $filePath = storage_path("app/public/uploads/phishingMaterial/phishing_websites/{$website->file}");

                        if (File::exists($filePath)) {
                            $content = File::get($filePath);
                            return response($content)->header('Content-Type', 'text/html');
                        } else {
                            echo "file not found";
                        }
                    } else {
                        echo "file not found";
                    }
                } else {

                    abort(404);
                }
            } else {
                $website = PhishingWebsite::find($p);

                if ($website) {
                    $filePath = storage_path("app/public/uploads/phishingMaterial/phishing_websites/{$website->file}");

                    if (File::exists($filePath)) {

                        DB::table('tprm_websites_sessions')->insert([
                            'user' => $dynamicvalue,
                            'session' => $c,
                            'website_id' => $p,
                            'website_name' => $l,
                            'expiry' => now()->addMinutes(10)
                        ]);

                        $content = File::get($filePath);
                        return response($content)->header('Content-Type', 'text/html');
                    } else {
                        echo "file not found";
                    }
                } else {
                    echo "file not found";
                }
            }
        }
        //checking if this page is already visited and expiry

        $visited = DB::table('phish_websites_sessions')->where([
            'user' => $dynamicvalue,
            'session' => $c,
            'website_id' => $p,
            'website_name' => $l
        ])->first();

        if ($visited) {

            if ($visited->expiry > now()) {

                $website = PhishingWebsite::find($p);

                if ($website) {
                    $filePath = storage_path("app/public/uploads/phishingMaterial/phishing_websites/{$website->file}");

                    if (File::exists($filePath)) {
                        $content = File::get($filePath);
                        return response($content)->header('Content-Type', 'text/html');
                    } else {
                        echo "file not found";
                    }
                } else {
                    echo "file not found";
                }
            } else {

                abort(404);
            }
        } else {
            $website = PhishingWebsite::find($p);

            if ($website) {
                $filePath = storage_path("app/public/uploads/phishingMaterial/phishing_websites/{$website->file}");

                if (File::exists($filePath)) {

                    DB::table('phish_websites_sessions')->insert([
                        'user' => $dynamicvalue,
                        'session' => $c,
                        'website_id' => $p,
                        'website_name' => $l,
                        'expiry' => now()->addMinutes(10)
                    ]);

                    $content = File::get($filePath);
                    return response($content)->header('Content-Type', 'text/html');
                } else {
                    echo "file not found";
                }
            } else {
                echo "file not found";
            }
        }
    }


    public function loadjs()
    {
        $filePath = public_path("assets/t/gz.js");
        $content = File::get($filePath);

        // Replace the placeholder with JavaScript code that sets up AJAX headers
        $content = str_replace('//{csrf}//', "
        \$.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '" . csrf_token() . "'
            }
        });
    ", $content);

        // Return the modified JavaScript content with the correct Content-Type header
        return response($content)->header('Content-Type', 'application/javascript');
    }

    public function showAlertPage()
    {

        $filePath = public_path("assets/t/alertPage.html");
        $content = File::get($filePath);

        log_action('Email phishing | Employee fell for simulation', 'employee', 'employee');
        return response($content)->header('Content-Type', 'text/html');
    }

    public function checkWhereToRedirect(Request $request)
    {
        $campid = $request->input('campid');



        $campDetail = CampaignLive::find($campid);

        if ($campDetail) {
            $companySetting = Settings::where('company_id', $campDetail->company_id)->first();

            if ($companySetting) {
                $arr = [
                    'redirect' => $companySetting->phish_redirect,
                    'redirect_url' => $companySetting->phish_redirect_url
                ];

                return response()->json($arr);
            }
        }

        return response()->json(['error' => 'Campaign or Company Setting not found'], 404);
    }

    public function tcheckWhereToRedirect(Request $request)
    {
        $campid = $request->input('campid');



        $campDetail = TprmCampaignLive::find($campid);

        if ($campDetail) {
            $companySetting = Settings::where('company_id', $campDetail->company_id)->first();

            if ($companySetting) {
                $arr = [
                    'redirect' => $companySetting->phish_redirect,
                    'redirect_url' => $companySetting->phish_redirect_url
                ];

                return response()->json($arr);
            }
        }

        return response()->json(['error' => 'Campaign or Company Setting not found'], 404);
    }

    public function assignTraining(Request $request)
    {
        if ($request->has('assignTraining')) {
            $campid = $request->input('campid');
            $userid = $request->input('userid');


            // Check if the campaign exists for the given user
            $user = CampaignLive::where('id', $campid)
                ->first();

            if (!$user) {
                return response()->json(['error' => 'Invalid campaign or user']);
            }

            if ($user->training_module == null) {

                return response()->json(['error' => 'No training module assigned']);
            }

            //checking assignment
            $campaign = Campaign::where('campaign_id', $user->campaign_id)->first();

            if ($campaign->training_assignment == 'all') {
                $trainings = json_decode($campaign->training_module, true);
                return $this->assignAllTrainings($trainings, $user);
            } else {
                return $this->assignSingleTraining($user);
            }
        }
    }

    private function assignSingleTraining($user)
    {

        // Check if training is already assigned to the user
        $checkAssignedUser = DB::table('training_assigned_users')
            ->where('user_id', $user->user_id)
            ->where('training', $user->training_module)
            ->first();

        if ($checkAssignedUser) {

            return $this->sendTrainingReminder($checkAssignedUser, $user);
        } else {
            // Check if user login already exists
            $checkLoginExist = DB::table('user_login')
                ->where('login_username', $user->user_email)
                ->first();

            if ($checkLoginExist) {

                return $this->assignAnotherTraining($checkLoginExist, $user);
            } else {
                return $this->assignFirstTraining($user);
            }
        }
    }

    private function assignAllTrainings($trainings, $user)
    {
        foreach ($trainings as $training) {
            // Check if training is already assigned to the user
            $checkAssignedUser = DB::table('training_assigned_users')
                ->where('user_id', $user->user_id)
                ->where('training', $training)
                ->first();

            if ($checkAssignedUser) {

                $this->sendTrainingReminder($checkAssignedUser, $user);
            } else {
                // Check if user login already exists
                $checkLoginExist = DB::table('user_login')
                    ->where('login_username', $user->user_email)
                    ->first();

                if ($checkLoginExist) {

                    $this->assignAnotherTraining($checkLoginExist, $user, $training);
                } else {
                    $this->assignFirstTraining($user, $training);
                }
            }
        }
        return response()->json(['success' => 'All trainings assigned successfully']);
    }

    private function assignFirstTraining($user, $training = null)
    {

        $training_assigned = DB::table('training_assigned_users')
            ->insert([
                'campaign_id' => $user->campaign_id,
                'user_id' => $user->user_id,
                'user_name' => $user->user_name,
                'user_email' => $user->user_email,
                'training' => $training ?? $user->training_module,
                'training_lang' => $user->training_lang,
                'training_type' => $user->training_type,
                'assigned_date' => now()->toDateString(),
                'training_due_date' => now()->addDays((int)$user->days_until_due)->toDateString(),
                'company_id' => $user->company_id
            ]);

        if (!$training_assigned) {
            return response()->json(['error' => 'Failed to assign training']);
        }

        $learnSiteAndLogo = $this->checkWhitelabeled($user->company_id);
        $token = encrypt($user->user_email);

        $passwordGenLink = env('APP_URL') . '/learner/create-password/' . $token;
        $mailData = [
            'user_name' => $user->user_name,
            'training_name' => $this->trainingName($training ?? $user->training_module),
            'password_create_link' => $passwordGenLink,
            'company_name' => $learnSiteAndLogo['company_name'],
            'company_email' => $learnSiteAndLogo['company_email'],
            'learning_site' => $learnSiteAndLogo['learn_domain'],
            'logo' => $learnSiteAndLogo['logo']
        ];
        log_action("Email simulation | Training {$this->trainingName($training ??$user->training_module)} assigned to {$user->user_email}.", 'employee', 'employee');

        Mail::to($user->user_email)->send(new AssignTrainingWithPassResetLink($mailData));

        NewLearnerPassword::create([
            'email' => $user->user_email,
            'token' => $token
          ]);


        // Update campaign_live table
        $user->training_assigned = 1;
        $user->save();

        // Update campaign_reports table
        $updateReport = CampaignReport::where('campaign_id', $user->campaign_id)
            ->increment('training_assigned');


        return response()->json(['success' => 'New training assigned successfully']);

        

       
    }

    private function assignAnotherTraining($checkLoginExist, $user, $training = null)
    {

        // Insert into training_assigned_users table
        $current_date = now()->toDateString();
        $date_after_14_days = now()->addDays((int)$user->days_until_due)->toDateString();
        $res2 = DB::table('training_assigned_users')
            ->insert([
                'campaign_id' => $user->campaign_id,
                'user_id' => $user->user_id,
                'user_name' => $user->user_name,
                'user_email' => $user->user_email,
                'training' => $training ?? $user->training_module,
                'training_lang' => $user->training_lang,
                'training_type' => $user->training_type,
                'assigned_date' => $current_date,
                'training_due_date' => $date_after_14_days,
                'company_id' => $user->company_id
            ]);

        if ($res2) {
            // echo "user created successfully";

            $learnSiteAndLogo = $this->checkWhitelabeled($user->company_id);

            $mailData = [
                'user_name' => $user->user_name,
                'training_name' => $this->trainingName($training ?? $user->training_module),
                'login_email' => $checkLoginExist->login_username,
                'login_pass' => $checkLoginExist->login_password,
                'company_name' => $learnSiteAndLogo['company_name'],
                'company_email' => $learnSiteAndLogo['company_email'],
                'learning_site' => $learnSiteAndLogo['learn_domain'],
                'logo' => $learnSiteAndLogo['logo']
            ];

            log_action("Email simulation | Training {$this->trainingName($training ??$user->training_module)} assigned to {$checkLoginExist->login_username}.", 'employee', 'employee');

            Mail::to($checkLoginExist->login_username)->send(new TrainingAssignedEmail($mailData));


            // Update campaign_live table
            $user->training_assigned = 1;
            $user->save();

            // Update campaign_reports table
            $updateReport = CampaignReport::where('campaign_id', $user->campaign_id)
                ->increment('training_assigned');

            return response()->json(['success' => 'Another training assigned']);
        } else {
            return response()->json(['error' => 'Failed to create user']);
        }
    }

    private function sendTrainingReminder($assigned_user, $user)
    {

        // Fetch user credentials
        $userCredentials = DB::table('user_login')
            ->where('login_username', $assigned_user->user_email)
            ->first();

        $learnSiteAndLogo = $this->checkWhitelabeled($user->company_id);

        $mailData = [
            'user_name' => $user->user_name,
            'training_name' => $this->trainingName($user->training_module),
            'login_email' => $userCredentials->login_username,
            'login_pass' => $userCredentials->login_password,
            'company_name' => $learnSiteAndLogo['company_name'],
            'company_email' => $learnSiteAndLogo['company_email'],
            'learning_site' => $learnSiteAndLogo['learn_domain'],
            'logo' => $learnSiteAndLogo['logo']
        ];

        log_action("Email simulation | Training {$this->trainingName($user->training_module)} assigned to {$userCredentials->login_username}.", 'employee', 'employee');

        Mail::to($userCredentials->login_username)->send(new TrainingAssignedEmail($mailData));

        // Update campaign_live table
        $user->training_assigned = 1;
        $user->save();

        // Update campaign_reports table
        $updateReport = CampaignReport::where('campaign_id', $user->campaign_id)
            ->increment('training_assigned');

        return response()->json(['success' => 'Credentials sent to the employee']);
    }

    private function trainingName($training_id)
    {
        $training = DB::table('training_modules')
            ->where('id', $training_id)
            ->first();
        if ($training) {
            $trainingModuleName = $training->name;
        } else {
            $trainingModuleName = '';
        }

        return $trainingModuleName;
    }

    // Function to check if the campaign is whitelabeled
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

    // Function to generate a random password
    private function generateRandom()
    {
        // Implement your random password generation logic here
        // For example:
        return Str::random(16);
    }



    public function handleCompromisedEmail(Request $request)
    {
        if ($request->has('emailCompromised')) {
            $campid = $request->input('campid');
            $userid = $request->input('userid');

            // Check if the campaign exists and user's email is compromised
            $user = DB::table('campaign_live')
                ->where('id', $campid)
                ->where('emp_compromised', 0)
                ->where('user_id', $userid)
                ->first();

            if ($user) {
                $campId2 = $user->campaign_id;

                // Update campaign_reports table
                $reportsEmpComCount = DB::table('campaign_reports')
                    ->where('campaign_id', $campId2)
                    ->first();

                if ($reportsEmpComCount) {
                    $emp_compromised = (int)$reportsEmpComCount->emp_compromised + 1;

                    DB::table('campaign_reports')
                        ->where('campaign_id', $campId2)
                        ->update(['emp_compromised' => $emp_compromised]);
                }

                // Update campaign_live table
                $isUpdatedIndividual = DB::table('campaign_live')
                    ->where('id', $campid)
                    ->update(['emp_compromised' => 1]);

                if ($isUpdatedIndividual) {
                    log_action('Employee compromised in Email campaign', 'employee', 'employee');
                    return response()->json(['message' => 'Email compromised status updated successfully']);
                } else {

                    return response()->json(['error' => 'Failed to update email compromised status']);
                }
            } else {
                return response()->json(['error' => 'Invalid campaign or user email already compromised']);
            }
        }
    }

    public function thandleCompromisedEmail(Request $request)
    {
        if ($request->has('emailCompromised')) {
            $campid = $request->input('campid');
            $userid = $request->input('userid');

            // Check if the campaign exists and user's email is compromised
            $user = DB::table('tprm_campaign_live')
                ->where('id', $campid)
                ->where('emp_compromised', 0)
                ->where('user_id', $userid)
                ->first();

            if ($user) {
                $campId2 = $user->campaign_id;

                // Update campaign_reports table
                $reportsEmpComCount = DB::table('tprm_campaign_reports')
                    ->where('campaign_id', $campId2)
                    ->first();

                if ($reportsEmpComCount) {
                    $emp_compromised = (int)$reportsEmpComCount->emp_compromised + 1;

                    DB::table('tprm_campaign_reports')
                        ->where('campaign_id', $campId2)
                        ->update(['emp_compromised' => $emp_compromised]);
                }

                // Update campaign_live table
                $isUpdatedIndividual = DB::table('tprm_campaign_live')
                    ->where('id', $campid)
                    ->update(['emp_compromised' => 1]);

                if ($isUpdatedIndividual) {

                    log_action('Employee compromised in TPRM email campaign', 'employee', 'employee');

                    return response()->json(['message' => 'Email compromised status updated successfully']);
                } else {
                    return response()->json(['error' => 'Failed to update email compromised status']);
                }
            } else {
                return response()->json(['error' => 'Invalid campaign or user email already compromised']);
            }
        }
    }

    public function updatePayloadClick(Request $request)
    {
        if ($request->has('updatePayloadClick')) {
            $campid = $request->input('campid');
            $userid = $request->input('userid');

            // Check if the campaign exists and payload is not already clicked
            $user = DB::table('campaign_live')
                ->where('id', $campid)
                ->where('payload_clicked', 0)
                ->where('user_id', $userid)
                ->first();

            if ($user) {
                $campId2 = $user->campaign_id;

                // Update campaign_reports table
                $reportsPayloadCount = DB::table('campaign_reports')
                    ->where('campaign_id', $campId2)
                    ->first();

                if ($reportsPayloadCount) {
                    $payloads_clicked = (int)$reportsPayloadCount->payloads_clicked + 1;

                    DB::table('campaign_reports')
                        ->where('campaign_id', $campId2)
                        ->update(['payloads_clicked' => $payloads_clicked]);
                }

                // Update campaign_live table
                $isUpdatedIndividual = DB::table('campaign_live')
                    ->where('id', $campid)
                    ->update(['payload_clicked' => 1]);

                if ($isUpdatedIndividual) {

                    log_action("Phishing email payload clicked by {$user->user_email}", 'employee', 'employee');

                    return response()->json(['message' => 'Payload click updated']);
                } else {
                    return response()->json(['error' => 'Failed to update payload click']);
                }
            } else {
                log_action('Email phishing | Invalid campaign or payload click already updated', 'employee', 'employee');
                return response()->json(['error' => 'Invalid campaign or payload click already updated']);
            }
        }
    }

    public function tupdatePayloadClick(Request $request)
    {
        if ($request->has('updatePayloadClick')) {
            $campid = $request->input('campid');
            $userid = $request->input('userid');

            // Check if the campaign exists and payload is not already clicked
            $user = DB::table('tprm_campaign_live')
                ->where('id', $campid)
                ->where('payload_clicked', 0)
                ->where('user_id', $userid)
                ->first();

            if ($user) {
                $campId2 = $user->campaign_id;

                // Update campaign_reports table
                $reportsPayloadCount = DB::table('tprm_campaign_reports')
                    ->where('campaign_id', $campId2)
                    ->first();

                if ($reportsPayloadCount) {
                    $payloads_clicked = (int)$reportsPayloadCount->payloads_clicked + 1;

                    DB::table('tprm_campaign_reports')
                        ->where('campaign_id', $campId2)
                        ->update(['payloads_clicked' => $payloads_clicked]);
                }

                // Update campaign_live table
                $isUpdatedIndividual = DB::table('tprm_campaign_live')
                    ->where('id', $campid)
                    ->update(['payload_clicked' => 1]);

                if ($isUpdatedIndividual) {
                    log_action("TPRM phishing payload clicked by {$user->user_email}", 'employee', 'employee');

                    return response()->json(['message' => 'TPRM campaign payload click updated']);
                } else {
                    return response()->json(['error' => 'Failed to update payload click']);
                }
            } else {
                log_action("TPRM phishing | Invalid campaign or payload click already updated", 'employee', 'employee');
                return response()->json(['error' => 'Invalid campaign or payload click already updated']);
            }
        }
    }
}
