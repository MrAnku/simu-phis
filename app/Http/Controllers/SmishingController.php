<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\UsersGroup;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\TrainingModule;
use App\Models\PhishingWebsite;
use App\Models\SmishingCampaign;
use App\Models\SmishingTemplate;
use App\Models\SmishingLiveCampaign;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\Auth;
use Dotenv\Exception\ValidationException;

class SmishingController extends Controller
{
    public function index()
    {
        $campaigns = SmishingCampaign::where('company_id', Auth::user()->company_id)
            ->get();
        $templates = SmishingTemplate::where('company_id', Auth::user()->company_id)
            ->orWhere('company_id', 'default')
            ->take(10)
            ->get();
        $totalSentCampaigns = SmishingLiveCampaign::where('company_id', Auth::user()->company_id)
            ->where('sent', 1)
            ->count();
        $totalCompromised = SmishingLiveCampaign::where('company_id', Auth::user()->company_id)
            ->where('compromised', 1)
            ->count();

        $trainingModules = TrainingModule::where(function ($query) {
            $query->where('company_id', Auth::user()->company_id)
                ->orWhere('company_id', 'default');
        })->where('training_type', 'static_training')
            ->take(10)
            ->get();
        $phishingWebsites = PhishingWebsite::where('company_id', Auth::user()->company_id)
            ->orWhere('company_id', 'default')
            ->take(10)
            ->get();

        return view('smishing', compact(
            'campaigns',
            'templates',
            'totalSentCampaigns',
            'totalCompromised',
            'phishingWebsites',
            'trainingModules'
        ));
    }

