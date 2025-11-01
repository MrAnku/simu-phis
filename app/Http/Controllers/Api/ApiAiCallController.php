<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AiAgentRequest;
use App\Models\AiCallAgent;
use App\Models\AiCallCampaign;
use App\Models\AiCallCampLive;
use App\Models\AiCallLikelifeAgent;
use App\Models\BlueCollarEmployee;
use App\Models\TrainingAssignedUser;
use App\Models\TrainingModule;
use App\Models\Users;
use App\Models\UsersGroup;
use Illuminate\Support\Carbon;
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
            $campaigns = AiCallCampaign::where('company_id', $companyId)->orderBy('id', 'desc')->get();

            $campaigns->each(function ($campaign) {
                if ($campaign->training_module == null && $campaign->scorm_training == null) {
                    $campaign->campaign_type = 'phishing';
                } else {
                    $campaign->campaign_type = 'phishing_and_training';
                }
            });
            // $agents = AiCallAgent::where('company_id', $companyId)->orWhere('company_id', 'default')->get();
            $agents = AiCallLikelifeAgent::where('company_id', $companyId)->get([
                'agent_name',
                'agent_id'
            ]);
            // $phone_numbers = $this->getPhoneNumbers();
            $phone_numbers = [
                'phone_number' => env('TWILIO_PHONE_NUMBER')
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'company' => $company,
                    'agents' => $agents,
                    'phone_numbers' => [$phone_numbers],
                    'empGroups' => $empGroups,
                    'campaigns' => [
                        'data' => $campaigns
                    ],
                    'trainings' => $trainings
                ],
                'message' => __('AI Call Campaigns fetched successfully')
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function createAiAgent(Request $request)
    {
        try {
            $validated = $request->validate([
                'agent_name' => 'required|string|max:255',
                // 'user_id' => 'required|integer',
                // 'llm' => 'required|string|max:255',
                // 'tts_provider' => 'required|string|max:255',
                'tts_voice' => 'required|string|max:255',
                'language' => 'required|string|max:10',
                'welcome_message' => 'required|string|max:400',
                'system_prompt' => 'required|string|max:5000',
                'use_memory' => 'required|boolean',
                'auto_generate_welcome_message' => 'required|boolean',
                'auto_end_call' => 'required|boolean',
                'auto_end_call_duration' => 'required|integer|min:1|max:300',
                'tts_speed' => 'required|numeric|between:0.1,2.0',
                'tts_stability' => 'required|numeric|between:0.0,1.0',
                'tts_similarity_boost' => 'required|numeric|between:0.0,1.0'
            ]);

            // $validated['company_id'] = Auth::user()->company_id;
            $requestBody = $validated;
            $requestBody['user_id'] = extractIntegers(Auth::user()->company_id);
            $requestBody['llm'] = 'gpt-4o';
            $requestBody['tts_provider'] = 'elevenlabs';

            // make api call
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post('https://callapi3.sparrowhost.net/agent', $requestBody);

            if ($response->successful()) {
                $data = $response->json();
                $validated['agent_id'] = $data['agent_id'];
                $validated['user_id'] = extractIntegers(Auth::user()->company_id);
                $validated['company_id'] = Auth::user()->company_id;
                if ($request->auto_generate_welcome_message) {
                    $validated['welcome_message'] = $data['welcome_message'];
                }

                AiCallLikelifeAgent::create($validated);
                return response()->json([
                    'success' => true,
                    'message' => __('AI Call Agent created successfully'),
                    'data' => [
                        'agent_id' => $data['agent_id']
                    ]
                ], 201);
            }

            return response()->json([
                'success' => false,
                'message' => __('Something went wrong')
            ], 500);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Validation Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function getAiAgents()
    {
        try {
            $companyId = Auth::user()->company_id;

            $agents = AiCallLikelifeAgent::where('company_id', $companyId)->get();

            return response()->json([
                'success' => true,
                'data' => $agents,
                'message' => __('AI Call Agents fetched successfully')
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function updateAiAgent(Request $request)
    {
        try {
            $validated = $request->validate([
                'agent_id' => 'required|string|exists:ai_call_likelife_agents,agent_id',
                'agent_name' => 'required|string|max:255',
                'tts_voice' => 'required|string|max:255',
                'language' => 'required|string|max:10',
                'welcome_message' => 'required|string|max:400',
                'system_prompt' => 'required|string|max:5000',
                'use_memory' => 'required|boolean',
                'auto_generate_welcome_message' => 'required|boolean',
                'auto_end_call' => 'required|boolean',
                'auto_end_call_duration' => 'required|integer|min:1|max:300',
                'tts_speed' => 'required|numeric|between:0.1,2.0',
                'tts_stability' => 'required|numeric|between:0.0,1.0',
                'tts_similarity_boost' => 'required|numeric|between:0.0,1.0'
            ]);

            $agent = AiCallLikelifeAgent::where('agent_id', $validated['agent_id'])->first();

            if (!$agent) {
                return response()->json(['success' => false, 'message' => __('Agent not found')], 404);
            }

            // Prepare request body for API call
            $requestBody = $validated;
            $requestBody['user_id'] = extractIntegers(Auth::user()->company_id);
            $requestBody['llm'] = 'gpt-4o';
            $requestBody['tts_provider'] = 'elevenlabs';

            // Make API call to update agent
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->put("https://callapi3.sparrowhost.net/agent?agent_id={$validated['agent_id']}", $requestBody);

            if ($response->successful()) {
                $data = $response->json();
                if ($request->auto_generate_welcome_message) {
                    $validated['welcome_message'] = $data['welcome_message'];
                }
                $agent->update($validated);

                return response()->json([
                    'success' => true,
                    'message' => __('AI Call Agent updated successfully')
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => __('Failed to update agent via API')
            ], 500);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Validation Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function deleteAiAgentNew(Request $request)
    {
        try {
            $agentId = $request->route('agent_id');

            if (!$agentId) {
                return response()->json(['success' => false, 'message' => __('Agent ID is required')], 422);
            }

            $agent = AiCallLikelifeAgent::where('agent_id', $agentId)->where('company_id', Auth::user()->company_id)->first();

            if (!$agent) {
                return response()->json(['success' => false, 'message' => __('Agent not found')], 404);
            }

            // Make API call to delete agent
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->delete("https://callapi3.sparrowhost.net/agent?agent_id={$agentId}&user_id=" . extractIntegers(Auth::user()->company_id));

            if ($response->successful()) {
                $agent->delete();

                return response()->json([
                    'success' => true,
                    'message' => __('AI Call Agent deleted successfully')
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => __('Something went wrong')
            ], 500);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }


    public function testAiAgent(Request $request)
    {
        try {
            $validated = $request->validate([
                'agent_id' => 'required|exists:ai_call_agents,agent_id',
                'from_phone' => ['required'],
                'to_phone' => ['required', 'regex:/^\+?[1-9]\d{1,14}$/']
            ]);

            $url = 'https://api.retellai.com/v2/create-phone-call';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('RETELL_API_KEY'),
            ])
                ->withOptions(['verify' => false])
                ->post($url, [
                    'from_number' => $validated['from_phone'],
                    'to_number' => $validated['to_phone'],
                    'override_agent_id' => $validated['agent_id']
                ]);

            // Check for a successful response
            if ($response->successful()) {
                log_action("AI Vishing Agent test call initiated for agent id {$validated['agent_id']} and phone number {$validated['to_phone']}");
                return response()->json([
                    'success' => true,
                    'message' => __('Test call initiated successfully.')
                ], 200);
            } else {
                // Handle the error, e.g., log the error or throw an exception
                log_action("Error while initiating AI Vishing Agent test call for agent id {$validated['agent_id']} and phone number {$validated['to_phone']}");
                return response()->json([
                    'success' => false,
                    'message' => __('Error: ') . $response->body()
                ], 422);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation Error: ') . $e->validator->errors()->first()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function aiAgents()
    {
        try {
            $companyId = Auth::user()->company_id;


            $default = AiCallAgent::where('company_id', 'default')->get();
            $custom = AiCallAgent::where('company_id', $companyId)->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'default' => $default,
                    'custom' => $custom
                ]
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

    public function deleteAiAgent(Request $request)
    {
        try {
            $agentId = $request->route('agent_id');

            if (!$agentId) {
                return response()->json(['success' => false, 'message' => __('Agent ID is required')], 422);
            }

            $agent = AiCallAgent::where('agent_id', $agentId)->where('company_id', Auth::user()->company_id)->first();

            if (!$agent) {
                return response()->json(['success' => false, 'message' => __('Agent not found')], 404);
            }

            //delete from retell api
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('RETELL_API_KEY'),
            ])->withOptions(['verify' => false])->delete("https://api.retellai.com/delete-agent/{$agentId}");

            if ($response->successful()) {
                $agent->delete();
                log_action("AI Vishing Agent deleted: {$agent->agent_name}");
                return response()->json(['success' => true, 'message' => __('Agent deleted successfully')], 200);
            } else {
                log_action("Error while deleting AI Vishing Agent: {$response->body()}");
                return response()->json(['success' => false, 'message' => __('Error: ') . $response->body()], 422);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
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
                if (is_array($value)) {
                    array_walk_recursive($value, function ($item) {
                        if (preg_match('/<[^>]*>|<\?php/', $item)) {
                            return response()->json([
                                'success' => false,
                                'message' => __('Invalid input detected.')
                            ], 422);
                        }
                    });
                } else {
                    if (preg_match('/<[^>]*>|<\?php/', $value)) {
                        return response()->json([
                            'success' => false,
                            'message' => __('Invalid input detected.')
                        ], 422);
                    }
                }
            }
            $request->merge($input);

            //xss check end
            $validated = $request->validate(
                [
                    'camp_name' => 'required|string|min:5|max:50',
                    'users_group' => 'required|string',
                    'training_module' => 'nullable|array',
                    'scorm_training' => 'nullable|array',
                    'training_lang' => 'nullable|string',
                    'campaign_type' => 'required|in:phishing_and_training,phishing',
                    'training_type' => 'nullable|string',
                    'employee_type' => 'required|in:normal,bluecollar',
                    'users_grp_name' => 'required|string',
                    'ai_agent_name' => 'required|string',
                    'ai_agent' => 'required|string',
                    'ai_phone' => 'required|string',
                    'schedule_type' => 'required|in:immediately,scheduled,schLater',
                    'scheduled_at' => 'required_if:schedule_type,scheduled|string',
                    'training_assignment' => 'required|string|in:random,all',
                    'selected_users' => 'nullable|array',
                    "call_freq" => 'required|in:once,weekly,monthly,quarterly',
                    'expire_after' => 'required_if:call_freq,weekly,monthly,quarterly|nullable|date|after_or_equal:tomorrow',
                    'policies' => 'nullable|array',
                    "schedule_date" => 'nullable|required_if:schedule_type,scheduled|date|after_or_equal:today',
                    "time_zone" => 'nullable|required_if:schedule_type,scheduled|string',
                    'start_time' => [
                        'nullable',
                        'required_if:schedule_type,scheduled',
                        'date_format:Y-m-d H:i:s',
                        function ($attribute, $value, $fail) {
                            $inputDate = Carbon::parse($value)->startOfDay();
                            $today = Carbon::today();

                            if ($inputDate->lt($today)) {
                                $fail('The ' . $attribute . ' must not be a past date.');
                            }
                        },
                    ],
                    'end_time'   => 'nullable|required_if:schedule_type,scheduled|date_format:Y-m-d H:i:s|after:start_time'
                ],
                [
                    "camp_name.min" => __('Campaign Name must be at least 5 Characters')
                ]
            );

            if ($request->employee_type == 'normal') {
                if (!UsersGroup::where('group_id', $request->users_group)->where('users', '!=', null)->exists()) {
                    return response()->json(['success' => false, 'message' => __('Employee Group does not exist or No user found in group')], 422);
                }

                //checking if all users have valid mobile number
                $hasPhoneNo = $this->groupHasPhoneNumber($request->users_group);

                if (!$hasPhoneNo) {
                    return response()->json(['success' => false, 'message' => __('Please check if selected employee division has valid phone number')], 422);
                }
            }

            $companyId = Auth::user()->company_id;
            $campId = Str::random(6);
            $validated = $request->all();
            $launchType = $request->schedule_type;

            if ($launchType === 'immediately') {
                log_action("AI campaign created");
                return $this->handleImmediateLaunch($validated, $campId, $companyId);
            }

            if ($launchType === 'scheduled') {
                log_action("AI campaign scheduled");
                return $this->handleScheduledLaunch($validated, $campId, $companyId);
            }

            if ($request['schedule_type'] === 'schLater') {
                log_action("AI campaign saved for scheduling later");
                return $this->handleLaterLaunch($validated, $campId);
            }

            log_action("AI campaign launched with invalid launch type");
            return response()->json([
                'success' => false,
                'message' => __('Invalid launch type')
            ], 422);








            // if ($request->schedule_type === 'immediately') {
            //     $scheduledAt = Carbon::now()->toDateTimeString();
            // } else {
            //     $scheduledAt = Carbon::parse($request->scheduled_at)->toDateTimeString();
            // }

            // $status = $request->schedule_type === 'immediately' ? 'running' : 'pending';

            // AiCallCampaign::create([
            //     'campaign_id' => $campId,
            //     'campaign_name' => $request->camp_name,
            //     'employee_type' => $request->employee_type,
            //     'users_group' => $request->users_group,
            //     'selected_users' => $request->selected_users != null ? json_encode($request->selected_users) : null,
            //     'users_grp_name' => $request->users_grp_name,
            //     'training_module' => $request->campaign_type == 'phishing' || empty($request->training_module) ? null : json_encode($request->training_module),
            //     'scorm_training' => $request->campaign_type == 'phishing' || empty($request->scorm_training) ? null : json_encode($request->scorm_training),
            //     'training_assignment' => ($request->campaign_type == 'phishing') ? null : $request->training_assignment,
            //     'training_lang' => $request->campaign_type == 'phishing' ? null : $request->training_lang,
            //     'training_type' => $request->campaign_type == 'phishing' ? null : $request->training_type,
            //     'policies' => (is_array($request->policies) && !empty($request->policies)) ? json_encode($request->policies) : null,
            //     'ai_agent' => $request->ai_agent,
            //     'ai_agent_name' => $request->ai_agent_name,
            //     'phone_no' => $request->ai_phone,
            //     'status' => $status,
            //     'launch_time' => $scheduledAt,
            //     'launch_type' => $request->schedule_type,
            //     'company_id' => $companyId,
            //     'schedule_date' => $request->schedule_type === 'scheduled' ? $request->schedule_date : null,
            //     'time_zone'      => $request->schedule_type === 'scheduled' ? $request->time_zone : null,
            //     'start_time'      => $request->schedule_type === 'scheduled' ? $request->start_time : null,
            //     'end_time'      => $request->schedule_type === 'scheduled' ? $request->end_time : null,
            //     'launch_date' => $request->schedule_type === 'immediately' ? now() : $request->schedule_date,
            //     'call_freq' => $request->call_freq,
            //     'expire_after' => $request->expire_after ?? null,
            // ]);

            // if ($status === 'running') {
            //     $this->makeCampaignLive($campId);
            // }



            // log_action('Campaign for AI Vishing simulation created');

            // return response()->json(['success' => true, 'message' => __('Campaign created successfully.')], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }


    private function handleImmediateLaunch($validated, $campId)
    {
        try {
            AiCallCampaign::create([
                'campaign_id' => $campId,
                'campaign_name' => $validated['camp_name'],
                'employee_type' => $validated['employee_type'],
                'users_group' => $validated['users_group'],
                'selected_users' => $validated['selected_users'] != null ? json_encode($validated['selected_users']) : null,
                'users_grp_name' => $validated['users_grp_name'],
                'training_module' => $validated['campaign_type'] == 'phishing' || empty($validated['training_module']) ? null : json_encode($validated['training_module']),
                'scorm_training' => $validated['campaign_type'] == 'phishing' || empty($validated['scorm_training']) ? null : json_encode($validated['scorm_training']),
                'training_assignment' => ($validated['campaign_type'] == 'phishing') ? null : $validated['training_assignment'],
                'training_lang' => $validated['campaign_type'] == 'phishing' ? null : $validated['training_lang'],
                'training_type' => $validated['campaign_type'] == 'phishing' ? null : $validated['training_type'],
                'policies' => (is_array($validated['policies']) && !empty($validated['policies'])) ? json_encode($validated['policies']) : null,
                'ai_agent' => $validated['ai_agent'],
                'ai_agent_name' => $validated['ai_agent_name'],
                'phone_no' => $validated['ai_phone'],
                'status' => 'running',
                'launch_time' => now(),
                'launch_type' => $validated['schedule_type'],
                'company_id' => Auth::user()->company_id,
                'schedule_date' => $validated['schedule_type'] === 'scheduled' ? $validated['schedule_date'] : null,
                'time_zone'      => $validated['schedule_type'] === 'scheduled' ? $validated['time_zone'] : null,
                'start_time'      => $validated['schedule_type'] === 'scheduled' ? $validated['start_time'] : null,
                'end_time'      => $validated['schedule_type'] === 'scheduled' ? $validated['end_time'] : null,
                'launch_date' => $validated['schedule_type'] === 'immediately' ? now() : $validated['schedule_date'],
                'call_freq' => $validated['call_freq'],
                'expire_after' => $validated['expire_after'] ?? null,
            ]);
            $this->makeCampaignLive($campId);

            log_action("AI Campaign Created");

            return response()->json([
                'success' => true,
                'message' => __('Campaign created successfully'),
                'campaign_id' => $campId
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function handleScheduledLaunch($validated, $campId)
    {
        try {
            AiCallCampaign::create([
                'campaign_id' => $campId,
                'campaign_name' => $validated['camp_name'],
                'employee_type' => $validated['employee_type'],
                'users_group' => $validated['users_group'],
                'selected_users' => $validated['selected_users'] != null ? json_encode($validated['selected_users']) : null,
                'users_grp_name' => $validated['users_grp_name'],
                'training_module' => $validated['campaign_type'] == 'phishing' || empty($validated['training_module']) ? null : json_encode($validated['training_module']),
                'scorm_training' => $validated['campaign_type'] == 'phishing' || empty($validated['scorm_training']) ? null : json_encode($validated['scorm_training']),
                'training_assignment' => ($validated['campaign_type'] == 'phishing') ? null : $validated['training_assignment'],
                'training_lang' => $validated['campaign_type'] == 'phishing' ? null : $validated['training_lang'],
                'training_type' => $validated['campaign_type'] == 'phishing' ? null : $validated['training_type'],
                'policies' => (is_array($validated['policies']) && !empty($validated['policies'])) ? json_encode($validated['policies']) : null,
                'ai_agent' => $validated['ai_agent'],
                'ai_agent_name' => $validated['ai_agent_name'],
                'phone_no' => $validated['ai_phone'],
                'status' => 'pending',
                'launch_time' => now(),
                'launch_type' => $validated['schedule_type'],
                'company_id' => Auth::user()->company_id,
                'schedule_date' => $validated['schedule_type'] === 'scheduled' ? $validated['schedule_date'] : null,
                'time_zone'      => $validated['schedule_type'] === 'scheduled' ? $validated['time_zone'] : null,
                'start_time'      => $validated['schedule_type'] === 'scheduled' ? $validated['start_time'] : null,
                'end_time'      => $validated['schedule_type'] === 'scheduled' ? $validated['end_time'] : null,
                'launch_date' => $validated['schedule_type'] === 'immediately' ? now() : $validated['schedule_date'],
                'call_freq' => $validated['call_freq'],
                'expire_after' => $validated['expire_after'] ?? null,
            ]);

            log_action("AI Campaign created : {$validated['camp_name']}");

            return response()->json([
                'success' => true,
                'message' => __('Campaign created successfully'),
                'campaign_id' => $campId
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function handleLaterLaunch($data, $campId)
    {
        AiCallCampaign::create([
            'campaign_id' => $campId,
            'campaign_name' => $data['camp_name'],
            'employee_type' => $data['employee_type'],
            'users_group' => $data['users_group'],
            'selected_users' => $data['selected_users'] != null ? json_encode($data['selected_users']) : null,
            'users_grp_name' => $data['users_grp_name'],
            'training_module' => $data['campaign_type'] == 'phishing' || empty($data['training_module']) ? null : json_encode($data['training_module']),
            'scorm_training' => $data['campaign_type'] == 'phishing' || empty($data['scorm_training']) ? null : json_encode($data['scorm_training']),
            'training_assignment' => ($data['campaign_type'] == 'phishing') ? null : $data['training_assignment'],
            'training_lang' => $data['campaign_type'] == 'phishing' ? null : $data['training_lang'],
            'training_type' => $data['campaign_type'] == 'phishing' ? null : $data['training_type'],
            'policies' => (is_array($data['policies']) && !empty($data['policies'])) ? json_encode($data['policies']) : null,
            'ai_agent' => $data['ai_agent'],
            'ai_agent_name' => $data['ai_agent_name'],
            'phone_no' => $data['ai_phone'],
            'status' => 'not_scheduled',
            'launch_time' => now(),
            'launch_type' => $data['schedule_type'],
            'company_id' => Auth::user()->company_id,
            'schedule_date' => $data['schedule_type'] === 'scheduled' ? $data['schedule_date'] : null,
            'time_zone'      => $data['schedule_type'] === 'scheduled' ? $data['time_zone'] : null,
            'start_time'      => $data['schedule_type'] === 'scheduled' ? $data['start_time'] : null,
            'end_time'      => $data['schedule_type'] === 'scheduled' ? $data['end_time'] : null,
            'launch_date' => $data['schedule_type'] === 'immediately' ? now() : $data['schedule_date'],
            'call_freq' => $data['call_freq'],
            'expire_after' => $data['expire_after'] ?? null,
        ]);

        log_action('AI campaign created for schedule later');

        return response()->json([
            'success' => true,
            'message' => __('Campaign saved successfully!')
        ]);
    }

    private function groupHasPhoneNumber($groupid)
    {
        // Retrieve the JSON-encoded users column and decode it
        $userIdsJson = UsersGroup::where('group_id', $groupid)->value('users');

        // Decode the JSON into an array
        $userIds = json_decode($userIdsJson, true);

        // If decoding fails or no user IDs exist, return false
        if (empty($userIds) || !is_array($userIds)) {
            return false;
        }

        // filter users with valid whatsapp numbers
        $users = Users::whereIn('id', $userIds)
            ->get();

        $users = $users->filter(function ($user) {
            // Check if the whatsapp column is not null
            return $user->whatsapp !== null;
        });

        // If no users with valid whatsapp numbers are found, return false
        if ($users->isEmpty()) {
            return false;
        }


        return true;
    }

    private function makeCampaignLive($campaignid)
    {
        $companyId = Auth::user()->company_id;

        $campaign = AiCallCampaign::where('campaign_id', $campaignid)->first();

        if ($campaign) {

            if ($campaign->employee_type == 'normal') {
                $userIdsJson = UsersGroup::where('group_id', $campaign->users_group)->value('users');
                $userIds = json_decode($userIdsJson, true);
                if ($campaign->selected_users == null) {
                    $users = Users::whereIn('id', $userIds)->get();
                } else {
                    $users = Users::whereIn('id', json_decode($campaign->selected_users, true))->get();
                }
            }

            if ($campaign->employee_type == 'bluecollar') {
                if ($campaign->selected_users == null) {
                    $users = BlueCollarEmployee::where('group_id', $campaign->users_group)->get();
                } else {
                    $users = BlueCollarEmployee::whereIn('id', json_decode($campaign->selected_users, true))->get();
                }
            }

            if ($users) {
                foreach ($users as $user) {

                    if ($user->whatsapp == null) {
                        continue;
                    }

                    $training_mods = json_decode($campaign->training_module, true);
                    $scorms = json_decode($campaign->scorm_training, true);

                    AiCallCampLive::create([
                        'campaign_id' => $campaign->campaign_id,
                        'campaign_name' => $campaign->campaign_name,
                        'employee_type' => $campaign->employee_type,
                        'user_id' => $user->id,
                        'user_name' => $user->user_name,
                        'user_email' => $user->user_email,
                        'training_module' => (empty($training_mods) ? null :  $training_mods[array_rand($training_mods)]),
                        'scorm_training' => (empty($scorms) ? null :  $scorms[array_rand($scorms)]),
                        'training_lang' => $campaign->training_lang ?? null,
                        'training_type' => $campaign->training_type ?? null,
                        'from_mobile' => $campaign->phone_no,
                        'to_mobile' => "+" . $user->whatsapp,
                        'agent_id' => $campaign->ai_agent,
                        'status' => 'pending',
                        'company_id' => $campaign->company_id,
                    ]);

                    // Audit log
                    audit_log(
                        $campaign->company_id,
                        $user->user_email,
                        $user->whatsapp,
                        'AI_CAMPAIGN_SIMULATED',
                        "The campaign ‘{$campaign->campaign_name}’ has been sent to {$user->whatsapp}",
                        'normal'
                    );
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

            $campaign = AiCallCampaign::with(['individualCamps'])
                ->where('campaign_id', $id)
                ->first();

            $trainingModules = $campaign->trainingModules()->get();
            $campaign->training_modules_data = $trainingModules;

            $scormTrainings = $campaign->scormTrainings()->get();
            $campaign->scorm_trainings_data = $scormTrainings;

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

            if ($request->deleteTrainingsAlso == 1) {
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

    public function  fetchCallReport($callId)
    {
        try {
            if (!$callId) {
                return response()->json(['success' => false, 'message' => __('Call ID is required')], 422);
            }
            //checking if call report is available or not

            $localReport = AiCallCampLive::where('call_id', $callId)->first();
            if (!$localReport) {
                return response()->json(['success' => false, 'message' => __('Call report not found')], 404);
            }
            if ($localReport->call_report == null) {

                $callReport = $this->getReportFromApi($callId);
                if (!$callReport) {
                    return response()->json(['success' => false, 'message' => __('Call report not found')], 404);
                }
                $callReport['agent_id'] = $localReport->agent_id;
                $callReport['call_id'] = $callId;
                $callReport['disconnect_reason'] = 'user_hangup';
                $callReport['call_status'] = $localReport->status;
                $callReport['compromised'] = $localReport->compromised == 1 ? "Yes" : "No";
                if ($localReport->compromised == 1) {
                    $callReport['interactions'] = null;
                }
                $callReport['training_assigned'] = $localReport->training_assigned == 1 ? "Yes" : "No";
                $localReport->call_report = json_encode($callReport);
                $localReport->save();

                return response()->json(['success' => true, 'data' => $callReport], 200);
            } else {
                $callReport = json_decode($localReport->call_report, true);
                if ($localReport->compromised == 1) {
                    $callReport['interactions'] = null;
                }

                return response()->json(['success' => true, 'data' => $callReport], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function fetchCallRecording($callId)
    {
        try {
            if (!$callId) {
                return response()->json(['success' => false, 'message' => __('Call ID is required')], 422);
            }

            // Make the HTTP request
            $response = Http::get('https://callapi3.sparrowhost.net/audio/' . $callId);

            if ($response->successful()) {
                $data = $response->json();

                return response()->json(['success' => true, 'data' => $data], 200);
            } else {
                return response()->json(['success' => false, 'message' => __('Call recording not found')], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    private function getReportFromApi($callId)
    {
        try {
            // Make the HTTP request
            $response = Http::get('https://callapi3.sparrowhost.net/call/call_info/' . $callId);

            if ($response->successful()) {
                $data = $response->json();

                return $data;
            }
            return false;
        } catch (\Exception $e) {
            return false;
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
                'voice_file' => 'nullable|file|mimes:mp3,wav,aac',
            ]);

            $companyId = Auth::user()->company_id;

            if ($request->hasFile('voice_file')) {

                $file = $request->file('voice_file');

                // Generate a random name for the file
                $randomName = generateRandom(32);
                $extension = $file->getClientOriginalExtension();
                $newFilename = $randomName . '.' . $extension;

                $filePath = $request->file('voice_file')->storeAs('/deepfake_audio', $newFilename, 's3');
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

    public function relaunchCampaign(Request $request)
    {
        try {
            $campid = $request->route('campaign_id');

            if (!$campid) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required'),
                ], 422);
            }
            $campaign = AiCallCampaign::where('campaign_id', $campid)->first();

            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign not found'),
                ], 404);
            }
            //check if the agent id starts with agent i.e. agent_bdjsdhbjshdbs
            if (strpos($campaign->ai_agent, 'agent_') === 0) {
                return response()->json([
                    'success' => false,
                    'message' => __('This campaign can not be relaunched. Please create new campaign.'),
                ], 422);
            }

            //check if the agent is available 
            $agentExists = AiCallLikelifeAgent::where('agent_id', $campaign->ai_agent)->exists();
            if (!$agentExists) {
                return response()->json([
                    'success' => false,
                    'message' => __('The agent associated with this campaign is no longer available. Please create new campaign.'),
                ], 422);
            }

            $company_id = Auth::user()->company_id;

            AiCallCampaign::where('campaign_id', $campid)
                ->where('company_id', $company_id)
                ->update([
                    'created_at' => now(),
                    'status' => 'pending'
                ]);

            // Update campaign_live table
            AiCallCampLive::where('campaign_id', $campid)
                ->where('company_id', $company_id)
                ->update([
                    'call_id' => null,
                    'status' => 'pending',
                    'training_assigned' => '0',
                    'compromised' => '0',
                    'call_send_response' => null,
                    'call_end_response' => null,
                    'call_report' => null,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

            log_action('AI Call campaign relaunched');
            return response()->json([
                'success' => true,
                'message' => __('Campaign relaunched successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function rescheduleCampaign(Request $request)
    {
        try {
            $campaignId = $request->route('campaign_id', null);
            if (!$campaignId) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required')
                ], 422);
            }

            $request->validate([
                'schedule_type' => 'required|in:immediately,scheduled',
                "schedule_date" => 'nullable|required_if:schedule_type,scheduled|date|after_or_equal:today',
                "call_freq" => 'required|in:once,weekly,monthly,quarterly',
                "time_zone" => 'nullable|string|required_if:schedule_type,scheduled',
                'start_time' => [
                    'nullable',
                    'required_if:schedule_type,scheduled',
                    'date_format:Y-m-d H:i:s',
                    function ($attribute, $value, $fail) {
                        $inputDate = Carbon::parse($value)->startOfDay();
                        $today = Carbon::today();

                        if ($inputDate->lt($today)) {
                            $fail('The ' . $attribute . ' must not be a past date.');
                        }
                    },
                ],
                'end_time'   => 'nullable|required_if:schedule_type,scheduled|date_format:Y-m-d H:i:s|after:start_time',
                'expire_after' => 'required_if:call_freq,weekly,monthly,quarterly|nullable|date|after_or_equal:tomorrow',
            ]);

            $companyId = Auth::user()->company_id;

            $campaign = AiCallCampaign::where('campaign_id', $campaignId)
                ->where('company_id', $companyId)
                ->first();
            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign not found')
                ], 404);
            }

            if ($request->schedule_type == 'immediately') {
                $call_freq = $request->call_freq;
                $expire_after = $request->expire_after;

                // Retrieve the campaign instance
                $campaign = AiCallCampaign::where('campaign_id', $campaignId)->where('company_id', $companyId)->first();

                $groupExists = UsersGroup::where('group_id', $campaign->users_group)->where('company_id', $companyId)->exists();
                if (!$groupExists) {
                    return ['status' => 0, 'msg' => __('Group not found')];
                }

                // Retrieve the users in the specified group
                $userIdsJson = UsersGroup::where('group_id', $campaign->users_group)
                    ->where('company_id', $companyId)
                    ->value('users');

                $userIds = json_decode($userIdsJson, true);
                $users = Users::whereIn('id', $userIds)->get();

                // Check if users exist in the group
                if ($users->isEmpty()) {
                    return ['status' => 0, 'msg' => __('No employees available in this group')];
                }

                $this->makeCampaignLive($campaignId);

                // Update the campaign status to 'running'
                $campaign->update([
                    'status' => 'running',
                    'launch_type' => 'immediately',
                    'launch_time' => now(),
                    'call_freq' => $call_freq,
                    'expire_after' => $expire_after
                ]);
            }

            if ($request->launch_type == 'scheduled') {
                $campaign->launch_time =  now();
                $campaign->launch_type = 'scheduled';
                $campaign->schedule_date = $request->schedule_date;
                $campaign->launch_date = $request->schedule_date;
                $campaign->call_freq = $request->call_freq;
                $campaign->start_time = $request->start_time;
                $campaign->end_time = $request->end_time;
                $campaign->time_zone = $request->time_zone;
                $campaign->expire_after = $request->expire_after;
                $campaign->status = 'pending';
                $campaign->save();
            }

            log_action('AI campaign rescheduled');

            return response()->json([
                'success' => true,
                'message' => __('Campaign rescheduled successfully!')
            ]);
        } catch (ValidationException $e) {
            log_action('Validation error occured while creating AI campaign');
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->validator->errors()->first()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }
}
