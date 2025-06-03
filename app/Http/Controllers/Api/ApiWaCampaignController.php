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
use App\Models\BlueCollarEmployee;
use App\Models\WhatsappTempRequest;
use App\Http\Controllers\Controller;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\CompanyWhatsappConfig;
use App\Models\CompanyWhatsappTemplate;
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

            $campaigns = WaCampaign::with(['trainingData', 'userGroupData'])
                ->where('company_id', $company_id)
                ->orderByDesc('id')
                ->get();
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
                'training_assignment' => 'nullable|in:all,random',
                'days_until_due' => 'nullable|integer|min:1',
                'training_lang' => 'nullable|string|size:2',
                'training_type' => 'nullable',
                'template_name' => 'required|string|max:255',
                'users_group' => 'required|string|max:255',
                'schedule_type' => 'required|in:immediately,scheduled',
                'launch_time' => 'nullable|date',
                'variables' => 'required|array'
            ]);

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
                'training_module' => $validated['campaign_type'] == 'phishing' ? null : json_encode($validated['training_module']),
                'training_assignment' => $validated['campaign_type'] == 'phishing' ? null : $validated['training_assignment'],
                'days_until_due' => $validated['campaign_type'] == 'phishing' ? null : $validated['days_until_due'],
                'training_lang' => $validated['campaign_type'] == 'phishing' ? null : $validated['training_lang'],
                'training_type' => $validated['campaign_type'] == 'phishing' ? null : $validated['training_type'],
                'template_name' => $validated['template_name'],
                'users_group' => $validated['users_group'],
                'schedule_type' => $validated['schedule_type'],
                'launch_time' => now(),
                'status' => 'running',
                'variables' => json_encode($validated['variables']),
                'company_id' => Auth::user()->company_id,
            ]);

            if ($validated['employee_type'] == 'normal') {
                $userIdsJson = UsersGroup::where('group_id', $validated['users_group'])->value('users');
                $userIds = json_decode($userIdsJson, true);
                $users = Users::whereIn('id', $userIds)->get();
            }

            if ($validated['employee_type'] == 'bluecollar') {

                $users = BlueCollarEmployee::where('group_id', $validated['users_group'])->get();
            }


            if ($users->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => __('No employees available in this group')
                ], 422);
            }

            foreach ($users as $user) {

                WaLiveCampaign::create([
                    'campaign_id' => $campaign_id,
                    'campaign_name' => $validated['campaign_name'],
                    'campaign_type' => $validated['campaign_type'],
                    'employee_type' => $validated['employee_type'],
                    'user_name' => $user->user_name,
                    'user_id' => $user->id,
                    'user_email' => $user->user_email ?? null,
                    'user_phone' => $user->whatsapp,
                    'employee_type' => $validated['employee_type'],
                    'employee_type' => $validated['employee_type'],
                    'employee_type' => $validated['employee_type'],
                    'phishing_website' => $validated['phishing_website'],
                    'training_module' => ($validated['campaign_type'] == 'phishing') ? null : $validated['training_module'][array_rand($validated['training_module'])],
                    'training_assignment' => $validated['campaign_type'] == 'phishing' ? null : $validated['training_assignment'],

                    'days_until_due' => $validated['campaign_type'] == 'phishing' ? null : $validated['days_until_due'],
                    'training_lang' => $validated['campaign_type'] == 'phishing' ? null : $validated['training_lang'],
                    'training_type' => $validated['campaign_type'] == 'phishing' ? null : $validated['training_type'],
                    'template_name' => $validated['template_name'],
                    'variables' => json_encode($validated['variables']),
                    'company_id' => Auth::user()->company_id,
                ]);
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
                'training_module' => $validated['campaign_type'] == 'phishing' ? null : json_encode($validated['training_module']),
                'training_assignment' => $validated['campaign_type'] == 'phishing' ? null : $validated['training_assignment'],
                'days_until_due' => $validated['campaign_type'] == 'phishing' ? null : $validated['days_until_due'],
                'training_lang' => $validated['campaign_type'] == 'phishing' ? null : $validated['training_lang'],
                'training_type' => $validated['campaign_type'] == 'phishing' ? null : $validated['training_type'],
                'template_name' => $validated['template_name'],
                'users_group' => $validated['users_group'],
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

            $campaigns = WaLiveCampaign::with(['whatsTrainingData', 'whatsTrainingData.trainingData'])
                ->where('campaign_id', $campaign_id)
                ->where('company_id', Auth::user()->company_id)
                ->get();

            if ($campaigns->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign not found!'),
                ], 404);
            }

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
                return response()->json([
                    'success' => false,
                    'message' => __('Configuration not found!'),
                ], 422);
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
                $result = BlueCollarGroup::where('company_id', $companyId)->get();
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
}
