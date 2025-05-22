<?php

namespace App\Http\Controllers\Api;

use App\Models\Users;
use App\Models\UsersGroup;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\TrainingModule;
use App\Models\PhishingWebsite;
use App\Models\SmishingCampaign;
use App\Models\SmishingTemplate;
use App\Http\Controllers\Controller;
use App\Models\SmishingLiveCampaign;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ApiSmishingController extends Controller
{
    public function index()
    {
        try {
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

            return response()->json([
                'success' => true,
                'data' => [
                    'campaigns' => $campaigns,
                    'templates' => $templates,
                    'totalSentCampaigns' => $totalSentCampaigns,
                    'totalCompromised' => $totalCompromised,
                    'phishingWebsites' => $phishingWebsites,
                    'trainingModules' => $trainingModules
                ],
                'message' => __('Smishing data retrieved successfully')
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error:') . $e->getMessage()], 500);
        }
    }

    public function createCampaign(Request $request)
    {
        try {
            $request->validate([
                'campaign_name' => 'required|string|max:255',
                'campaign_type' => 'required|string',
                'days_until_due' => 'required|integer|min:1',
                'employee_group_id' => 'required|string|max:255',
                'phishing_website_id' => 'required|exists:phishing_websites,id',
                'smishing_language' => 'required|string|size:2',
                'smishing_materials_id' => 'required|array|min:1',
                'smishing_materials_id.*' => 'exists:smishing_templates,id',
            ]);

            $campaign = new SmishingCampaign();
            $campaign->campaign_id = Str::random(6);
            $campaign->campaign_name = $request->campaign_name;
            $campaign->campaign_type = $request->campaign_type;
            $campaign->users_group = $request->employee_group_id;
            $campaign->template_id = json_encode($request->smishing_materials_id);
            $campaign->template_lang = $request->smishing_language;
            $campaign->website_id = $request->phishing_website_id;

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
            $live = $this->makeCampaignLive($campaign->campaign_id, $request->employee_group_id);

            if ($live['status'] == 0) {
                return response()->json([
                    'success' => false,
                    'message' => $live['msg'],
                ]);
            }

            log_action("Smishing Campaign Created : {$request->campaign_name}");

            return response()->json([
                'success' => true,
                'data' => [
                    'campaign_id' => $campaign->campaign_id,
                ],
                'message' => 'Campaign created successfully',
            ], 201);
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

    public function fetchMoreTemps(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $companyId = Auth::user()->company_id;

            $templates = SmishingTemplate::where('company_id', $companyId)
                ->orWhere('company_id', 'default')
                ->skip(($page - 1) * 10)
                ->take(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $templates,
                'message' => 'Templates Fetched successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching templates: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function fetchMoreWebsites(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $companyId = Auth::user()->company_id;

            $websites = PhishingWebsite::where('company_id', $companyId)
                ->orWhere('company_id', 'default')
                ->skip(($page - 1) * 10)
                ->take(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $websites,
                'message' => 'Websites Fetched successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching websites: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function searchTemplate(Request $request)
    {
        try {
            if (!$request->query('search')) {
                return response()->json(['success' => false, 'message' => 'Search field is required'], 404);
            }
            $searchTerm = $request->query('search');
            $companyId = Auth::user()->company_id;

            $templates = SmishingTemplate::where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhere('company_id', 'default');
            })->where(function ($query) use ($searchTerm) {
                $query->where('name', 'LIKE', "%{$searchTerm}%");
            })->get();

            if ($templates->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Template not found'], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $templates,
                'message' => 'Templates Searched successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while searhing templates: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function searchWebsite(Request $request)
    {
        try {
            if (!$request->query('search')) {
                return response()->json(['success' => false, 'message' => 'Search field is required'], 404);
            }
            $searchTerm = $request->query('search');
            $companyId = Auth::user()->company_id;

            $websites = PhishingWebsite::where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhere('company_id', 'default');
            })->where(function ($query) use ($searchTerm) {
                $query->where('name', 'LIKE', "%{$searchTerm}%");
            })->get();

            if ($websites->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Website not found'], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $websites,
                'message' => 'Website Searched successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while searhing websites: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deleteCampaign(Request $request)
    {
        try {
            $campaign_id = $request->route('campId');

            if (!$campaign_id) {
                return response()->json(['success' => false, 'message' => __('Campaign ID is required.')], 404);
            }
            $campaign = SmishingCampaign::where('campaign_id', $campaign_id)->first();
            if (!$campaign) {
                return response()->json(['success' => false, 'message' => __('Campaign not found.')], 404);
            }
            $campaign->delete();
            SmishingLiveCampaign::where('campaign_id', $campaign_id)->delete();

            log_action("Smishing Campaign deleted : {$campaign->campaign_name}");
            return response()->json(['success' => true, 'message' => __('Campaign deleted successfully')], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while Campaign deletion: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function fetchCampDetail(Request $request)
    {
        try {
            $campaign_id = $request->route('campId');

            if (!$campaign_id) {
                return response()->json(['success' => false, 'message' => __('Campaign ID is required.')], 404);
            }

            $campaign = SmishingCampaign::with('campLive')->where('campaign_id', $campaign_id)->where('company_id', Auth::user()->company_id)->first();
            if (!$campaign) {
                return response()->json(['success' => false, 'message' => 'Campaign not found.'], 404);
            }
            $trainingAssigned = TrainingAssignedUser::with('trainingData')->where('campaign_id', $campaign_id)
                ->where('company_id', Auth::user()->company_id)
                ->get();
            $campaign->trainingAssigned = $trainingAssigned;
            return response()->json(['success' => true, 'data' => $campaign, 'message' => 'Campaign details fetched successfully'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching campaign details: ' . $e->getMessage(),
            ], 500);
        }
    }
}
