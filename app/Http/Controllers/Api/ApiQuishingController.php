<?php

namespace App\Http\Controllers\Api;

use App\Models\Users;
use App\Models\UsersGroup;
use App\Models\QshTemplate;
use Illuminate\Support\Str;
use App\Models\QuishingCamp;
use Illuminate\Http\Request;
use App\Models\TrainingModule;
use App\Models\QuishingActivity;
use App\Models\QuishingLiveCamp;
use App\Http\Controllers\Controller;
use App\Models\ScormAssignedUser;
use App\Models\TrainingAssignedUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ApiQuishingController extends Controller
{
    public function index(Request $request)
    {
        try {
            $company_id = Auth::user()->company_id;
            $campaigns = QuishingCamp::with('userGroupData')
                ->where(function ($query) use ($company_id) {
                    $query->where('company_id', $company_id)
                        ->orWhere('company_id', 'default');
                })
                ->orderBy('id', 'desc')
                ->get();

            $campLive = QuishingLiveCamp::where('company_id', $company_id)
                ->get();
            $qshTemplate = QshTemplate::where('company_id', $company_id)
                ->orWhere('company_id', 'default')
                ->get();

            return response()->json([
                'success' => true,
                'message' => __('Quishing campaign data retrieved successfully'),
                'data' => [
                    'campaigns' => $campaigns,
                    'qshTemplate' => $qshTemplate,
                    'total_sent' => $campLive->where('sent', '1')->count(),
                    'total_opened' => $campLive->where('mail_open', '1')->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred while retrieving data'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createCampaign(Request $request)
    {
        try {
            // XSS attack prevention
            $campData = $request->except(['quishing_materials', 'training_modules', 'scorm_training', 'selected_users', 'policies']);
            foreach ($campData as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Invalid input detected'),
                    ], 422);
                }
            }

            $validated = $request->validate([
                'campaign_name' => 'required|string|max:255',
                'campaign_type' => 'required|in:quishing-training,quishing',
                "employee_group" => 'required|string',
                "training_modules" => 'nullable|array',
                "scorm_training" => 'nullable|array',
                'training_assignment' => 'nullable|in:all,random',
                'days_until_due' => 'nullable|integer|min:1',
                'training_language' => 'nullable|string|size:2',
                'training_type' => 'nullable',
                'training_on_click' => 'required|string|in:true,false',
                'compromise_on_click' => 'required|string|in:true,false',
                "quishing_materials" => 'required|array',
                "quishing_language" => 'nullable|string',
                "sender_profile" => 'nullable|string',
                'selected_users' => 'nullable|string',
                'policies' => 'nullable|array',
                'schedule_type' => 'required|in:immediately,scheduled',
                "schedule_date" => 'nullable|date|after_or_equal:today',
                "time_zone" => 'nullable|string',
                'start_time' => [
                    'nullable',
                    'date_format:Y-m-d H:i:s',
                    function ($attribute, $value, $fail) {
                        $inputDate = Carbon::parse($value)->startOfDay();
                        $today = Carbon::today();

                        if ($inputDate->lt($today)) {
                            $fail('The ' . $attribute . ' must not be a past date.');
                        }
                    },
                ],
                'end_time'   => 'nullable|date_format:Y-m-d H:i:s|after:start_time'
            ]);

            $campaign_id = Str::random(6);

            $validated = $request->all();
            $launchType = $request->schedule_type;
            $companyId = Auth::user()->company_id;

            if ($launchType === 'immediately') {
                log_action("Quishing campaign created");
                return $this->handleImmediateLaunch($validated, $campaign_id, $companyId);
            }

            if ($launchType === 'scheduled') {
                log_action("Quishing campaign scheduled");
                return $this->handleScheduledLaunch($validated, $campaign_id, $companyId);
            }

            log_action("Quishing campaign launched with invalid launch type");
            return response()->json([
                'success' => false,
                'message' => __('Invalid launch type')
            ], 422);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->validator->errors()->first(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Campaign creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    private function handleImmediateLaunch($data, $campId, $companyId)
    {
        $userIdsJson = UsersGroup::where('group_id', $data['employee_group'])->value('users');
        $userIds = json_decode($userIdsJson, true);
        if ($data['selected_users'] ==  'null') {
            $users = Users::whereIn('id', $userIds)->get();
        } else {
            $users = Users::whereIn('id', json_decode($data['selected_users'], true))->get();
        }

        if (!$users || $users->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => __('No users found in the selected group'),
            ], 422);
        }

        foreach ($users as $user) {
            $camp_live = QuishingLiveCamp::create([
                'campaign_id'        => $campId,
                'campaign_name'      => $data['campaign_name'],
                'user_id'            => $user->id,
                'user_name'          => $user->user_name,
                'user_email'         => $user->user_email,

                'training_module'    => $data['campaign_type'] === 'quishing' || empty($data['training_modules'])
                    ? null
                    : $data['training_modules'][array_rand($data['training_modules'])],

                'scorm_training'    => $data['campaign_type'] === 'quishing' || empty($data['scorm_training'])
                    ? null
                    : $data['scorm_training'][array_rand($data['scorm_training'])],

                'days_until_due'     => $data['campaign_type'] === 'quishing' ? null : $data['days_until_due'],
                'training_lang'      => $data['campaign_type'] === 'quishing' ? null : $data['training_language'],
                'training_type'      => $data['campaign_type'] === 'quishing' ? null : $data['training_type'],

                'quishing_material'  => $data['quishing_materials'][array_rand($data['quishing_materials'])],
                'sender_profile'     => $data['sender_profile'] ?? null,

                'quishing_lang'      => $data['quishing_language'] ?? null,
                'company_id'         => Auth::user()->company_id,
            ]);
            QuishingActivity::create([
                'campaign_id' => $camp_live->campaign_id,
                'campaign_live_id' => $camp_live->id,
                'company_id' => Auth::user()->company_id,
            ]);

            // Audit log
            audit_log(
                Auth::user()->company_id,
                $user->user_email,
                null,
                'QUISHING_CAMPAIGN_SIMULATED',
                "The campaign ‘{$data['campaign_name']}’ has been sent to {$user->user_email}",
                'normal'
            );
        }

        QuishingCamp::create([
            'campaign_id'        => $campId,
            'campaign_name'      => $data['campaign_name'],
            'campaign_type'      => $data['campaign_type'],
            'users_group'        => $data['employee_group'],
            'selected_users'     => $data['selected_users'] != 'null' ? $data['selected_users'] : null,
            'training_module'    => $data['campaign_type'] === 'quishing' || empty($data['training_modules']) ? null : json_encode($data['training_modules']),
            'scorm_training'    => $data['campaign_type'] === 'quishing' || empty($data['scorm_training']) ? null : json_encode($data['scorm_training']),
            'training_assignment' => $data['campaign_type'] === 'quishing' ? null : $data['training_assignment'],
            'days_until_due'     => $data['campaign_type'] === 'quishing' ? null : $data['days_until_due'],
            'training_lang'      => $data['campaign_type'] === 'quishing' ? null : $data['training_language'],
            'training_type'      => $data['campaign_type'] === 'quishing' ? null : $data['training_type'],
            'policies' => (is_array($data['policies']) && !empty($data['policies'])) ? json_encode($data['policies']) : null,
            'training_on_click'  => $data['training_on_click'] == 'false' ? 0 : 1,
            'compromise_on_click'  => $data['compromise_on_click'] == 'false' ? 0 : 1,
            'quishing_material'  => !empty($data['quishing_materials']) ? json_encode($data['quishing_materials']) : null,
            'sender_profile'   =>  $data['sender_profile'] ?? null,
            'quishing_lang'      => $data['quishing_language'] ?? null,
            'status'             => 'running',
            'company_id'         => $companyId,
            'schedule_type'      => $data['schedule_type'],
            'schedule_date'      => $data['schedule_date'],
            'time_zone'      => $data['time_zone'],
            'start_time'      => $data['start_time'],
            'end_time'      => $data['end_time'],
        ]);

        log_action('Quishing campaign created');

        return response()->json([
            'success' => true,
            'message' => __('Campaign created and running!')
        ]);
    }

    private function handleScheduledLaunch($data, $campId, $companyId)
    {
        QuishingCamp::create([
            'campaign_id'        => $campId,
            'campaign_name'      => $data['campaign_name'],
            'campaign_type'      => $data['campaign_type'],
            'users_group'        => $data['employee_group'],
            'selected_users'     => $data['selected_users'] != 'null' ? $data['selected_users'] : null,
            'training_module'    => $data['campaign_type'] === 'quishing' || empty($data['training_modules']) ? null : json_encode($data['training_modules']),
            'scorm_training'    => $data['campaign_type'] === 'quishing' || empty($data['scorm_training']) ? null : json_encode($data['scorm_training']),
            'training_assignment' => $data['campaign_type'] === 'quishing' ? null : $data['training_assignment'],
            'days_until_due'     => $data['campaign_type'] === 'quishing' ? null : $data['days_until_due'],
            'training_lang'      => $data['campaign_type'] === 'quishing' ? null : $data['training_language'],
            'training_type'      => $data['campaign_type'] === 'quishing' ? null : $data['training_type'],
            'policies' => (is_array($data['policies']) && !empty($data['policies'])) ? json_encode($data['policies']) : null,
            'training_on_click'  => $data['training_on_click'] == 'false' ? 0 : 1,
            'compromise_on_click'  => $data['compromise_on_click'] == 'false' ? 0 : 1,
            'quishing_material'  => !empty($data['quishing_materials']) ? json_encode($data['quishing_materials']) : null,
            'sender_profile'   =>  $data['sender_profile'] ?? null,
            'quishing_lang'      => $data['quishing_language'] ?? null,
            'status'             => 'pending',
            'company_id'         => $companyId,
            'schedule_type'      => $data['schedule_type'],
            'schedule_date'      => $data['schedule_date'],
            'time_zone'      => $data['time_zone'],
            'start_time'      => $data['start_time'],
            'end_time'      => $data['end_time'],
        ]);



        log_action('Quishing campaign scheduled');

        return response()->json([
            'success' => true,
            'message' => __('Campaign created and scheduled!')
        ]);
    }

    public function deleteCampaign(Request $request)
    {
        try {
            $campaign_id = $request->route('campaign_id');
            if (!$campaign_id) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required'),
                ], 422);
            }
            $campaign = QuishingCamp::where('campaign_id', $campaign_id)->first();
            $campaign_name = $campaign->campaign_name;
            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign not found'),
                ], 404);
            }

            if ($request->deleteTrainingsAlso == 1) {
                TrainingAssignedUser::where('campaign_id', $campaign_id)->delete();
            }

            $campaign->delete();
            QuishingLiveCamp::where('campaign_id', $campaign_id)->delete();
            QuishingActivity::where('campaign_id', $campaign_id)->delete();

            log_action("Quishing Campaign deleted : {$campaign_name}");

            return response()->json([
                'success' => true,
                'message' => __('Campaign deleted successfully'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function campaignDetail(Request $request)
    {
        try {
            $campaign_id = $request->route('campaign_id');
            if (!$campaign_id) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required'),
                ], 422);
            }
            $campaign = QuishingCamp::with('campLive', 'campLive.campaignActivity', 'emailReplies')->where('campaign_id', $campaign_id)->where('company_id', Auth::user()->company_id)->first();
            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign not found'),
                ], 404);
            }
            $trainingModules = $campaign->trainingModules()->get();
            $quishingMaterials = $campaign->quishingMaterials()->get();
            $campaign->training_modules_data = $trainingModules;
            $campaign->quishing_materials_data = $quishingMaterials;

            $trainingAssigned = TrainingAssignedUser::with('trainingData')->where('campaign_id', $campaign_id)
                ->where('company_id', Auth::user()->company_id)
                ->get();
            $scormAssigned = ScormAssignedUser::with('scormTrainingData')->where('campaign_id', $campaign_id)
                ->where('company_id', Auth::user()->company_id)
                ->get();
            $campaign->trainingAssigned = $trainingAssigned;
            $campaign->scormAssigned = $scormAssigned;

            return response()->json([
                'success' => true,
                'message' => __('Campaign details retrieved successfully'),
                'data' => $campaign
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
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

            if (!QuishingCamp::where('campaign_id', $campid)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign not found'),
                ], 404);
            }

            $company_id = Auth::user()->company_id;

            QuishingCamp::where('campaign_id', $campid)
                ->where('company_id', $company_id)
                ->update([
                    'created_at' => now(),
                    'status' => 'running'
                ]);

            // Update campaign_live table
            QuishingLiveCamp::where('campaign_id', $campid)
                ->where('company_id', $company_id)
                ->update([
                    'sent' => '0',
                    'mail_open' => '0',
                    'qr_scanned' => '0',
                    'compromised' => '0',
                    'email_reported' => '0',
                    'training_assigned' => '0',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

            log_action('Quishing campaign relaunched');
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
}
