<?php

namespace App\Http\Controllers;

use App\Models\Users;
use Plivo\RestClient;
use App\Models\Company;
use App\Models\Campaign;
use App\Models\Settings;
use App\Models\WaCampaign;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use App\Models\CampaignLive;
use App\Models\QuishingCamp;
use Illuminate\Http\Request;
use App\Models\CampaignReport;
use App\Models\WaLiveCampaign;
use App\Models\PhishingWebsite;
use App\Models\QuishingLiveCamp;
use App\Models\SmishingCampaign;
use \App\Models\TprmCampaignLive;
use App\Models\EmailCampActivity;
use App\Models\NewLearnerPassword;
use Illuminate\Support\Facades\DB;
use App\Mail\TrainingAssignedEmail;
use App\Models\SmishingLiveCampaign;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Services\TrainingAssignedService;
use App\Mail\AssignTrainingWithPassResetLink;
use App\Models\QuishingActivity;
use Illuminate\Validation\ValidationException;

class ShowWebsiteController extends Controller
{
    public function index($dynamicvalue)
    {

        $queryParams = request()->query();

        // Access dynamic parameters
        $c = $queryParams['c'] ?? null;
        $p = $queryParams['p'] ?? null;
        $l = $queryParams['l'] ?? null;

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
                    $content = file_get_contents(env('CLOUDFRONT_URL') . $website->file);
                    return response($content)->header('Content-Type', 'text/html');
                } else {
                    abort(404);
                }
            } else {
                abort(404);
            }
        } else {
            $website = PhishingWebsite::find($p);

            // return "hello";

            if ($website) {

                DB::table('phish_websites_sessions')->insert([
                    'user' => $dynamicvalue,
                    'session' => $c,
                    'website_id' => $p,
                    'website_name' => $l,
                    'expiry' => now()->addMinutes(10)
                ]);

                $content = file_get_contents(env('CLOUDFRONT_URL') . $website->file);
                return response($content)->header('Content-Type', 'text/html');
            } else {
                abort(404);
            }
        }
    }


    public function loadjs()
    {
        $filePath = resource_path("js/gz.js");
        $content = File::get($filePath);

        // Replace the placeholder with JavaScript code that sets up AJAX headers
        $content = str_replace('//{csrf}//', "$.ajaxSetup({headers: {'X-CSRF-TOKEN':'" . csrf_token() . "'}});", $content);

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
        $qsh = $request->input('qsh');
        $smi = $request->input('smi');
        $wsh = $request->input('wsh');
        if ($qsh == 1) {
            $campDetail = QuishingLiveCamp::find($campid);
        } else if ($smi == 1) {
            $campDetail = SmishingLiveCampaign::find($campid);
        } else if ($wsh == 1) {
            $campDetail = WaLiveCampaign::find($campid);
        } else {
            $campDetail = CampaignLive::find($campid);
        }
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

    private function assignTrainingByQuishing($campid)
    {
        $campaign = QuishingLiveCamp::where('id', $campid)->first();
        if (!$campaign) {
            return response()->json(['error' => 'Invalid campaign or user']);
        }
        if ($campaign->training_module == null) {
            return response()->json(['error' => 'No training module assigned']);
        }

        //checking assignment
        $all_camp = QuishingCamp::where('campaign_id', $campaign->campaign_id)->first();

        if ($all_camp->training_assignment == 'all') {
            $trainings = json_decode($all_camp->training_module, true);
            $this->assignAllTrainings($campaign, $trainings);

            // Update campaign_live table
            $campaign->update(['sent' => '1', 'training_assigned' => '1']);
        } else {
            $this->assignSingleTraining($campaign);

            // Update campaign_live table
            $campaign->update(['sent' => '1', 'training_assigned' => '1']);
        }
    }

    private function assignTrainingBySmishing($campid)
    {
        $campaign = SmishingLiveCampaign::where('id', $campid)->first();
        if (!$campaign) {
            return response()->json(['error' => 'Invalid campaign or user']);
        }
        if ($campaign->training_module == null) {
            return response()->json(['error' => 'No training module assigned']);
        }

        //checking assignment
        $all_camp = SmishingCampaign::where('campaign_id', $campaign->campaign_id)->first();

        if ($all_camp->training_assignment == 'all') {
            $trainings = json_decode($all_camp->training_module, true);
            $this->assignAllTrainings($campaign, $trainings, true);

            // Update campaign_live table
            $campaign->update(['training_assigned' => 1]);
        } else {
            $this->assignSingleTraining($campaign, true);

            // Update campaign_live table
            $campaign->update(['training_assigned' => 1]);
        }
    }

    private function assignTrainingByWhatsapp($campid)
    {
        $campaign = WaLiveCampaign::where('id', $campid)->first();
        if (!$campaign) {
            return response()->json(['error' => 'Invalid campaign or user']);
        }
        if ($campaign->training_module == null) {
            return response()->json(['error' => 'No training module assigned']);
        }

        //checking assignment
        $all_camp = WaCampaign::where('campaign_id', $campaign->campaign_id)->first();

        if ($campaign->employee_type == 'normal') {
            if ($all_camp->training_assignment == 'all') {
                $trainings = json_decode($all_camp->training_module, true);
                $this->assignAllTrainings($campaign, $trainings);

                // Update campaign_live table
                $campaign->update(['sent' => 1, 'training_assigned' => 1]);
            } else {
                $this->assignSingleTraining($campaign);

                // Update campaign_live table
                $campaign->update(['sent' => 1, 'training_assigned' => 1]);
            }
        } else {

            // assign training to bluecollar employees
            $trainingAssigned = DB::table('blue_collar_training_users')
                ->where('user_whatsapp', $campaign->user_phone)
                ->where('training', $campaign->training_module)
                ->first();

            if ($trainingAssigned) {
                // return "Send Remainder";
                return $this->whatsappSendTrainingReminder($campaign, $trainingAssigned->id);
            } else {
                // return "Assign Training";
                return $this->whatsappAssignFirstTraining($campaign);
            }
        }
    }

    private function whatsappAssignFirstTraining($campaign)
    {
        $training_assigned = DB::table('blue_collar_training_users')
            ->insertGetId([
                'campaign_id' => $campaign->campaign_id,
                'user_id' => $campaign->user_id,
                'user_name' => $campaign->user_name,
                'user_whatsapp' => $campaign->user_phone,
                'training' => $campaign->training_module,
                'training_lang' => $campaign->training_lang,
                'training_type' => $campaign->training_type,
                'assigned_date' => now()->toDateString(),
                'training_due_date' => now()->addDays((int)$campaign->days_until_due)->toDateString(),
                'company_id' => $campaign->company_id
            ]);



        if (!$training_assigned) {
            return response()->json(['error' => __('Failed to assign training')]);
        }

        $campaign->update(['training_assigned' => 1]);

        // WhatsApp Notification
        $access_token = env('WHATSAPP_CLOUD_API_TOKEN');
        $phone_number_id = env('WHATSAPP_CLOUD_API_PHONE_NUMBER_ID');
        $whatsapp_url = "https://graph.facebook.com/v22.0/{$phone_number_id}/messages";

        $whatsapp_data = [
            "messaging_product" => "whatsapp",
            "to" => $campaign->user_phone, // Replace with actual user phone number
            "type" => "template",
            "template" => [
                "name" => "training_message",
                "language" => ["code" => "en"],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $campaign->user_name],
                            ["type" => "text", "text" => $campaign->trainingData->name],
                            ["type" => "text", "text" => "https://" . Str::random(3) . "." . env('PHISHING_WEBSITE_DOMAIN') . "/start-training/" . base64_encode($training_assigned),]
                        ]
                    ]
                ]
            ]
        ];

        $whatsapp_response = Http::withHeaders([
            "Authorization" => "Bearer {$access_token}",
            "Content-Type" => "application/json"
        ])->withOptions([
            'verify' => false
        ])->post($whatsapp_url, $whatsapp_data);


        if ($whatsapp_response->successful()) {
            log_action("Bluecollar Training Assigned | Training {$campaign->trainingData->name} assigned to {$campaign->user_phone}.", 'employee', 'employee');
        } else {
            log_action("Training assignment failed", 'employee', 'employee');
        }

        return response()->json(['success' => __('Training assigned and WhatsApp notification sent')]);
    }

    private function whatsappSendTrainingReminder($campaign, $trainingAssignedId)
    {
        // WhatsApp API Configuration
        $access_token = env('WHATSAPP_CLOUD_API_TOKEN');
        $phone_number_id = env('WHATSAPP_CLOUD_API_PHONE_NUMBER_ID');
        $whatsapp_url = "https://graph.facebook.com/v22.0/{$phone_number_id}/messages";


        $whatsapp_data = [
            "messaging_product" => "whatsapp",
            "to" => $campaign->user_phone, // Replace with actual user phone number
            "type" => "template",
            "template" => [
                "name" => "training_message",
                "language" => ["code" => "en"],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $campaign->user_name],
                            ["type" => "text", "text" => $campaign->trainingData->name],
                            ["type" => "text", "text" => "https://" . Str::random(3) . "." . env('PHISHING_WEBSITE_DOMAIN') . "/start-training/" . base64_encode($trainingAssignedId)],
                        ]
                    ]
                ]
            ]
        ];

        // Send WhatsApp message

        $whatsapp_response = Http::withHeaders([
            "Authorization" => "Bearer {$access_token}",
            "Content-Type" => "application/json"
        ])->withOptions([
            'verify' => false
        ])->post($whatsapp_url, $whatsapp_data);


        if ($whatsapp_response->successful()) {
            log_action("Bluecolar training Reminder Sent | Training {$campaign->trainingData->name} assigned to {$campaign->user_phone}.", 'employee', 'employee');
            return response()->json(['success' => __('Training reminder sent via WhatsApp')]);
        } else {
            return response()->json([
                'error' => __('Failed to send WhatsApp message'),
                'status' => $whatsapp_response->status(),
                'response' => $whatsapp_response->body()
            ], 500);
        }
    }



    public function assignTraining(Request $request)
    {
        if ($request->has('assignTraining')) {
            $campid = $request->input('campid');
            $userid = $request->input('userid');
            $qsh = $request->input('qsh');
            $smi = $request->input('smi');
            $wsh = $request->input('wsh');

            // Quishing Campaign
            if ($qsh == 1) {
                $this->assignTrainingByQuishing($campid);
                return;
            }
            if ($smi == 1) {
                $this->assignTrainingBySmishing($campid);
                $this->sendTrainingSms($campid);
                return;
            }
            if ($wsh == 1) {
                $this->assignTrainingByWhatsapp($campid);
                return;
            }

            // =======================================
            // Email Campaign 
            $campaign = CampaignLive::where('id', $campid)->first();

            if (!$campaign) {
                return response()->json(['error' => 'Invalid campaign or user']);
            }

            if ($campaign->training_module == null) {

                return response()->json(['error' => 'No training module assigned']);
            }

            //checking assignment
            $all_camp = Campaign::where('campaign_id', $campaign->campaign_id)->first();

            if ($all_camp->training_assignment == 'all') {
                $trainings = json_decode($all_camp->training_module, true);
                $this->assignAllTrainings($campaign, $trainings);

                // Update campaign_live table
                $campaign->update(['sent' => 1, 'training_assigned' => 1]);

                // Update campaign_reports table
                $updateReport = CampaignReport::where('campaign_id', $campaign->campaign_id)
                    ->increment('training_assigned');
            } else {
                $this->assignSingleTraining($campaign);

                // Update campaign_live table
                $campaign->update(['sent' => 1, 'training_assigned' => 1]);

                // Update campaign_reports table
                $updateReport = CampaignReport::where('campaign_id', $campaign->campaign_id)
                    ->increment('training_assigned');
            }
        }
    }

    private function assignAllTrainings($campaign, $trainings, $smishing = false)
    {
        $trainingAssignedService = new TrainingAssignedService();

        if ($smishing) {
            $user_email = Users::find($campaign->user_id)->user_email;
        } else {
            $user_email = $campaign->user_email;
        }

        foreach ($trainings as $training) {

            //check if this training is already assigned to this user
            $assignedTraining = TrainingAssignedUser::where('user_email', $user_email)
                ->where('training', $training)
                ->first();

            if (!$assignedTraining) {
                //call assignNewTraining from service method
                $campData = [
                    'campaign_id' => $campaign->campaign_id,
                    'user_id' => $campaign->user_id,
                    'user_name' => $campaign->user_name,
                    'user_email' => $user_email,
                    'training' => $training,
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
                    echo 'Failed to assign training to ' . $user_email;
                }
            }
        }

        //send mail to user
        $campData = [
            'user_name' => $campaign->user_name,
            'user_email' => $user_email,
            'company_id' => $campaign->company_id
        ];
        $isMailSent = $trainingAssignedService->sendTrainingEmail($campData);

        if ($isMailSent['status'] == true) {

            echo $isMailSent['msg'];
        } else {
            echo 'Failed to send mail to ' . $user_email;
        }
    }

    private function assignSingleTraining($campaign, $smishing = false)

    {
        $trainingAssignedService = new TrainingAssignedService();

        if ($smishing) {
            $user_email = Users::find($campaign->user_id)->user_email;
        } else {
            $user_email = $campaign->user_email;
        }

        $assignedTraining = TrainingAssignedUser::where('user_email', $user_email)
            ->where('training', $campaign->training_module)
            ->first();

        if (!$assignedTraining) {
            //call assignNewTraining from service method
            $campData = [
                'campaign_id' => $campaign->campaign_id,
                'user_id' => $campaign->user_id,
                'user_name' => $campaign->user_name,
                'user_email' => $user_email,
                'training' => $campaign->training_module,
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
                echo 'Failed to assign training to ' . $user_email;
            }
        }

        //send mail to user
        $campData = [
            'user_name' => $campaign->user_name,
            'user_email' => $user_email,
            'company_id' => $campaign->company_id
        ];
        $isMailSent = $trainingAssignedService->sendTrainingEmail($campData);

        if ($isMailSent['status'] == true) {
            echo $isMailSent['msg'];
        } else {
            echo 'Failed to send mail to ' . $user_email;
        }
    }

    private function sendAlertSms($campaign)
    {
        try {
            $client = new RestClient(
                env('PLIVO_AUTH_ID'),
                env('PLIVO_AUTH_TOKEN')
            );
            if ($campaign->training_module == null) {
                $msgBody = "Oops! You were in attack! Don't worry this is just for test. This simulation is part of our ongoing efforts to improve cybersecurity awareness. Thank you for your cooperation.";
            } else {
                $msgBody = "Oops! You were in attack! This simulation is part of our ongoing efforts to improve cybersecurity awareness. Please complete the training sent to your email to enhance your awareness and security. Thank you for your cooperation.";
            }


            $response = $client->messages->create(
                [
                    "src" => env('PLIVO_MOBILE_NUMBER'),
                    "dst" => $campaign->user_phone,
                    "text"  => $msgBody
                ]
            );
            return response()->json([
                'status' => 'success',
                'message' => __('SMS sent successfully'),
                'response' => $response
            ]);
        } catch (\Plivo\Exceptions\PlivoRestException $e) {
            // Handle the Plivo exception
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send SMS: ' . $e->getMessage()
            ]);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }

    private function sendTrainingSms($campid)
    {
        $campaign = SmishingLiveCampaign::where('id', $campid)->first();
        if (!$campaign) {
            return response()->json(['error' => 'Invalid campaign or user']);
        }

        if ($campaign->training_module == null) {
            return response()->json(['error' => 'No training module assigned']);
        }


        try {
            $client = new RestClient(
                env('PLIVO_AUTH_ID'),
                env('PLIVO_AUTH_TOKEN')
            );

            $msgBody = "Training assigned! Please check your email for the training. This simulation is part of our ongoing efforts to improve cybersecurity awareness. Thank you for your cooperation.";

            $response = $client->messages->create(
                [
                    "src" => env('PLIVO_MOBILE_NUMBER'),
                    "dst" => $campaign->user_phone,
                    "text"  => $msgBody
                ]
            );
            return response()->json([
                'status' => 'success',
                'message' => __('SMS sent successfully'),
                'response' => $response
            ]);
        } catch (\Plivo\Exceptions\PlivoRestException $e) {
            // Handle the Plivo exception
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send SMS: ' . $e->getMessage()
            ]);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }

    public function handleCompromisedEmail(Request $request)
    {
        if ($request->has('emailCompromised')) {
            $campid = $request->input('campid');
            $userid = $request->input('userid');
            $qsh = $request->input('qsh');
            $smi = $request->input('smi');
            $wsh = $request->input('wsh');

            if ($qsh == 1) {
                QuishingLiveCamp::where('id', $campid)->where('compromised', '0')->update(['compromised' => '1']);

                $agent = new Agent();

                $clientData = [
                    'platform' => $agent->platform(), // Extract OS
                    'browser' => $agent->browser(), // Extract Browser
                    'os' => $agent->platform() . ' ' . $agent->version($agent->platform()), // OS + Version
                    'ip' => $request->ip(), // Client IP Address
                    'source' => $request->header('User-Agent'), // Full User-Agent string
                    'browserVersion' => $agent->version($agent->browser()),
                    'device' => $agent->device(),
                    'isMobile' => $agent->isMobile(),
                    'isDesktop' => $agent->isDesktop(),

                ];
                QuishingActivity::where('campaign_live_id', $campid)
                    ->update([
                        'compromised_at' => now(),
                        'client_details' => json_encode($clientData)
                    ]);

                return;
            }

            if ($smi == 1) {
                SmishingLiveCampaign::where('id', $campid)->where('compromised', 0)->update(['compromised' => 1]);
                return;
            }

            if ($wsh == 1) {
                WaLiveCampaign::where('id', $campid)->where('compromised', 0)->update(['compromised' => 1]);
                return;
            }

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



                $agent = new Agent();

                $clientData = [
                    'platform' => $agent->platform(), // Extract OS
                    'browser' => $agent->browser(), // Extract Browser
                    'os' => $agent->platform() . ' ' . $agent->version($agent->platform()), // OS + Version
                    'ip' => $request->ip(), // Client IP Address
                    'source' => $request->header('User-Agent'), // Full User-Agent string
                    'browserVersion' => $agent->version($agent->browser()),
                    'device' => $agent->device(),
                    'isMobile' => $agent->isMobile(),
                    'isDesktop' => $agent->isDesktop(),

                ];
                EmailCampActivity::where('campaign_live_id', $campid)
                    ->update([
                        'compromised_at' => now(),
                        'client_details' => json_encode($clientData)
                    ]);

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
            $qsh = $request->input('qsh');
            $smi = $request->input('smi');
            $wsh = $request->input('wsh');

            if ($qsh == 1) {
                QuishingLiveCamp::where('id', $campid)->update(['qr_scanned' => '1']);
                QuishingActivity::where('campaign_live_id', $campid)->update(['payload_clicked_at' => now()]);
                return;
            }
            if ($smi == 1) {
                SmishingLiveCampaign::where('id', $campid)->update(['payload_clicked' => 1]);
                return;
            }
            if ($wsh == 1) {
                WaLiveCampaign::where('id', $campid)->update(['payload_clicked' => 1]);
                return;
            }

            $campaign = CampaignLive::where('id', $campid)->where('payload_clicked', 0)->first();

            if ($campaign) {
                $campaign->update(['payload_clicked' => 1]);

                CampaignReport::where('campaign_id', $campaign->campaign_id)->increment('payloads_clicked');

                EmailCampActivity::where('campaign_live_id', $campid)->update(['payload_clicked_at' => now()]);

                log_action("Phishing email payload clicked by {$campaign->user_email}", 'employee', 'employee');
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