    public function createCampaign(Request $request)
    {
        try {
            $request->validate([
                'campaign_name' => 'required|string|max:255',
                'campaign_type' => 'required|string',
                'days_until_due' => 'required|integer|min:1',
                'employee_group' => 'required|string|max:255',
                'phishing_website' => 'required|exists:phishing_websites,id',
                'smishing_language' => 'required|string|size:2',
                'smishing_materials' => 'required|array|min:1',
                'smishing_materials.*' => 'exists:smishing_templates,id',
            ]);

            $campaign = new SmishingCampaign();
            $campaign->campaign_id = Str::random(6);
            $campaign->campaign_name = $request->campaign_name;
            $campaign->campaign_type = $request->campaign_type;
            $campaign->users_group = $request->employee_group;
            $campaign->template_id = json_encode($request->smishing_materials);
            $campaign->template_lang = $request->smishing_language;
            $campaign->website_id = $request->phishing_website;

            if ($request->campaign_type == 'smishing') {
                $campaign->training_module = null;
                $campaign->training_assignment = null;
                $campaign->days_until_due = null;
                $campaign->training_lang = null;
                $campaign->training_type = null;
            } else {
                $campaign->training_module = json_encode($request->training_modules ?? null);
                $campaign->training_assignment = $request->training_assignment ?? null;
                $campaign->days_until_due = $request->days_until_due;
                $campaign->training_lang = $request->training_language ?? null;
                $campaign->training_type = $request->training_type ?? null;
            }
            $campaign->launch_time = now();
            $campaign->status = 'running';
            $campaign->company_id = Auth::user()->company_id;
            $campaign->save();

            //make campaign live
            $live = $this->makeCampaignLive($campaign->campaign_id, $request->employee_group);

            if ($live['status'] == 0) {
                return response()->json([
                    'success' => false,
                    'message' => $live['msg'],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Campaign created successfully',
                'campaign_id' => $campaign->campaign_id,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the campaign: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function makeCampaignLive($campaignId, $employeeGroup)
    {
        $userIdsJson = UsersGroup::where('group_id', $employeeGroup)->value('users');
        $userIds = json_decode($userIdsJson, true);
        $users = Users::whereIn('id', $userIds)->get();

        if ($users->isEmpty()) {
            return [
                'status' => 0,
                'msg' => 'No employees found in selected group.',
            ];
        }

        $campaign = SmishingCampaign::where('campaign_id', $campaignId)->first();

        $templateArray = json_decode($campaign->template_id, true);
        $trainingArray = json_decode($campaign->training_module, true);

        foreach ($users as $user) {
            SmishingLiveCampaign::create([
                'campaign_id' => $campaignId,
                'campaign_name' => $campaign->campaign_name,
                'campaign_type' => $campaign->campaign_type,
                'user_id' => $user->id,
                'user_name' => $user->user_name,
                'user_phone' => $user->whatsapp,
                'template_id' => is_array($templateArray) ? $templateArray[array_rand($templateArray)] : null,
                'template_lang' => $campaign->template_lang,
                'website_id' => $campaign->website_id,
                'training_module' => ($campaign->campaign_type === 'smishing')
                    ? null
                    : (is_array($trainingArray) ? $trainingArray[array_rand($trainingArray)] : null),
                'days_until_due' => $campaign->days_until_due,
                'training_lang' => $campaign->training_lang,
                'training_type' => $campaign->training_type,
                'company_id' => Auth::user()->company_id,
            ]);
        }

        return [
            'status' => 1,
            'msg' => 'Campaign created successfully.',
        ];
    }

    public function showMoreTemps(Request $request)
    {
        $page = $request->input('page', 1);
        $companyId = Auth::user()->company_id;

        $templates = SmishingTemplate::where('company_id', $companyId)
            ->orWhere('company_id', 'default')
            ->skip(($page - 1) * 10)
            ->take(10)
            ->get();

        return response()->json(['status' => 1, 'data' => $templates]);
    }

    public function showMoreWebsites(Request $request)
    {
        $page = $request->input('page', 1);
        $companyId = Auth::user()->company_id;

        $websites = PhishingWebsite::where('company_id', $companyId)
            ->orWhere('company_id', 'default')
            ->skip(($page - 1) * 10)
            ->take(10)
            ->get();

        return response()->json(['status' => 1, 'data' => $websites]);
    }

    public function searchTemplate(Request $request)
    {
        $searchTerm = $request->input('search');
        $companyId = Auth::user()->company_id;

        $templates = SmishingTemplate::where(function ($query) use ($companyId) {
            $query->where('company_id', $companyId)
                ->orWhere('company_id', 'default');
        })->where(function ($query) use ($searchTerm) {
            $query->where('name', 'LIKE', "%{$searchTerm}%");
        })->get();

        return response()->json(['status' => 1, 'data' => $templates]);
    }

    public function searchWebsite(Request $request)
    {
        $searchTerm = $request->input('search');
        $companyId = Auth::user()->company_id;

        $websites = PhishingWebsite::where(function ($query) use ($companyId) {
            $query->where('company_id', $companyId)
                ->orWhere('company_id', 'default');
        })->where(function ($query) use ($searchTerm) {
            $query->where('name', 'LIKE', "%{$searchTerm}%");
        })->get();

        return response()->json(['status' => 1, 'data' => $websites]);
    }

    public function deleteCampaign(Request $request)
    {
        $campaign_id = base64_decode($request->campid);
        $campaign = SmishingCampaign::where('campaign_id', $campaign_id)->first();
        if (!$campaign) {
            return response()->json(['status' => 0, 'msg' => 'Campaign not found.']);
        }

        $campaign->delete();
        SmishingLiveCampaign::where('campaign_id', $campaign_id)->delete();

        return response()->json(['status' => 1, 'msg' => __('Campaign deleted successfully')]);
    }

    public function fetchCampDetail(Request $request)
    {
        $campaign_id = $request->campid;
        $campaign = SmishingCampaign::with('campLive')->where('campaign_id', $campaign_id)->where('company_id', Auth::user()->company_id)->first();
        if (!$campaign) {
            return response()->json(['status' => 0, 'msg' => 'Campaign not found.']);
        }
        $trainingAssigned = TrainingAssignedUser::with('trainingData')->where('campaign_id', $campaign_id)
            ->where('company_id', Auth::user()->company_id)
            ->get();
        $campaign->trainingAssigned = $trainingAssigned;
        return response()->json(['status' => 1, 'data' => $campaign]);
    }
}
