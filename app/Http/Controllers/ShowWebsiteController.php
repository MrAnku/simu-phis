<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\TprmUsers;
use App\Models\CampaignLive;
use Illuminate\Http\Request;
use App\Models\WaLiveCampaign;
use App\Models\CompanySettings;
use App\Models\PhishingWebsite;
use App\Models\QuishingLiveCamp;
use \App\Models\TprmCampaignLive;
use Illuminate\Support\Facades\DB;
use App\Models\SmishingLiveCampaign;
use Illuminate\Support\Facades\File;
use App\Services\InteractionHandlers\WaInteractionHandler;
use App\Services\InteractionHandlers\EmailInteractionHandler;
use App\Services\InteractionHandlers\QuishingInteractionHandler;
use App\Services\InteractionHandlers\SmishingInteractionHandler;
use App\Services\InteractionHandlers\TprmInteractionHandler;

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

    public function showAlertPage(Request $request)
    {
        $lang = $request->query('lang');
        if ($lang && $lang != 'en') {
            $filePath = resource_path("oopsPage/alertPage_{$lang}.html");
            if (!File::exists($filePath)) {
                $filePath = resource_path("oopsPage/alertPage.html");
            }
        } else {
            $filePath = resource_path("oopsPage/alertPage.html");
        }

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
        $tprm = $request->input('tprm');
        if ($qsh == 1) {
            $campDetail = QuishingLiveCamp::find($campid);
        } else if ($smi == 1) {
            $campDetail = SmishingLiveCampaign::find($campid);
        } else if ($wsh == 1) {
            $campDetail = WaLiveCampaign::find($campid);
        } else if ($tprm == 1) {
            $campDetail = TprmCampaignLive::find($campid);
        } else {
            $campDetail = CampaignLive::find($campid);
        }
        if ($campDetail) {
            $companySetting = CompanySettings::where('company_id', $campDetail->company_id)->first();

            if ($companySetting) {
                $arr = [
                    'redirect' => $companySetting->phish_redirect,
                    'redirect_url' => $companySetting->phish_redirect_url,
                    'lang' => $companySetting->default_notifications_lang,
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
            $smi = $request->input('smi');
            $wsh = $request->input('wsh');

            // Quishing Campaign
            if ($qsh == 1) {
                $handler = new QuishingInteractionHandler($campid);
                if ($handler->trainingOnClick()) {
                    return;
                }
                return $handler->assignTraining();


                // $this->assignTrainingByQuishing($campid);
                // return;
            } else if ($smi == 1) {
                $handler = new SmishingInteractionHandler($campid);
                return $handler->assignTraining();
                // $this->assignTrainingBySmishing($campid);
                // $this->sendTrainingSms($campid);
                // return;
            } else if ($wsh == 1) {
                $handler = new WaInteractionHandler($campid);
                if ($handler->trainingOnClick()) {
                    return;
                }
                return $handler->assignTraining();
            } else {
                $handler = new EmailInteractionHandler($campid);
                if ($handler->trainingOnClick()) {
                    return;
                }
                return $handler->assignTraining();
            }
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
            $tprm = $request->input('tprm');

            $companyId = Users::where('id', $userid)->value('company_id');

            if ($tprm == 1) {
                $companyId = TprmUsers::where('id', $userid)->value('company_id');
            }
            if ($wsh == 1) {
                $companyId = WaLiveCampaign::where('id', $campid)->value('company_id');
            }

            setCompanyTimezone($companyId);

            if ($qsh == 1) {
                $handler = new QuishingInteractionHandler($campid);
                return $handler->handleCompromisedEmail($companyId);
            } else if ($smi == 1) {
                $handler = new SmishingInteractionHandler($campid);
                return $handler->handleCompromisedMsg($companyId);
            } else if ($wsh == 1) {

                $handler = new WaInteractionHandler($campid);
                return $handler->handleCompromisedMsg($companyId);
            } else if ($tprm == 1) {
                $handler = new TprmInteractionHandler($campid);
                return $handler->handleCompromisedEmail($companyId);
            } else {
                $handler = new EmailInteractionHandler($campid);
                return $handler->handleCompromisedEmail($companyId);
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
            $tprm = $request->input('tprm');

            $companyId = Users::where('id', $userid)->value('company_id');

            if ($tprm == 1) {
                $companyId = TprmUsers::where('id', $userid)->value('company_id');
            }
            if ($wsh == 1) {
                $companyId = WaLiveCampaign::where('id', $campid)->value('company_id');
            }

            setCompanyTimezone($companyId);

            if ($qsh == 1) {

                $handler = new QuishingInteractionHandler($campid);
                $handler->updatePayloadClick($companyId);
                return;
            } else if ($smi == 1) {
                $handler = new SmishingInteractionHandler($campid);
                $handler->updatePayloadClick($companyId);
                return;
            } else if ($wsh == 1) {
                $handler = new WaInteractionHandler($campid);
                $handler->updatePayloadClick($companyId);

                return;
            } else if ($tprm == 1) {
                $handler = new TprmInteractionHandler($campid);
                $handler->updatePayloadClick($companyId);
            } else {
                $handler = new EmailInteractionHandler($campid);
                $handler->updatePayloadClick($companyId);
            }
        }
    }
}
