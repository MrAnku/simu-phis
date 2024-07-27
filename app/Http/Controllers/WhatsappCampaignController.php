<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\UsersGroup;
use App\Models\WhatsappCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsappCampaignController extends Controller
{
    public function index()
    {
        $company_id = auth()->user()->company_id;
        $all_users = UsersGroup::where('company_id', $company_id)->get();
        $templates = $this->getTemplates()['templates'];
        $campaigns = WhatsappCampaign::where('company_id', $company_id)->get();
        return view('whatsapp-campaign', compact('all_users', 'templates', 'campaigns'));
    }

    public function getTemplates()
    {
        $response = Http::withOptions(['verify' => false])->get(env('WHATSAPP_CRM') . '/getTemplates', [
            'token' => env('WHATSAPP_CRM_TOKEN')
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function submitCampaign(Request $request)
    {

        $company_id = auth()->user()->company_id;

        $new_campaign = new WhatsappCampaign();

        $new_campaign->camp_id = generateRandom(6);
        $new_campaign->camp_name = $request->camp_name;
        $new_campaign->template_name = $request->template_name;
        $new_campaign->user_group = $request->user_group;
        $new_campaign->company_id = $company_id;
        $new_campaign->created_at = now();
        $new_campaign->save();

        $response = $this->sendWhatsAppMsg($request);

        return $response;
    }

    public function sendWhatsAppMsg($campaignData)
    {

        $users = Users::where('group_id', $campaignData->user_group)->get();

        foreach ($users as $user) {
            $url = env('WHATSAPP_CRM') . '/sendtemplatemessage';  

            $payload = [
                "token" => env('WHATSAPP_CRM_TOKEN'),
                "phone" => $user->whatsapp,
                "template_name" => $campaignData->template_name,
                "template_language" => $campaignData->template_language,
                "components" => $campaignData->components
            ];

            // Make the POST request
            $response = Http::withOptions(['verify' => false])->post($url, $payload);

            // Handle the response
            if ($response->successful()) {
                // Success handling
                return response()->json($response->json());
            } else {
                // Error handling
                return response()->json(['error' => 'Request failed'], $response->status());
            }
        }
    }

    public function deleteCampaign(Request $request){

        $request->validate([
            'campid' => 'required'
        ]);

        $campaign = WhatsappCampaign::where('camp_id', $request->campid)->first();

        if($campaign){
            
            $campaign->delete();    
            return response()->json(['status' => 1, 'msg' => 'Campaign deleted successfully']);
        }else{
            return response()->json(['status' => 0, 'msg' => 'Something went wrong!']);
        }


    }
}
