<?php

namespace App\Http\Controllers\Api;

use App\Models\Users;
use App\Models\UsersGroup;
use App\Models\WaCampaign;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\TrainingModule;
use App\Models\WaLiveCampaign;
use App\Models\BlueCollarGroup;
use App\Models\WhatsappActivity;
use App\Models\BlueCollarEmployee;
use App\Models\WhatsappTempRequest;
use App\Http\Controllers\Controller;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\CompanyWhatsappConfig;
use App\Models\CompanyWhatsappTemplate;
use App\Models\RequestWhatsappTemplate;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ApiWaCampaignController extends Controller
{

    public function index()
    {
        try {
            $company_id = Auth::user()->company_id;
            $config = CompanyWhatsappConfig::where('company_id', $company_id)->first();

            $employee_groups = UsersGroup::where('company_id', $company_id)->where('users', '!=', null)->get();
            $hasTemplates = CompanyWhatsappTemplate::where('company_id', $company_id)->first();
            if (!$hasTemplates) {
                $templates = [];
            } else {
                $templates = json_decode($hasTemplates->template, true)['data'];
            }

            $campaigns = WaCampaign::with(['trainingData'])
                ->where('company_id', $company_id)
                ->orderByDesc('id')
                ->get();
            $campaigns->each(function ($campaign) {
                if ($campaign->employee_type == 'normal') {
                    $campaign->user_group_data = $campaign->userGroupData()->first();
                } else {
                    $campaign->user_group_data = BlueCollarGroup::where('group_id', $campaign->users_group)->first();
                }
            });

            $trainings = TrainingModule::where('company_id', $company_id)
                ->orWhere('company_id', 'default')->get();

            return response()->json([
                'success' => true,
                'message' => __('Whatsapp Campaigns'),
                'data' => [
                    'config' => $config,
                    'employee_groups' => $employee_groups,
                    'templates' => $templates,
                    'wa_campaigns' => $campaigns,
                    'trainings' => $trainings,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function createCampaign(Request $request)
    {
        try {
            $validated = $request->validate([
                'campaign_name' => 'required|string|max:255',
                'campaign_type' => 'required|in:phishing_and_training,phishing',
                'employee_type' => 'required|in:normal,bluecollar',
                'phishing_website' => 'required|integer',
                'training_module' => 'nullable|array',
                'scorm_training' => 'nullable|array',
                'training_assignment' => 'nullable|in:all,random',
                'days_until_due' => 'nullable|integer|min:1',
                'training_lang' => 'nullable|string|size:2',
                'training_type' => 'nullable',
                'training_on_click' => 'required|string|in:true,false',
                'compromise_on_click' => 'required|string|in:true,false',
                'template_name' => 'required|string|max:255',
                'users_group' => 'required|string|max:255',
                'schedule_type' => 'required|in:immediately,scheduled',
                'launch_time' => 'nullable|date',
                'variables' => 'required|array',
                'selected_users' => 'nullable|array',
            ]);

            //check if the selected users group has users has whatsapp number
            if ($request->employee_type == 'normal') {
                if (!atLeastOneUserWithWhatsapp($validated['users_group'], Auth::user()->company_id)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('No employees with WhatsApp number found in the selected division.'),
                    ], 422);
                }
            }

            if ($validated['schedule_type'] == 'immediately') {
                return $this->handleImmediateCampaign($validated);
            } else {
                return $this->handleScheduledCampaign($validated);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __("Error") . " :" . $e->validator->errors()->first(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }



    private function handleImmediateCampaign($validated)
    {
        try {
            $campaign_id = Str::random(6);
            WaCampaign::create([
                'campaign_id' => $campaign_id,
                'campaign_name' => $validated['campaign_name'],
                'campaign_type' => $validated['campaign_type'],
                'employee_type' => $validated['employee_type'],
                'phishing_website' => $validated['phishing_website'],
                'training_module' => $validated['campaign_type'] == 'phishing' || empty($validated['training_module']) ? null : json_encode($validated['training_module']),
                'scorm_training' => $validated['campaign_type'] == 'phishing' || empty($validated['scorm_training']) ? null : json_encode($validated['scorm_training']),
                'training_assignment' => $validated['campaign_type'] == 'phishing' ? null : $validated['training_assignment'],
                'days_until_due' => $validated['campaign_type'] == 'phishing' ? null : $validated['days_until_due'],
                'training_lang' => $validated['campaign_type'] == 'phishing' ? null : $validated['training_lang'],
                'training_type' => $validated['campaign_type'] == 'phishing' ? null : $validated['training_type'],
                'training_on_click' => $validated['training_on_click'] == 'false' ? 0 : 1,
                'compromise_on_click' => $validated['compromise_on_click'] == 'false' ? 0 : 1,
                'template_name' => $validated['template_name'],
                'users_group' => $validated['users_group'],
                'selected_users' => $validated['selected_users'] != null ? json_encode($validated['selected_users']) : null,
                'schedule_type' => $validated['schedule_type'],
                'launch_time' => now(),
                'status' => 'running',
                'variables' => json_encode($validated['variables']),
                'company_id' => Auth::user()->company_id,
            ]);

            if ($validated['employee_type'] == 'normal') {
                $userIdsJson = UsersGroup::where('group_id', $validated['users_group'])->value('users');
                $userIds = json_decode($userIdsJson, true);
                if ($validated['selected_users'] == null) {
                    $users = Users::whereIn('id', $userIds)->get();
                } else {
                    $users = Users::whereIn('id', $validated['selected_users'])->get();
                }
            }

            if ($validated['employee_type'] == 'bluecollar') {

                if ($validated['selected_users'] == null) {
                    $users = BlueCollarEmployee::where('group_id', $validated['users_group'])->get();
                } else {
                    $users = BlueCollarEmployee::whereIn('id', $validated['selected_users'])->get();
                }
            }


            if ($users->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => __('No employees available in this group')
                ], 422);
            }

            foreach ($users as $user) {

                if (!$user->whatsapp) {
                    continue;
                }
                $camp_live = WaLiveCampaign::create([
                    'campaign_id' => $campaign_id,
                    'campaign_name' => $validated['campaign_name'],
                    'campaign_type' => $validated['campaign_type'],
                    'employee_type' => $validated['employee_type'],
                    'user_name' => $user->user_name,
                    'user_id' => $user->id,
                    'user_email' => $user->user_email ?? null,
                    'user_phone' => $user->whatsapp,
                    'phishing_website' => $validated['phishing_website'],
                    'training_module' => ($validated['campaign_type'] == 'phishing') || empty($validated['training_module']) ? null : $validated['training_module'][array_rand($validated['training_module'])],
                    'scorm_training' => ($validated['campaign_type'] == 'phishing') || empty($validated['scorm_training']) ? null : $validated['scorm_training'][array_rand($validated['scorm_training'])],
                    'training_assignment' => $validated['campaign_type'] == 'phishing' ? null : $validated['training_assignment'],

                    'days_until_due' => $validated['campaign_type'] == 'phishing' ? null : $validated['days_until_due'],
                    'training_lang' => $validated['campaign_type'] == 'phishing' ? null : $validated['training_lang'],
                    'training_type' => $validated['campaign_type'] == 'phishing' ? null : $validated['training_type'],
                    'template_name' => $validated['template_name'],
                    'variables' => json_encode($validated['variables']),
                    'company_id' => Auth::user()->company_id,
                ]);

                WhatsappActivity::create([
                    'campaign_id' => $camp_live->campaign_id,
                    'campaign_live_id' => $camp_live->id,
                    'company_id' => $camp_live->company_id,
                ]);

                // Audit log
                audit_log(
                    Auth::user()->company_id,
                    $user->user_email ?? null,
                    $user->whatsapp ?? null,
                    'WHATSAPP_CAMPAIGN_SIMULATED',
                    "The campaign ‘{$validated['campaign_name']}’ has been sent to " . ($user->user_email ?? $user->whatsapp),
                    $validated['employee_type']
                );
            }

            log_action("Whatsapp Campaign Created");

            return response()->json([
                'success' => true,
                'message' => __('Campaign created successfully'),
                'campaign_id' => $campaign_id
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function handleScheduledCampaign($validated)
    {
        try {
            $campaign_id = Str::random(6);
            WaCampaign::create([
                'campaign_id' => $campaign_id,
                'campaign_name' => $validated['campaign_name'],
                'campaign_type' => $validated['campaign_type'],
                'employee_type' => $validated['employee_type'],
                'phishing_website' => $validated['phishing_website'],
                'training_module' => $validated['campaign_type'] == 'phishing' || empty($validated['training_module']) ? null : json_encode($validated['training_module']),
                'scorm_training' => $validated['campaign_type'] == 'phishing' || empty($validated['scorm_training']) ? null : json_encode($validated['scorm_training']),

                'training_assignment' => $validated['campaign_type'] == 'phishing' ? null : $validated['training_assignment'],
                'days_until_due' => $validated['campaign_type'] == 'phishing' ? null : $validated['days_until_due'],
                'training_lang' => $validated['campaign_type'] == 'phishing' ? null : $validated['training_lang'],
                'training_type' => $validated['campaign_type'] == 'phishing' ? null : $validated['training_type'],
                'training_on_click' => $validated['training_on_click'] == 'false' ? 0 : 1,
                'compromise_on_click' => $validated['compromise_on_click'] == 'false' ? 0 : 1,
                'template_name' => $validated['template_name'],
                'users_group' => $validated['users_group'],
                'selected_users' => $validated['selected_users'] != null ? json_encode($validated['selected_users']) : null,
                'schedule_type' => $validated['schedule_type'],
                'launch_time' => $validated['launch_time'],
                'status' => 'pending',
                'variables' => json_encode($validated['variables']),
                'company_id' => Auth::user()->company_id,
            ]);

            log_action("Whatsapp Campaign created : {$validated['campaign_name']}");

            return response()->json([
                'success' => true,
                'message' => __('Campaign created successfully'),
                'campaign_id' => $campaign_id
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteCampaign(Request $request)
    {
        try {
            $campaign_id = $request->route('campaign_id');
            if (!$campaign_id) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required!'),
                ], 422);
            }

            $campaign = WaCampaign::where('campaign_id', $campaign_id)->where('company_id', Auth::user()->company_id)->first();

            if (!$campaign) {
                log_action('Something went wrong while deleting WhatsApp campaign');
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign not found!'),
                ], 404);
            }

            if ($request->deleteTrainingsAlso == 1) {
                TrainingAssignedUser::where('campaign_id', $campaign_id)->delete();
            }

            $campaign->delete();

            WaLiveCampaign::where('campaign_id', $campaign_id)->where('company_id', Auth::user()->company_id)->delete();
            WhatsappActivity::where('campaign_id', $campaign_id)->where('company_id', Auth::user()->company_id)->delete();

            log_action('WhatsApp campaign deleted');
            return response()->json([
                'success' => true,
                'message' => __('Campaign deleted successfully!'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function fetchCampaign(Request $request)
    {
        try {
            $campaign_id = $request->route('campaign_id');
            if (!$campaign_id) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required!'),
                ], 422);
            }


            $campaigns = WaLiveCampaign::with([
                'whatsTrainingData',
                'phishingWebsite',
                'whatsTrainingData.trainingData',
                'scormTrainingData.scormTrainingData',
                'campaignActivity'
            ])
                ->where('campaign_id', $campaign_id)
                ->where('company_id', Auth::user()->company_id)
                ->get();
            $campaign = WaCampaign::where('campaign_id', $campaign_id)
                ->where('company_id', Auth::user()->company_id)
                ->first();
            $training_modules_data = $campaign->trainingModules()->get();
            $campaigns->each(function ($campaign) use ($training_modules_data) {
                $campaign->training_modules = $training_modules_data;
            });

            // if ($campaigns->isEmpty()) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => __('Campaign not found!'),
            //     ], 404);
            // }

            return response()->json([
                'success' => true,
                'message' => __('Campaign fetched successfully!'),
                'data' => $campaigns
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function saveConfig(Request $request)
    {
        try {
            $validated = $request->validate([
                'from_phone_id' => 'required|numeric',
                'access_token' => 'required',
                'business_id' => 'required|numeric',
            ]);

            $company_id = Auth::user()->company_id;
            $validated['company_id'] = $company_id;

            $config = CompanyWhatsappConfig::where('company_id', $company_id)->first();
            if ($config) {
                return response()->json([
                    'success' => false,
                    'message' => __('Configuration already exists!'),
                ], 422);
            } else {
                CompanyWhatsappConfig::create($validated);
            }

            log_action('Whatsapp Configuration saved');

            return response()->json([
                'success' => true,
                'message' => __('Configuration saved successfully!'),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->validator->errors()->first(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function updateConfig(Request $request)
    {
        try {
            $validated = $request->validate([
                'from_phone_id' => 'required|numeric',
                'access_token' => 'required',
                'business_id' => 'required|numeric',
            ]);

            $company_id = Auth::user()->company_id;
            $validated['company_id'] = $company_id;

            $config = CompanyWhatsappConfig::where('company_id', $company_id)->first();
            if ($config) {
                $config->update($validated);
            } else {
                CompanyWhatsappConfig::create($validated);
            }

            log_action("Whatsapp Configuration Updated");

            return response()->json([
                'success' => true,
                'message' => __('Configuration updated successfully!'),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->validator->errors()->first(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function syncTemplates(Request $request)
    {
        try {
            $company_id = Auth::user()->company_id;
            $config = CompanyWhatsappConfig::where('company_id', $company_id)->first();
            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => __('Configuration not found!'),
                ], 422);
            }
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $config->access_token
            ])->withoutVerifying()->get('https://graph.facebook.com/v22.0/' . $config->business_id . '/message_templates');

            if ($response->successful()) {
                $responseData = $response->json();
                if (isset($responseData['error'])) {
                    return response()->json(['error' => $responseData['error']]);
                }
                CompanyWhatsappTemplate::where('company_id', $company_id)->delete();
                CompanyWhatsappTemplate::create([
                    'template' => json_encode($responseData),
                    'company_id' => $company_id
                ]);
                return response()->json([
                    'success' => true,
                    'message' => __('Templates synced successfully!'),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('Error: ') . $response->body(),
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function groupUsers(Request $request)
    {
        try {
            $type = $request->route('employee_type');
            if (!$type) {
                return response()->json([
                    'success' => false,
                    'message' => __('Employee type is required!'),
                ], 422);
            }
            $companyId = Auth::user()->company_id;
            if ($type == "normal") {
                $result = UsersGroup::where('company_id', $companyId)->get();

                // Add users_count to each group
                $result->transform(function ($group) {
                    $userIds = $group->users ? json_decode($group->users, true) : [];
                    $group->users_count = is_array($userIds) ? count($userIds) : 0;
                    return $group;
                });

                if ($result->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => __('No groups found!'),
                    ], 404);
                }
                return response()->json([
                    'success' => true,
                    'message' => __('Groups fetched successfully!'),
                    'data' => $result
                ]);
            }
            if ($type == "bluecollar") {
                $result = BlueCollarGroup::where('company_id', $companyId)
                    ->whereHas('bluecollarusers')
                    ->withCount('bluecollarusers')
                    ->get();

                // Rename bluecollarusers_count to users_count for consistency
                $result->transform(function ($group) {
                    $group->users_count = $group->bluecollarusers_count ?? 0;
                    unset($group->bluecollarusers_count);
                    return $group;
                });

                return response()->json([
                    'success' => true,
                    'message' => __('Groups fetched successfully!'),
                    'data' => $result
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => __('Invalid employee type!'),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function newTemplate(Request $request)
    {
        try {
            //xss check start

            $input = $request->all();
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Invalid input detected.'),
                    ], 422);
                }
            }
            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);

            //xss check end

            $validated = $request->validate([
                'template_name' => 'required|string|max:255',
                'template_body' => ['required', 'string', 'max:5000', function ($attribute, $value, $fail) {
                    $count = substr_count($value, '{{var}}');
                    if ($count !== 3) {
                        $fail(__('The Template Body must contain exactly 3 instances of {{var}}.'));
                    }
                }],
            ]);

            $company_id = Auth::user()->company_id;
            $partner_id = Auth::user()->partner_id;

            // Validation has already been performed at this point
            // $validated = $request->validated();

            // Store the template
            $template = new WhatsappTempRequest();
            $template->template_name = $validated['template_name'];
            $template->template_body = $validated['template_body'];
            $template->company_id = $company_id;
            $template->partner_id = $partner_id;
            $template->created_at = now();
            $template->save();

            log_action('New WhatsApp templete requested');

            return response()->json([
                'success' => true,
                'message' => __('Template requested successfully!'),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->validator->errors()->first(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function requestNewTemplate(Request $request)
    {


        try {

            // Validate the incoming request data
            $validated = $request->validate([
                'name' => 'required|string|max:512',
                'category' => 'required|in:MARKETING,UTILITY,AUTHENTICATION',
                'components' => 'required|array',
                'language' => 'required|string|max:20',
            ]);

            $companyId = Auth::user()->company_id;

            $whatsappConfig = CompanyWhatsappConfig::where('company_id', $companyId)->first();
            if (!$whatsappConfig) {
                return response()->json([
                    'success' => false,
                    'message' => __('WhatsApp configuration not found for this company.'),
                ], 422);
            }

            // WhatsApp Cloud API endpoint and credentials
            $accessToken = $whatsappConfig->access_token;
            $wabaId = $whatsappConfig->business_id;
            $apiUrl = "https://graph.facebook.com/v20.0/{$wabaId}/message_templates";

            // Prepare the template data
            $templateData = [
                'name' => $validated['name'],
                'category' => $validated['category'],
                'components' => $validated['components'],
                'language' => $validated['language'],
            ];

            // Make POST request to WhatsApp Cloud API using Http facade
            $response = Http::withToken($accessToken)
                ->withoutVerifying()
                ->post($apiUrl, $templateData);

            // Check if the request was successful
            if ($response->successful()) {
                $responseData = $response->json();
                $templateId = $responseData['id'] ?? null;

                if ($templateId) {
                    // Store the template ID and other details in the local database
                    RequestWhatsappTemplate::create([
                        'template_id' => $templateId,
                        'name' => $validated['name'],
                        'category' => $validated['category'],
                        'language' => $validated['language'],
                        'status' => 'PENDING', // Initial status, to be updated later
                        'waba_id' => $wabaId,
                        'company_id' => $companyId,
                    ]);

                    return response()->json([
                        'success' => true,
                        'template_id' => $templateId,
                        'data' => $responseData,
                        'message' => 'Template request submitted successfully.',
                    ], 200);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Template ID not returned by WhatsApp API.',
                ], 500);
            }

            // Handle API errors
            return response()->json([
                'success' => false,
                'message' => $response->json()['error']['message'] ?? 'Failed to submit template request.',
            ], $response->status());
        } catch (ValidationException $e) {
            // Handle connection or other unexpected errors
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to WhatsApp Cloud API: ' . $e->validator->errors()->first(),
            ], 500);
        } catch (\Exception $e) {
            // Handle connection or other unexpected errors
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to WhatsApp Cloud API: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function requestedTemplates()
    {
        $companyId = Auth::user()->company_id;

        $templates = RequestWhatsappTemplate::where('company_id', $companyId)->get();

        return response()->json([
            'success' => true,
            'data' => $templates
        ]);
    }

    public function checkTemplateStatus($templateId)
    {

        $companyId = Auth::user()->company_id;

        $whatsappConfig = CompanyWhatsappConfig::where('company_id', $companyId)->first();
        if (!$whatsappConfig) {
            return response()->json([
                'success' => false,
                'message' => __('WhatsApp configuration not found for this company.'),
            ], 422);
        }
        $accessToken = $whatsappConfig->access_token;
        $apiUrl = "https://graph.facebook.com/v20.0/{$templateId}";

        try {
            $response = Http::withToken($accessToken)->withoutVerifying()->get($apiUrl);

            if ($response->successful()) {
                $status = $response->json()['status'] ?? 'UNKNOWN';
                // Update status in the database
                RequestWhatsappTemplate::where('template_id', $templateId)->update(['status' => $status]);

                return response()->json([
                    'success' => true,
                    'template_id' => $templateId,
                    'status' => $status,
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json()['error']['message'] ?? 'Failed to check template status.',
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to WhatsApp Cloud API: ' . $e->getMessage(),
            ], 500);
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

            if (!WaCampaign::where('campaign_id', $campid)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign not found'),
                ], 404);
            }

            $company_id = Auth::user()->company_id;

            WaCampaign::where('campaign_id', $campid)
                ->where('company_id', $company_id)
                ->update([
                    'launch_time' => now(),
                    'status' => 'running'
                ]);

            // Update campaign_live table
            WaLiveCampaign::where('campaign_id', $campid)
                ->where('company_id', $company_id)
                ->update([
                    'sent' => '0',
                    'payload_clicked' => '0',
                    'compromised' => '0',
                    'training_assigned' => '0',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

            log_action('Whatsapp campaign relaunched');
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
    public function deleteTemplate(Request $request)
    {
        try {
            $templateId = $request->template_id;
            $templateName = $request->template_name;

            if (!$templateId) {
                return response()->json([
                    'success' => false,
                    'message' => __('Template ID is required.'),
                ], 422);
            }

            if (!$templateName) {
                return response()->json([
                    'success' => false,
                    'message' => __('Template Name is required.'),
                ], 422);
            }

            $companyId = Auth::user()->company_id;
            $whatsappConfig = CompanyWhatsappConfig::where('company_id', $companyId)->first();
            if (!$whatsappConfig) {
                return response()->json([
                    'success' => false,
                    'message' => __('WhatsApp configuration not found for this company.'),
                ], 422);
            }

            $template = RequestWhatsappTemplate::where('template_id', $templateId)
                ->where('name', $templateName)
                ->where('company_id', $companyId)
                ->first();

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => __('This template was not created on this platform. Please try deleting it directly from Meta.'),
                ], 422);
            }

            $accessToken = $whatsappConfig->access_token;
            $waBusinessId = $whatsappConfig->business_id;

            $apiUrl = "https://graph.facebook.com/v23.0/{$waBusinessId}/message_templates?hsm_id={$templateId}&name={$templateName}";

            // Try to delete from Meta's server
            $response = Http::withToken($accessToken)
                ->withoutVerifying()
                ->delete($apiUrl);

            if ($response->successful()) {
                $template->delete();
                return response()->json([
                    'success' => true,
                    'message' => __('Template deleted successfully.'),
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to delete Template'),
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete template: ' . $e->getMessage(),
            ], 500);
        }
    }
}
