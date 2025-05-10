<?php

namespace App\Http\Controllers\Api;

use App\Models\Users;
use App\Models\UsersGroup;
use Illuminate\Http\Request;
use App\Models\TrainingModule;
use App\Models\BlueCollarGroup;
use App\Models\WhatsappCampaign;
use App\Models\BlueCollarEmployee;
use Illuminate\Support\Facades\DB;
use App\Models\WhatsappTempRequest;
use App\Http\Controllers\Controller;
use App\Models\WhatsAppCampaignUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\CompanyWhatsappConfig;
use App\Models\CompanyWhatsappTemplate;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\StoreWhatsAppTemplateRequest;

class ApiWhatsappCampaignController extends Controller
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

            $campaigns = WhatsappCampaign::with('trainingData')->where('company_id', $company_id)->orderBy('id', 'desc')
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

    public function createCampaign(Request $request)
    {
        try {
            //xss check start
            $request->validate([
                'campaign_name' => 'required',
                'template_name' => 'required',
                'training' => 'nullable',
                'training_type' => 'nullable',
                'template_language' => 'nullable',
                'components' => 'nullable',
                'employee_group' => 'required',
                'campaign_type' => 'required',
                'employee_type' => 'required',
            ]);

            $input = $request->only(
                'campaign_name',
                'template_name',
                'training',
                'training_type',
                'template_language',
                'employee_group',
                'campaign_type',
                'employee_type'
            );
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

            //checking if the selected group is a valid group
            if ($request->employee_type == "Normal") {
                $userGroup = UsersGroup::where('group_id', $request->employee_group)->first();
                if (!$userGroup) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Invalid user group selected!'),
                    ], 422);
                }

                if (!$userGroup->users) {
                    return response()->json([
                        'success' => false,
                        'message' => __('No users found in the selected group!'),
                    ], 422);
                }
            } else {
                $userGroup = BlueCollarGroup::where('group_id', $request->employee_group)->first();
                if (!$userGroup) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Invalid user group selected!'),
                    ], 422);
                }
            }

            $company_id = Auth::user()->company_id;

            $new_campaign = new WhatsappCampaign();

            $new_campaign->camp_id = generateRandom(6);
            $camp_id = $new_campaign->camp_id;
            $new_campaign->camp_name = $request->campaign_name;
            $new_campaign->template_name = $request->template_name;
            $new_campaign->user_group = $request->employee_group;
            if ($request->employee_type == "Normal") {

                $new_campaign->user_group_name = $this->userGroupName($request->employee_group);
            } else {
                $new_campaign->user_group_name = $this->BlueCollarGroupName($request->employee_group);
            }
            $new_campaign->camp_type = $request->campaign_type;
            $new_campaign->employee_type = $request->employee_type;

            if ($request->campaign_type == "Phishing and Training") {
                $new_campaign->training = $request->training;
                $new_campaign->training_type = $request->training_type;
            }
            $new_campaign->company_id = $company_id;
            $new_campaign->created_at = now();
            $new_campaign->save();

            $this->createCampaignIndividual($camp_id, $request);

            log_action('WhatsApp campaign created');

            return response()->json([
                'success' => true,
                'message' => __('Campaign created successfully!'),
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

    public function createCampaignIndividual($camp_id, $campaignData)
    {
        $users = [];

        if ($campaignData->employee_type == "Normal") {

            $userIdsJson = UsersGroup::where('group_id', $campaignData->employee_group)->value('users');
            $userIds = json_decode($userIdsJson, true);
            $users = Users::whereIn('id', $userIds)->get();

            // $users = Users::where('group_id', $campaignData->user_group)->get();
        } else {
            $users = BlueCollarEmployee::where('group_id', $campaignData->employee_group)->get();
        }

        $company_id = Auth::user()->company_id;

        $training = ($campaignData->campaign_type == "Phishing and Training") ? $campaignData->training : null;

        foreach ($users as $user) {

            if ($user->whatsapp == null) {
                continue;
            }
            DB::table('whatsapp_camp_users')->insert([
                'camp_id' => $camp_id,
                'camp_name' => $campaignData->campaign_name,
                'user_group' => $campaignData->employee_group,
                'user_name' => $user->user_name,
                'user_id' => $user->id,
                'user_email' => $user->user_email ?? null,
                'employee_type' => $campaignData->employee_type,
                'user_whatsapp' => $user->whatsapp,
                'template_name' => $campaignData->template_name,
                'template_language' => $campaignData->template_language,
                'training' => $training,
                'training_type' => $campaignData->training_type ?? null,
                'components' => json_encode($campaignData->components ?? []),
                'status' => 'pending',
                'created_at' => now(),
                'company_id' => $company_id,
            ]);
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

    private function BlueCollarGroupName($groupid)
    {
        $userGroup = BlueCollarGroup::where('group_id', $groupid)->first();
        if ($userGroup) {
            return $userGroup->group_name;
        } else {
            return null;
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

            $campaign = WhatsappCampaign::where('camp_id', $campaign_id)->first();

            if (!$campaign) {
                log_action('Something went wrong while deleting WhatsApp campaign');
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign not found!'),
                ], 404);
            }

            $campaign->delete();
            DB::table('whatsapp_camp_users')->where('camp_id', $campaign_id)->delete();

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

            $campaigns = WhatsAppCampaignUser::where('camp_id', $campaign_id)->get();

            if (!$campaigns) {
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
