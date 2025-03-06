<?php

namespace App\Http\Controllers;

use App\Models\Users;
use Aws\Api\Validator;
use App\Models\QshTemplate;
use Illuminate\Support\Str;
use App\Models\QuishingCamp;
use Illuminate\Http\Request;
use App\Models\TrainingModule;
use App\Models\QuishingLiveCamp;
use Illuminate\Support\Facades\Auth;

class QuishingController extends Controller
{
    public function index()
    {
        $company_id = Auth::user()->company_id;
        $quishingEmails = QshTemplate::where('company_id', $company_id)
            ->orWhere('company_id', 'default')
            ->get();

        $trainingModules = TrainingModule::where(function ($query) use ($company_id) {
            $query->where('company_id', $company_id)
                ->orWhere('company_id', 'default');
        })->where('training_type', 'static_training')
            ->limit(10)
            ->get();

        $campaigns = QuishingCamp::with('userGroupData')->where('company_id', $company_id)->get();

        return view('quishing', compact('quishingEmails', 'trainingModules', 'campaigns'));
    }

    public function showMoreTemps(Request $request)
    {
        $page = $request->input('page', 1);
        $companyId = Auth::user()->company_id;

        $phishingEmails = QshTemplate::where('company_id', $companyId)
            ->orWhere('company_id', 'default')
            ->skip(($page - 1) * 10)
            ->take(10)
            ->get();

        return response()->json(['status' => 1, 'data' => $phishingEmails]);
    }

    public function searchTemplate(Request $request)
    {
        $searchTerm = $request->input('search');
        $companyId = Auth::user()->company_id;

        $phishingEmails = QshTemplate::where(function ($query) use ($companyId) {
            $query->where('company_id', $companyId)
                ->orWhere('company_id', 'default');
        })->where(function ($query) use ($searchTerm) {
            $query->where('name', 'LIKE', "%{$searchTerm}%");
        })->get();

        return response()->json(['status' => 1, 'data' => $phishingEmails]);
    }
    public function createCampaign(Request $request)
    {
        //xss attack
        $campData = $request->except(['quishing_materials', 'training_modules']);
        foreach ($campData as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return response()->json(['status' => 0, 'msg' => 'Invalid input detected.']);
            }
        }
        //xss attack

        $users = Users::where('group_id', $request->employee_group)
            ->where('company_id', Auth::user()->company_id)
            ->get();

        if (!$users) {
            return response()->json(['status' => 0, 'msg' => 'No employees found in selected group.']);
        }
        $campaign_id = Str::random(6);
        QuishingCamp::create([
            'campaign_id' => $campaign_id,
            'campaign_name' => $request->campaign_name,
            'campaign_type' => $request->campaign_type,
            'users_group' => $request->employee_group,

            'training_module' => $request->campaign_type == 'quishing' ? null : json_encode($request->training_modules),

            'training_assignment' => $request->campaign_type == 'quishing' ? null : $request->training_assignment,

            'days_until_due' => $request->campaign_type == 'quishing' ? null : $request->days_until_due,
            'training_lang' => $request->campaign_type == 'quishing' ? null : $request->training_language,
            'training_type' => $request->campaign_type == 'quishing' ? null : $request->training_type,
            'quishing_material' => !empty($request->quishing_materials) ? json_encode($request->quishing_materials) : null,
            'quishing_lang' => $request->quishing_language ?? null,
            'status' => 'running',
            'company_id' => Auth::user()->company_id,
        ]);

        foreach ($users as $user) {
            QuishingLiveCamp::create([
                'campaign_id' => $campaign_id,
                'campaign_name' => $request->campaign_name,
                'user_id' => $user->id,
                'user_name' => $user->user_name,
                'user_email' => $user->user_email,
                'training_module' => $request->campaign_type == 'quishing' ? null : $request->training_modules[array_rand($request->training_modules)],

                'days_until_due' => $request->campaign_type == 'quishing' ? null : $request->days_until_due,
                'training_lang' => $request->campaign_type == 'quishing' ? null : $request->training_language,
                'training_type' => $request->campaign_type == 'quishing' ? null : $request->training_type,
                'quishing_material' => !empty($request->quishing_materials) ? $request->quishing_materials[array_rand($request->quishing_materials)] : null,
                'quishing_lang' => $request->quishing_language ?? null,
                'company_id' => Auth::user()->company_id
            ]);
        }

        return response()->json(['status' => 1, 'msg' => 'Campaign created successfully.']);
    }

    public function deleteCampaign(Request $request)
    {
        $campaign_id = base64_decode($request->campid);
        $campaign = QuishingCamp::where('campaign_id', $campaign_id)->first();
        if (!$campaign) {
            return response()->json(['status' => 0, 'msg' => 'Campaign not found.']);
        }

        $campaign->delete();
        QuishingLiveCamp::where('campaign_id', $campaign_id)->delete();

        return response()->json(['status' => 1, 'msg' => 'Campaign deleted successfully.']);
    }
}
