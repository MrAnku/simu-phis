<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\UsersGroup;
use App\Models\WhatsappCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsappCampaignController extends Controller
{
    public function index(){
        $company_id = auth()->user()->company_id;
        $all_users = UsersGroup::where('company_id', $company_id)->get();
        $templates = $this->getTemplates()['templates'];
        return view('whatsapp-campaign', compact('all_users', 'templates'));
    }

    public function getTemplates(){
        $response = Http::withOptions(['verify' => false])->get(env('WHATSAPP_CRM') . '/getTemplates', [
            'token' => env('WHATSAPP_CRM_TOKEN')
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function submitCampaign(Request $request){

        $company_id = auth()->user()->company_id;

        $new_campaign = new WhatsappCampaign();

        $new_campaign->camp_id = generateRandom(6);
        $new_campaign->camp_name = $request->camp_name;
        $new_campaign->template_name = $request->template_name;
        $new_campaign->user_group = $request->user_group;
        $new_campaign->company_id = $company_id;
        $new_campaign->created_at = now();
        $new_campaign->save();

        // return $request;
    }
}
