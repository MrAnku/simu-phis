<?php

namespace App\Http\Controllers;

use App\Mail\TrainingAssignedEmail;
use App\Models\Settings;
use Illuminate\Support\Str;
use App\Models\CampaignLive;
use App\Models\Company;
use App\Models\PhishingWebsite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use \App\Models\TprmCampaignLive;

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

            // Check if the campaign is whitelabeled
            // $getBranding = $this->checkWhitelabeled($campid);

            // Check if the campaign exists for the given user
            $user = DB::table('campaign_live')
                ->where('id', $campid)
                ->where('user_id', $userid)
                ->first();

            if ($user) {
                $userTrainingModule = $user->training_module;
                $usercampaignId = $user->campaign_id;

                // Fetch user training module name
                $userTrainingModuleName = DB::table('training_modules')
                    ->where('id', $userTrainingModule)
                    ->first();

                if ($userTrainingModule != '') {
                    $userName = $user->user_name;
                    $company_id = $user->company_id;
                    $campId2 = $user->campaign_id;
                    $userLoginEmail = $user->user_email;
                    $training_lang = $user->training_lang;
                    $training_type = $user->training_type;
                    $userLoginPass = $this->generateRandom();

                    // Check if training is already assigned to the user
                    $checkAssignedUser = DB::table('training_assigned_users')
                        ->where('user_id', $userid)
                        ->where('training', $userTrainingModule)
                        ->first();

                    if ($checkAssignedUser) {
                        $checkAssignedUseremail = $checkAssignedUser->user_email;

                        // Fetch user credentials
                        $userCredentials = DB::table('user_login')
                            ->where('login_username', $checkAssignedUseremail)
                            ->first();

                        $checkAssignedUserLoginEmail = $userCredentials->login_username;
                        $checkAssignedUserLoginPass = $userCredentials->login_password;

                        $learnSiteAndLogo = $this->checkWhitelabeled($company_id);

                        $mailData = [
                            'user_name' => $userName,
                            'training_name' => $userTrainingModuleName->name,
                            'login_email' => $checkAssignedUserLoginEmail,
                            'login_pass' => $checkAssignedUserLoginPass,
                            'company_name' => $learnSiteAndLogo['company_name'],
                            'company_email' => $learnSiteAndLogo['company_email'],
                            'learning_site' => $learnSiteAndLogo['learn_domain'],
                            'logo' => $learnSiteAndLogo['logo']
                        ];

                        Mail::to($checkAssignedUserLoginEmail)->send(new TrainingAssignedEmail($mailData));

                        // Generate mail body and send email
                        // $mailBody = $this->generateAlertMailBody($userTrainingModuleName->name, $checkAssignedUserLoginEmail, $checkAssignedUserLoginPass, $getBranding->learn_domain, $getBranding->logo);
                        // $this->assignTrainingMail($checkAssignedUserLoginEmail, $mailBody, $getBranding->company_name);
                    } else {
                        // Check if user login already exists
                        $checkLoginExist = DB::table('user_login')
                            ->where('login_username', $userLoginEmail)
                            ->first();

                        if ($checkLoginExist) {
                            $checkAssignedUserLoginEmail = $checkLoginExist->login_username;
                            $checkAssignedUserLoginPass = $checkLoginExist->login_password;

                            // Insert into training_assigned_users table
                            $current_date = now()->toDateString();
                            $date_after_14_days = now()->addDays(14)->toDateString();
                            $res2 = DB::table('training_assigned_users')
                                ->insert([
                                    'campaign_id' => $usercampaignId,
                                    'user_id' => $userid,
                                    'user_name' => $userName,
                                    'user_email' => $userLoginEmail,
                                    'training' => $userTrainingModule,
                                    'training_lang' => $training_lang,
                                    'training_type' => $training_type,
                                    'assigned_date' => $current_date,
                                    'training_due_date' => $date_after_14_days,
                                    'company_id' => $company_id
                                ]);

                            if ($res2) {
                                // echo "user created successfully";

                                $learnSiteAndLogo = $this->checkWhitelabeled($company_id);

                                $mailData = [
                                    'user_name' => $userName,
                                    'training_name' => $userTrainingModuleName->name,
                                    'login_email' => $checkAssignedUserLoginEmail,
                                    'login_pass' => $checkAssignedUserLoginPass,
                                    'company_name' => $learnSiteAndLogo['company_name'],
                                    'company_email' => $learnSiteAndLogo['company_email'],
                                    'learning_site' => $learnSiteAndLogo['learn_domain'],
                                    'logo' => $learnSiteAndLogo['logo']
                                ];

                                Mail::to($checkAssignedUserLoginEmail)->send(new TrainingAssignedEmail($mailData));

                                // Generate mail body and send email
                                // $mailBody = $this->generateAlertMailBody($userTrainingModuleName->name, $checkAssignedUserLoginEmail, $checkAssignedUserLoginPass, $getBranding->learn_domain, $getBranding->logo);
                                // $this->assignTrainingMail($userLoginEmail, $mailBody, $getBranding->company_name);

                                // Update campaign_live table
                                DB::table('campaign_live')
                                    ->where('id', $campid)
                                    ->update(['training_assigned' => 1]);

                                // Update campaign_reports table
                                $reportsTrainingAssignCount = DB::table('campaign_reports')
                                    ->where('campaign_id', $campId2)
                                    ->first();

                                if ($reportsTrainingAssignCount) {
                                    $training_assigned = (int)$reportsTrainingAssignCount->training_assigned + 1;

                                    DB::table('campaign_reports')
                                        ->where('campaign_id', $campId2)
                                        ->update(['training_assigned' => $training_assigned]);
                                }
                            } else {
                                return response()->json(['error' => 'Failed to create user']);
                            }
                        } else {
                            // Insert into training_assigned_users and user_login tables
                            $current_date = now()->toDateString();
                            $date_after_14_days = now()->addDays(14)->toDateString();

                            $res2 = DB::table('training_assigned_users')
                                ->insert([
                                    'campaign_id' => $usercampaignId,
                                    'user_id' => $userid,
                                    'user_name' => $userName,
                                    'user_email' => $userLoginEmail,
                                    'training' => $userTrainingModule,
                                    'training_lang' => $training_lang,
                                    'training_type' => $training_type,
                                    'assigned_date' => $current_date,
                                    'training_due_date' => $date_after_14_days,
                                    'company_id' => $company_id
                                ]);

                            $res3 = DB::table('user_login')
                                ->insert([
                                    'user_id' => $userid,
                                    'login_username' => $userLoginEmail,
                                    'login_password' => $userLoginPass
                                ]);

                            if ($res2 && $res3) {
                                // echo "user created successfully";

                                $learnSiteAndLogo = $this->checkWhitelabeled($company_id);

                                $mailData = [
                                    'user_name' => $userName,
                                    'training_name' => $userTrainingModuleName->name,
                                    'login_email' => $userLoginEmail,
                                    'login_pass' => $userLoginPass,
                                    'company_name' => $learnSiteAndLogo['company_name'],
                                    'company_email' => $learnSiteAndLogo['company_email'],
                                    'learning_site' => $learnSiteAndLogo['learn_domain'],
                                    'logo' => $learnSiteAndLogo['logo']
                                ];

                                Mail::to($userLoginEmail)->send(new TrainingAssignedEmail($mailData));

                                // Generate mail body and send email
                                // $mailBody = $this->generateAlertMailBody($userTrainingModuleName->name, $userLoginEmail, $userLoginPass, $getBranding->learn_domain, $getBranding->logo);
                                // $this->assignTrainingMail($userLoginEmail, $mailBody, $getBranding->company_name);

                                // Update campaign_live table
                                DB::table('campaign_live')
                                    ->where('id', $campid)
                                    ->update(['training_assigned' => 1]);

                                // Update campaign_reports table
                                $reportsTrainingAssignCount = DB::table('campaign_reports')
                                    ->where('campaign_id', $campId2)
                                    ->first();

                                if ($reportsTrainingAssignCount) {
                                    $training_assigned = (int)$reportsTrainingAssignCount->training_assigned + 1;

                                    DB::table('campaign_reports')
                                        ->where('campaign_id', $campId2)
                                        ->update(['training_assigned' => $training_assigned]);
                                }
                            } else {
                                return response()->json(['error' => 'Failed to create user']);
                            }
                        }
                    }
                }
            } else {
                return response()->json(['error' => 'Invalid campaign']);
            }
        }
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
                    return response()->json(['message' => 'Payload click updated']);
                } else {
                    return response()->json(['error' => 'Failed to update payload click']);
                }
            } else {
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
                    return response()->json(['message' => 'Payload click updated']);
                } else {
                    return response()->json(['error' => 'Failed to update payload click']);
                }
            } else {
                return response()->json(['error' => 'Invalid campaign or payload click already updated']);
            }
        }
    }
}
