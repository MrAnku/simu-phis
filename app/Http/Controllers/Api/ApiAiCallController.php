<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AiAgentRequest;
use App\Models\AiCallAgent;
use App\Models\AiCallCampaign;
use App\Models\AiCallCampLive;
use App\Models\TrainingAssignedUser;
use App\Models\TrainingModule;
use App\Models\Users;
use App\Models\UsersGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApiAiCallController extends Controller
{
    public function index()
    {
        try {
            $companyId = Auth::user()->company_id;

            $company = DB::table('ai_call_reqs')->where('company_id', $companyId)->first();

            $empGroups = UsersGroup::where('company_id', $companyId)->where('users', '!=', null)->get();
            $trainings = TrainingModule::where('company_id', 'default')->orWhere('company_id', $companyId)->get();
            $campaigns = AiCallCampaign::with('trainingName')->where('company_id', $companyId)->orderBy('id', 'desc')->paginate(10);
            $agents = AiCallAgent::where('company_id', $companyId)->orWhere('company_id', 'default')->get();
            $phone_numbers = $this->getPhoneNumbers();

            return response()->json([
                'success' => true,
                'data' => [
                    'company' => $company,
                    'agents' => $agents,
                    'phone_numbers' => $phone_numbers,
                    'empGroups' => $empGroups,
                    'campaigns' => $campaigns,
                    'trainings' => $trainings
                ],
                'message' => __('AI Call Campaigns fetched successfully')
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
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

    public function submitReq()
    {
        try {
            $companyId = Auth::user()->company_id;
            $partnerId = Auth::user()->partner_id;


            DB::table('ai_call_reqs')->insert([
                "company_id" => $companyId,
                "partner_id" => $partnerId,
                "status" => 0,
                "created_at" => now()
            ]);

            log_action('Request submitted for AI vishing simulation');
            return response()->json(['success' => true, 'message' => __('Your request has been submitted successfully.')], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function createCampaign(Request $request)
    {
        try {
            //xss check start

            $input = $request->all();
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json(['success' => false, 'message' => __('Invalid input detected.')], 422);
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
                    'emp_group' => 'required|string',
                    'training_module' => 'nullable|integer',
                    'training_lang' => 'nullable|string',
                    'training_type' => 'nullable|string',
                    'emp_group_name' => 'required|string',
                    'ai_agent_name' => 'required|string',
                    'ai_agent' => 'required|string',
                    'ai_phone' => 'required|string'
                ],
                [
                    "camp_name.min" => __('Campaign Name must be at least 5 Characters')
                ]
            );

            if (!UsersGroup::where('group_id', $request->emp_group)->where('users', '!=', null)->exists()) {
                return response()->json(['success' => false, 'message' => __('Employee Group does not exist or No user found in group')], 422);
            }

            // return "request validated";

            $companyId = Auth::user()->company_id;
            $campId = Str::random(6);

            //checking if all users have valid mobile number
            $isvalid = $this->checkValidMobile($request->emp_group);

            if (!$isvalid) {
                return response()->json(['success' => false, 'message' => __('Please check if selected employee group has valid phone number')], 422);
            }

            AiCallCampaign::create([
                'campaign_id' => $campId,
                'campaign_name' => $request->camp_name,
                'emp_group' => $request->emp_group,
                'emp_grp_name' => $request->emp_group_name,
                'training' => $request->campaign_type == 'phishing' ? null : $request->training_module,
                'training_lang' => $request->campaign_type == 'phishing' ? null : $request->training_lang,
                'training_type' => $request->campaign_type == 'phishing' ? null : $request->training_type,
                'ai_agent' => $request->ai_agent,
                'ai_agent_name' => $request->ai_agent_name,
                'phone_no' => $request->ai_phone,
                'status' => 'pending',
                'company_id' => $companyId
            ]);

            $this->makeCampaignLive($campId);

            log_action('Campaign for AI Vishing simulation created');

            return response()->json(['success' => true, 'message' => __('Campaign created successfully.')], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
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

            $userIdsJson = UsersGroup::where('group_id', $campaign->emp_group)->value('users');
            $userIds = json_decode($userIdsJson, true);
            $users = Users::whereIn('id', $userIds)->get();
            if ($users) {
                foreach ($users as $user) {

                    AiCallCampLive::create([
                        'campaign_id' => $campaign->campaign_id,
                        'campaign_name' => $campaign->campaign_name,
                        'user_id' => $user->id,
                        'employee_name' => $user->user_name,
                        'employee_email' => $user->user_email,
                        'training' => $campaign->training ?? null,
                        'training_lang' => $campaign->training_lang ?? null,
                        'training_type' => $campaign->training_type ?? null,
                        'from_mobile' => $campaign->phone_no,
                        'to_mobile' => "+" . $user->whatsapp,
                        'agent_id' => $campaign->ai_agent,
                        'status' => 'pending',
                        'company_id' => $campaign->company_id,
                    ]);
                }
            }
        }
    }

    public function viewCampaign(Request $request)
    {
        try {
            $id = $request->route('campaign_id');

            if (!$id) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required')
                ], 422);
            }

            $campaign = AiCallCampaign::with(['individualCamps', 'trainingName'])
                ->where('campaign_id', $id)
                ->first();

            if ($campaign) {
                return response()->json([
                    'success' => true,
                    'data' => $campaign,
                    'message' => __('Campaign details fetched successfully')
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign not found')
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }


    public function deleteCampaign(Request $request)
    {
        try {
            if (!$request->route('campaign_id')) {
                return response()->json(['success' => false, 'message' => __('Campaign ID is required')], 422);
            }
            $campaignid = $request->route('campaign_id');

            if($request->deleteTrainingsAlso == 1){
                TrainingAssignedUser::where('campaign_id', $campaignid)->delete();
            }

            $camp = AiCallCampaign::where('campaign_id', $campaignid)->first();
            if ($camp) {
                $camp->delete();
                AiCallCampLive::where('campaign_id', $campaignid)->delete();

                log_action('AI Vishing campaign deleted');
                return response()->json(['success' => true, 'message' => __('Campaign Deleted')], 200);
            } else {
                return response()->json(['success' => false, 'message' => __('Campaign not found')], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function getAgents()
    {
        try {
            // Make the HTTP request
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('RETELL_API_KEY'),
            ])->withOptions(['verify' => false])->get('https://api.retellai.com/list-agents');

            // Check for a successful response
            if ($response->successful()) {
                // Return the response data
                return response()->json([
                    'success' => true,
                    'data' => $response->json(),
                    'message' => __('Agents fetched successfully')
                ], 200);
            } else {
                // Handle the error, e.g., log the error or throw an exception
                return response()->json([
                    'success' => false,
                    'message' => $response->body()
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function fetchCallReport(Request $request)
    {
        try {
            if (!$request->route('callId')) {
                return response()->json(['success' => false, 'message' => __('Call ID is required')], 422);
            }
            //checking if call report is available or not

            $localReport = AiCallCampLive::where('call_id', $request->route('callId'))->first();
            if (!$localReport) {
                return response()->json(['success' => false, 'message' => __('Call report not found')], 404);
            }
            if ($localReport) {
                if ($localReport->call_report == null) {

                    // Make the HTTP request
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . env('RETELL_API_KEY'),
                    ])->withOptions(['verify' => false])->get('https://api.retellai.com/v2/get-call/' . $request->route('callId'));

                    if ($response->successful()) {
                        // Return the response data
                        $res = $response->json();
                        log_action("AI Vishing Call report fetched for call id {$request->route('callId')}");
                        if (isset($res['transcript_object']) && count($res['transcript_object']) > 0) {
                            $localReport->call_report = $res;
                            $localReport->save();
                        }

                        return $res;
                    } else {
                        // Handle the error, e.g., log the error or throw an exception
                        log_action("Error while fetching AI Vishing Call report for call id {$request->route('callId')}");

                        return response()->json(['success' => false, 'message' => $response->body()], 422);
                    }
                } else {
                    $data = json_decode($localReport->call_report);
                    return $data;
                }
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function agentRequest(Request $request)
    {
        try {
            //xss check start
            $input = $request->only('agent_name', 'agent_prompt', 'language');
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json(['success' => false, 'message' => __('Invalid input detected.')], 422);
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
                'deepfake_audio' => 'nullable|file|mimes:mp3,wav,aac',
            ]);

            $companyId = Auth::user()->company_id;

            if ($request->hasFile('deepfake_audio')) {

                 $file = $request->file('deepfake_audio');

                // Generate a random name for the file
                $randomName = generateRandom(32);
                $extension = $file->getClientOriginalExtension();
                $newFilename = $randomName . '.' . $extension;

                $filePath = $request->file('deepfake_audio')->storeAs('/deepfake_audio', $newFilename, 's3');
                $filePath = '/' . $filePath;
            } else {
                $filePath = null;
            }
            AiAgentRequest::create([
                'company_id' => $companyId,
                'agent_name' => $request->agent_name,
                'language' => $request->language,
                'audio_file' => $filePath,
                'prompt' => $request->agent_prompt,
                'status' => 0
            ]);

            log_action("New agent request submitted for AI Vishing : {$request->agent_name}");
            return response()->json(['success' => true, 'message' => __('New agent request submitted successfully.')], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }
}
