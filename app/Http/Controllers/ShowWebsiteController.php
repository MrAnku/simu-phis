<?php

namespace App\Http\Controllers;

use App\Mail\TrainingAssignedEmail;
use App\Models\Settings;
use Illuminate\Support\Str;
use App\Models\CampaignLive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

class ShowWebsiteController extends Controller
{
    public function index($websitefile)
    {

        $filePath = storage_path("app/public/uploads/phishingMaterial/phishing_websites/{$websitefile}");

        if (File::exists($filePath)) {
            $content = File::get($filePath);
            return response($content)->header('Content-Type', 'text/html');
        } else {
            abort(404, 'Not Found');
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

                        $mailData = [
                            'training_name' => $userTrainingModuleName->name,
                            'login_email' => $checkAssignedUserLoginEmail,
                            'login_pass' => $checkAssignedUserLoginPass,
                            'learning_site' => 'https://learn.simuphish.com',
                            'logo' => 'https://simuphish.com/wp-content/uploads/2023/09/simu-logo-main-01-1024x312.png'
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
                                    'assigned_date' => $current_date,
                                    'training_due_date' => $date_after_14_days,
                                    'company_id' => $company_id
                                ]);

                            if ($res2) {
                                // echo "user created successfully";

                                $mailData = [
                                    'training_name' => $userTrainingModuleName->name,
                                    'login_email' => $checkAssignedUserLoginEmail,
                                    'login_pass' => $checkAssignedUserLoginPass,
                                    'learning_site' => 'https://learn.simuphish.com',
                                    'logo' => 'https://simuphish.com/wp-content/uploads/2023/09/simu-logo-main-01-1024x312.png'
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

                                $mailData = [
                                    'training_name' => $userTrainingModuleName->name,
                                    'login_email' => $userLoginEmail,
                                    'login_pass' => $userLoginPass,
                                    'learning_site' => 'https://learn.simuphish.com',
                                    'logo' => 'https://simuphish.com/wp-content/uploads/2023/09/simu-logo-main-01-1024x312.png'
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
    private function checkWhitelabeled($campid)
    {
        // Implement your logic to check whitelabeling here
        // For example:
        return DB::table('whitelabels')
            ->where('id', $campid)
            ->first();
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
}
