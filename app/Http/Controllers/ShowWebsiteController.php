<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Campaign;
use App\Models\Settings;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use App\Models\CampaignLive;
use Illuminate\Http\Request;
use App\Models\CampaignReport;
use App\Models\PhishingWebsite;
use \App\Models\TprmCampaignLive;
use App\Models\EmailCampActivity;
use App\Models\NewLearnerPassword;
use Illuminate\Support\Facades\DB;
use App\Mail\TrainingAssignedEmail;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use App\Mail\AssignTrainingWithPassResetLink;
use App\Models\QuishingCamp;
use App\Models\QuishingLiveCamp;
use App\Models\TrainingAssignedUser;
use App\Services\TrainingAssignedService;

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
        if ($qsh == 1) {
            $campDetail = QuishingLiveCamp::find($campid);
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
        }


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
            $qsh = $request->input('qsh');

            // Quishing Campaign
            if ($qsh == 1) {
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
                return;
            }

            // =====================================================
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
                return $this->assignSingleTraining($campaign);

                // Update campaign_live table
                $campaign->update(['sent' => 1, 'training_assigned' => 1]);

                // Update campaign_reports table
                $updateReport = CampaignReport::where('campaign_id', $campaign->campaign_id)
                    ->increment('training_assigned');
            }
        }
    }

    private function assignAllTrainings($campaign, $trainings)
    {
        $trainingAssignedService = new TrainingAssignedService();

        foreach ($trainings as $training) {

            //check if this training is already assigned to this user
            $assignedTraining = TrainingAssignedUser::where('user_email', $campaign->user_email)
                ->where('training', $training)
                ->first();

            if (!$assignedTraining) {
                //call assignNewTraining from service method
                $campData = [
                    'campaign_id' => $campaign->campaign_id,
                    'user_id' => $campaign->user_id,
                    'user_name' => $campaign->user_name,
                    'user_email' => $campaign->user_email,
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
                    echo 'Failed to assign training to ' . $campaign->user_email;
                }
            }
        }

        //send mail to user
        $campData = [
            'user_name' => $campaign->user_name,
            'user_email' => $campaign->user_email,
            'company_id' => $campaign->company_id
        ];
        $isMailSent = $trainingAssignedService->sendTrainingEmail($campData);

        if ($isMailSent['status'] == true) {

            echo $isMailSent['msg'];
        } else {
            echo 'Failed to send mail to ' . $campaign->user_email;
        }
    }

    private function assignSingleTraining($campaign)
    {
        $trainingAssignedService = new TrainingAssignedService();

        $assignedTraining = TrainingAssignedUser::where('user_email', $campaign->user_email)
            ->where('training', $campaign->training_module)
            ->first();

        if (!$assignedTraining) {
            //call assignNewTraining from service method
            $campData = [
                'campaign_id' => $campaign->campaign_id,
                'user_id' => $campaign->user_id,
                'user_name' => $campaign->user_name,
                'user_email' => $campaign->user_email,
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
                echo 'Failed to assign training to ' . $campaign->user_email;
            }
        }

        //send mail to user
        $campData = [
            'user_name' => $campaign->user_name,
            'user_email' => $campaign->user_email,
            'company_id' => $campaign->company_id
        ];
        $isMailSent = $trainingAssignedService->sendTrainingEmail($campData);

        if ($isMailSent['status'] == true) {
            echo $isMailSent['msg'];
        } else {
            echo 'Failed to send mail to ' . $campaign->user_email;
        }
    }

    public function handleCompromisedEmail(Request $request)
    {
        if ($request->has('emailCompromised')) {
            $campid = $request->input('campid');
            $userid = $request->input('userid');
            $qsh = $request->input('qsh');

            if ($qsh == 1) {
                $campaign = QuishingLiveCamp::where('id', $campid)->where('compromised', '0')->first();
                if ($campaign) {
                    $campaign->update(['compromised' => '1']);
                }

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

            if ($qsh == 1) {
                QuishingLiveCamp::where('id', $campid)->update(['qr_scanned' => '1']);
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