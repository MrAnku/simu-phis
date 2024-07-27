<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\Company;
use App\Models\UsersGroup;
use Illuminate\Http\Request;
use App\Models\WhatsappCampaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class WhatsappCampaignController extends Controller
{
    public function index()
    {
        $company_id = auth()->user()->company_id;
        $tokenAndUrl = $this->getTokenAndUrl($company_id);
        if ($tokenAndUrl !== null) {
            $all_users = UsersGroup::where('company_id', $company_id)->get();
            $templates = $this->getTemplates()['templates'];
            $campaigns = WhatsappCampaign::where('company_id', $company_id)->get();
            return view('whatsapp-campaign', compact('all_users', 'templates', 'campaigns'));
        }else{
            return view('whatsapp-unavailable');
        }
    }

    public function getTemplates()
    {
        $company_id = auth()->user()->company_id;

        $tokenAndUrl = $this->getTokenAndUrl($company_id);
        if ($tokenAndUrl !== null) {
            $response = Http::withOptions(['verify' => false])->get($tokenAndUrl->url . '/getTemplates', [
                'token' => $tokenAndUrl->token
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        }
    }

    private function getTokenAndUrl($company_id)
    {
        $company = Company::where('company_id', $company_id)->first();
        $tokenUrl = DB::table('partner_whatsapp_api')->where('partner_id', $company->partner_id)->first();

        if ($tokenUrl) {
            return $tokenUrl;
        } else {
            return null;
        }
    }

    public function submitCampaign(Request $request)
    {

        $company_id = auth()->user()->company_id;

        $new_campaign = new WhatsappCampaign();

        $new_campaign->camp_id = generateRandom(6);
        $camp_id = $new_campaign->camp_id;
        $new_campaign->camp_name = $request->camp_name;
        $new_campaign->template_name = $request->template_name;
        $new_campaign->user_group = $request->user_group;
        $new_campaign->user_group_name = $this->userGroupName($request->user_group);
        $new_campaign->company_id = $company_id;
        $new_campaign->created_at = now();
        $new_campaign->save();

        $this->createCampaignIndividual($camp_id, $request);

        return response()->json(['status' => 1, 'msg' => 'Campaign created successfully!']);
    }

    public function createCampaignIndividual($camp_id, $campaignData)
    {

        $users = Users::where('group_id', $campaignData->user_group)->get();
        $company_id = Auth::user()->company_id;

        foreach ($users as $user) {
            DB::table('whatsapp_camp_users')->insert([
                'camp_id' => $camp_id,
                'camp_name' => $campaignData->camp_name,
                'user_group' => $campaignData->user_group,
                'user_name' => $user->user_name,
                'user_whatsapp' => $user->whatsapp,
                'template_name' => $campaignData->template_name,
                'template_language' => $campaignData->template_language,
                'components' => json_encode($campaignData->components),
                'status' => 'pending',
                'created_at' => now(),
                'company_id' => $company_id

            ]);
        }
    }

    public function deleteCampaign(Request $request)
    {

        $request->validate([
            'campid' => 'required'
        ]);

        $campaign = WhatsappCampaign::where('camp_id', $request->campid)->first();

        if ($campaign) {

            $campaign->delete();
            DB::table('whatsapp_camp_users')->where('camp_id', $request->campid)->delete();
            return response()->json(['status' => 1, 'msg' => 'Campaign deleted successfully']);
        } else {
            return response()->json(['status' => 0, 'msg' => 'Something went wrong!']);
        }
    }

    private function userGroupName($groupid)
    {
        $userGroup = UsersGroup::where('group_id', $groupid)->first();
        if ($userGroup) {
            return $userGroup->group_name;
        } else {
            return null;
        }
    }

    public function fetchCampaign(Request $request)
    {
        $company_id = auth()->user()->company_id;

        $campaign = DB::table('whatsapp_camp_users')->where('camp_id', $request->campid)->where('company_id', $company_id)->get();

        return response()->json($campaign);
    }
}
