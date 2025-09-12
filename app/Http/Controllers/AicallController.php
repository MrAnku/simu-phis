<?php

namespace App\Http\Controllers;

use App\Models\AiAgentRequest;
use App\Models\AiCallAgent;
use App\Models\Users;
use App\Models\Company;
use App\Models\UsersGroup;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\AiCallCampaign;
use App\Models\AiCallCampLive;
use App\Models\TrainingModule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class AicallController extends Controller
{
    public function index()
    {

        $companyId = Auth::user()->company_id;

        $company = DB::table('ai_call_reqs')->where('company_id', $companyId)->first();

        $companyId = Auth::user()->company_id;

        $empGroups = UsersGroup::where('company_id', $companyId)->where('users', '!=', null)->get();
        $trainings = TrainingModule::where('company_id', 'default')->orWhere('company_id', $companyId)->get();
        $campaigns = AiCallCampaign::with('trainingName')->where('company_id', $companyId)->orderBy('id', 'desc')->paginate(10);
        $agents = AiCallAgent::where('company_id', $companyId)->orWhere('company_id', 'default')->get();
        $phone_numbers = $this->getPhoneNumbers();

        // return $campaigns;
        return view("aicall", compact('company', 'agents', 'phone_numbers', 'empGroups', 'campaigns', 'trainings'));
    }

    public function agentRequest(Request $request)
    {

        //xss check start
        $input = $request->only('agent_name', 'agent_prompt', 'language');
        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return redirect()->back()->with('error', __('Invalid input detected.'));
            }
        }
        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);
        //xss check end

        $request->validate([
            'agent_name' => 'required|string',
            'agent_prompt' => 'required|string',
            'language' => 'required|string',
            'deepfake_audio' => 'nullable|file|mimes:mp3,wav,aac|max:2048',
        ]);

        $companyId = Auth::user()->company_id;

        if ($request->hasFile('deepfake_audio')) {
            $file = $request->file('deepfake_audio');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('deepfake_audio', $filename, 'public');
        } else {
            $filename = null;
        }
        AiAgentRequest::create([
            'company_id' => $companyId,
            'agent_name' => $request->agent_name,
            'language' => $request->language,
            'audio_file' => $filename,
            'prompt' => $request->agent_prompt,
            'status' => 0
        ]);

        return redirect()->back()->with('success', __('New agent request submitted successfully.'));
    }

    public function submitReq(Request $request)
    {

        $companyId = Auth::user()->company_id;
        $partnerId = Auth::user()->partner_id;


        DB::table('ai_call_reqs')->insert([
            "company_id" => $companyId,
            "partner_id" => $partnerId,
            "status" => 0,
            "created_at" => now()
        ]);

        log_action('Request submitted for AI vishing simulation');
        return redirect()->back()->with('success', __('Your request has been submitted successfully.'));
    }

    public function getAgents()
    {

        // Make the HTTP request
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('RETELL_API_KEY'),
        ])->withOptions(['verify' => false])->get('https://api.retellai.com/list-agents');

        // Check for a successful response
        if ($response->successful()) {
            // Return the response data
            return $response->json();
        } else {
            // Handle the error, e.g., log the error or throw an exception
            return [
                'error' => __('Unable to fetch agents'),
                'status' => $response->status(),
                'message' => $response->body()
            ];
        }
    }

    private function getPhoneNumbers()
    {
        // Make the HTTP request
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('RETELL_API_KEY'),
        ])->withOptions(['verify' => false])->get('https://api.retellai.com/list-phone-numbers');

        // Check for a successful response
        if ($response->successful()) {
            // Return the response data
            return $response->json();
        } else {
            // Handle the error, e.g., log the error or throw an exception
            return [
                'error' => __('Unable to fetch agents'),
                'status' => $response->status(),
                'message' => $response->body()
            ];
        }
    }

    public function createCampaign(Request $request)
    {

        //xss check start

        $input = $request->all();
        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return redirect()->back()->with('error', __('Invalid input detected.'));
            }
        }
        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);

        //xss check end


        $request->validate(
            [
                'camp_name' => 'required|string|min:5|max:50',
                'users_group' => 'required|string',
                'training_module' => 'nullable|integer',
                'training_type' => 'string',
                'emp_group_name' => 'required|string',
                'ai_agent_name' => 'required|string',
                'ai_agent' => 'required|string',
                'ai_phone' => 'required|string'
            ],
            [
                "camp_name.min" => __('Campaign Name must be at least 5 Characters')
            ]
        );

        // return "request validated";

        $companyId = Auth::user()->company_id;
        $campId = Str::random(6);

        //checking if all users have valid mobile number
        $isvalid = $this->checkValidMobile($request->users_group);

        if (!$isvalid) {
            return redirect()->back()->with('error', __('Please check if selected employee group has valid phone number'));
        }

        AiCallCampaign::create([
            'campaign_id' => $campId,
            'campaign_name' => $request->camp_name,
            'users_group' => $request->users_group,
            'users_grp_name' => $request->users_grp_name,
            'training_module' => $request->training_module ?? null,
            'training_lang' => $request->training_module ? $request->training_lang : null,
            'training_type' => $request->training_type ? $request->training_type : 'static_training',
            'ai_agent' => $request->ai_agent,
            'ai_agent_name' => $request->ai_agent_name,
            'phone_no' => $request->ai_phone,
            'status' => 'pending',
            'created_at' => now(),
            'company_id' => $companyId
        ]);

        $this->makeCampaignLive($campId);

        log_action('Campaign for AI Vishing simulation created');

        return redirect()->back()->with('success', __('Campaign created successfully.'));
    }

    public function viewCampaign($id)
    {

        $id = base64_decode($id);
        $campaign = AiCallCampaign::with(['individualCamps', 'trainingName'])->find($id);
        if ($campaign) {
            return response()->json($campaign);
        }
    }

    private function checkValidMobile($groupid)
    {
        // Retrieve the JSON-encoded users column and decode it
        $userIdsJson = UsersGroup::where('group_id', $groupid)->value('users');

        // Decode the JSON into an array
        $userIds = json_decode($userIdsJson, true);

        // If decoding fails or no user IDs exist, return false
        if (empty($userIds) || !is_array($userIds)) {
            return false;
        }

        // Fetch users based on the retrieved IDs
        $users = Users::whereIn('id', $userIds)->get();

        // If no users exist in Users table, return false
        if ($users->isEmpty()) {
            return false;
        }

        // Check if any user has whatsapp = 0 or null
        foreach ($users as $user) {
            if (empty($user->whatsapp)) { // Checks both 0 and null
                return false;
            }
        }

        return true; // All users have valid WhatsApp numbers
    }

    private function makeCampaignLive($campaignid)
    {

        $companyId = Auth::user()->company_id;

        $campaign = AiCallCampaign::where('campaign_id', $campaignid)->first();

        if ($campaign) {

            $userIdsJson = UsersGroup::where('group_id', $campaign->users_group)->value('users');
            $userIds = json_decode($userIdsJson, true);
            $users = Users::whereIn('id', $userIds)->get();
            if ($users) {
                foreach ($users as $user) {

                    AiCallCampLive::create([
                        'campaign_id' => $campaign->campaign_id,
                        'campaign_name' => $campaign->campaign_name,
                        'user_id' => $user->id,
                        'user_name' => $user->user_name,
                        'user_email' => $user->user_email,
                        'training_module' => $campaign->training_module ?? null,
                        'training_lang' => $campaign->training_lang ?? null,
                        'training_type' => $campaign->training_type ?? null,
                        'from_mobile' => $campaign->phone_no,
                        'to_mobile' => "+" . $user->whatsapp,
                        'agent_id' => $campaign->ai_agent,
                        'status' => 'pending',
                        'created_at' => now(),
                        'company_id' => $campaign->company_id,
                    ]);
                }
            }
        }
    }

    public function deleteCampaign(Request $request)
    {

        $campaignid = base64_decode($request->camp);
        $camp = AiCallCampaign::find($campaignid);
        if ($camp) {
            $camp->delete();
            AiCallCampLive::where('campaign_id', $camp->campaign_id)->delete();

            log_action('AI Vishing campaign deleted');
            return redirect()->back()->with('success', __('Campaign Deleted'));
        }
    }

    public function fetchCallReport($callid)
    {
        //checking if call report is available or not

        $localReport = AiCallCampLive::where('call_id', $callid)->first();
        if ($localReport) {
            if ($localReport->call_report == null) {

                // Make the HTTP request
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . env('RETELL_API_KEY'),
                ])->withOptions(['verify' => false])->get('https://api.retellai.com/v2/get-call/' . $callid);

                if ($response->successful()) {
                    // Return the response data
                    $res = $response->json();
                    log_action("AI Vishing Call report fetched for call id {$callid}");
                    if(isset($res['transcript_object']) && count($res['transcript_object']) > 0) {
                        $localReport->call_report = $res;
                        $localReport->save();
                    }
                    
                    return $res;
                } else {
                    // Handle the error, e.g., log the error or throw an exception
                    log_action("Error while fetching AI Vishing Call report for call id {$callid}");

                    return [
                        'error' => __('Unable to fetch call detail'),
                        'status' => $response->status(),
                        'message' => $response->body()
                    ];
                }
            } else {
                $data = json_decode($localReport->call_report);
                return $data;
            }
        }
    }

    public function logCallDetail(Request $request)
    {

        try {
            DB::table('ai_call_all_logs')->insert([
                'log_json' => json_encode($request->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now()
            ]);
        } catch (\Exception $e) {
            // Log the error or handle it as needed
            Log::error('Failed to insert log data: ' . $e->getMessage());
        }

        log_action("Post call received after call hangup from AI Vishing Provider", 'retell_ai', 'retell_ai');

        return response()->json(['status' => 1, 'msg' => __('msg logged')]);
    }

    public function translateCallDetail(Request $request){
        try {
            $prompt = "Translate the following HTML content into " . langName($request->lang) . " language. Ensure the structure and tags of the HTML remain intact while translating the text content:\n\n" . $request->html;

            $response = Http::withOptions(['verify' => false])->withHeaders([
                'Authorization' => 'Bearer ' . env("OPENAI_API_KEY"),
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert HTML translator. Always ensure the HTML structure remains intact while translating.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 1500,
                'temperature' => 0.7,
            ]);

            if ($response->failed()) {

                log_action("Failed to translate Ai call detail in {$request->lang} language");

                return response()->json([
                    'status' => 0,
                    'msg' => $response->body(),
                ]);
            }

            $translatedJson = $response['choices'][0]['message']['content'];

            log_action("AI Call detail translated in {$request->lang} language");

            return response()->json([
                'status' => 1,
                'html' => $translatedJson,
            ]);
        } catch (\Exception $e) {

            log_action("Failed to translate JSON data", 'learner', 'learner');

            return response()->json([
                'status' => 0,
                'msg' => $e->getMessage(),
            ]);
        }
    }
}
