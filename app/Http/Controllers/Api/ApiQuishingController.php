<?php

namespace App\Http\Controllers\Api;

use App\Models\Users;
use App\Models\UsersGroup;
use App\Models\QshTemplate;
use Illuminate\Support\Str;
use App\Models\QuishingCamp;
use Illuminate\Http\Request;
use App\Models\QuishingActivity;
use App\Models\QuishingLiveCamp;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ScormAssignedUser;
use App\Models\TrainingAssignedUser;
use App\Models\TrainingGame;
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
            $ipWhitelist = Company::where('company_id', $company_id)->value('ip_whitelist');

            return response()->json([
                'success' => true,
                'message' => __('Quishing campaign data retrieved successfully'),
                'data' => [
                    'campaigns' => $campaigns,
                    'qshTemplate' => $qshTemplate,
                    'total_sent' => $campLive->where('sent', '1')->count(),
                    'total_opened' => $campLive->where('mail_open', '1')->count(),
                    'ipWhitelist' => $ipWhitelist
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
                'campaign_name' => 'required|string|min:5|max:255',
                'campaign_type' => 'required|in:quishing-training,quishing',
                "employee_group" => 'required|string',
                "training_modules" => 'nullable|array',
                "scorm_training" => 'nullable|array',
                'training_assignment' => 'nullable|in:all,random',
                'days_until_due' => 'nullable|integer|min:1',
                'training_language' => 'nullable|string|size:2',
                'training_type' =>  'nullable|in:static_training,ai_training,games,gamified,conversational_training',
                'training_on_click' => 'required|string|in:true,false',
                'compromise_on_click' => 'required|string|in:true,false',
                "quishing_materials" => 'required|array',
                "quishing_language" => 'nullable|string',
                "sender_profile" => 'nullable|string',
                'selected_users' => 'nullable|string',
                'policies' => 'nullable|array',
                "email_freq" => 'required|in:once,weekly,monthly,quarterly',
                'expire_after' => 'required_if:email_freq,weekly,monthly,quarterly|nullable|date|after_or_equal:tomorrow',
                'schedule_type' => 'required|in:immediately,scheduled,schLater',
                "schedule_date" => "exclude_unless:schedule_type,scheduled|required|date|after_or_equal:today",
                "time_zone" => 'nullable|string|required_if:schedule_type,scheduled',
                'start_time' => [
                    'exclude_unless:schedule_type,scheduled',
                    'required',
                    'date_format:Y-m-d H:i:s',
                    function ($attribute, $value, $fail) {
                        $inputDate = Carbon::parse($value)->startOfDay();
                        $today = Carbon::today();

                        if ($inputDate->lt($today)) {
                            $fail('The ' . $attribute . ' must not be a past date.');
                        }
                    },
                ],
                'end_time'   => 'exclude_unless:schedule_type,scheduled|required|date_format:Y-m-d H:i:s|after:start_time'
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

            if ($launchType === 'schLater') {
                log_action("Quishing campaign saved for scheduling later");
                return $this->handleLaterLaunch($validated, $campaign_id, $companyId);
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
            'launch_date' => now(),
            'email_freq' => $data['email_freq'],
            'expire_after' => $data['expire_after'] ?? null,
        ]);

        $isLive = $this->makeCampaignLive($campId, $users);

        if ($isLive['status'] === 0) {
            return response()->json([
                'success' => false,
                'message' => $isLive['msg']
            ], 422);
        }

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
            'launch_date' => $data['schedule_date'],
            'email_freq' => $data['email_freq'],
            'expire_after' => $data['expire_after'] ?? null,
        ]);



        log_action('Quishing campaign scheduled');

        return response()->json([
            'success' => true,
            'message' => __('Campaign created and scheduled!')
        ]);
    }

    private function handleLaterLaunch($data, $campId, $companyId)
    {
        QuishingCamp::create([
            'campaign_id' => $campId,
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
            'status' => 'not_scheduled',
            'company_id'         => $companyId,
            'schedule_type'      => 'schLater'
        ]);

        log_action('Quishing campaign created for schedule later');

        return response()->json([
            'success' => true,
            'message' => __('Campaign saved successfully!')
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

            $trainingModules = collect(); // default empty collection

            if ($campaign->training_type === 'games') {
                $gameIds = json_decode($campaign->training_module, true);

                if (is_array($gameIds) && count($gameIds) > 0) {
                    $trainingModules = TrainingGame::whereIn('id', $gameIds)
                        ->select('id', 'name', 'cover_image')
                        ->get()
                        ->map(function ($game) {
                            return [
                                'name' => $game->name,
                                'video_url' => null,
                                'cover_img' => $game->cover_image,
                            ];
                        });
                }
            } else {
                $trainingModules = $campaign->trainingModules()
                    ->select('name', 'json_quiz', 'cover_image')
                    ->get()
                    ->map(function ($module) use ($campaign) {
                        $videoUrl = null;
                        $coverImg = null;

                        // 💬 Conversational training
                        if ($campaign->training_type === 'conversational_training') {
                            $coverImg = $module->cover_image ?? null;
                        }
                        // 🎥 Other types: extract video URL from json_quiz
                        else {
                            $data = json_decode($module->json_quiz, true);

                            if (isset($data['videoUrl'])) {
                                $videoUrl = $data['videoUrl'];
                            } elseif (is_array($data)) {
                                foreach ($data as $item) {
                                    if (!empty($item['videoUrl'])) {
                                        $videoUrl = $item['videoUrl'];
                                        break;
                                    }
                                }
                            }
                        }

                        return [
                            'name' => $module->name,
                            'video_url' => $videoUrl,
                            'cover_img' => $coverImg,
                        ];
                    });
            }

            $quishingMaterials = $campaign->quishingMaterials()->get();
            $campaign->training_modules_data = $trainingModules;
            $campaign->quishing_materials_data = $quishingMaterials;
            $campaign->scorm_trainings_data = $campaign->scormTrainings()->get();

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

            // Update quishing camp activity table
            QuishingActivity::where('campaign_id', $campid)
                ->where('company_id', $company_id)
                ->update([
                    'email_sent_at' => null,
                    'email_viewed_at' => null,
                    'payload_clicked_at' => null,
                    'compromised_at' => null,
                    'client_details' => null,
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
                "email_freq" => 'required|in:once,weekly,monthly,quarterly',
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
                'expire_after' => 'required_if:email_freq,weekly,monthly,quarterly|nullable|date|after_or_equal:tomorrow',
            ]);

            $companyId = Auth::user()->company_id;

            $campaign = QuishingCamp::where('campaign_id', $campaignId)
                ->where('company_id', $companyId)
                ->first();
            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign not found')
                ], 404);
            }

            $isRescheduled = false;

            if ($request->schedule_type == 'immediately') {
                $email_freq = $request->email_freq;
                $expire_after = $request->expire_after;

                // Retrieve the campaign instance
                $campaign = QuishingCamp::where('campaign_id', $campaignId)->where('company_id', $companyId)->first();

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

                $isLive = $this->makeCampaignLive($campaignId, $users);

                if ($isLive['status'] === 0) {
                    return response()->json([
                        'success' => false,
                        'message' => $isLive['msg']
                    ], 422);
                }

                // Update the campaign status to 'running'
                $campaign->update([
                    'status' => 'running',
                    'launch_date' => Carbon::parse(now())->toDateString(),
                    'schedule_type' => 'immediately',
                    'email_freq' => $email_freq,
                    'expire_after' => $expire_after
                ]);
                $isRescheduled = true;
            }

            if ($request->schedule_type == 'scheduled') {
                $campaign->schedule_type = 'scheduled';
                $campaign->schedule_date = $request->schedule_date;
                $campaign->launch_date = $request->schedule_date;
                $campaign->email_freq = $request->email_freq;
                $campaign->start_time = $request->start_time;
                $campaign->end_time = $request->end_time;
                $campaign->time_zone = $request->time_zone;
                $campaign->expire_after = $request->expire_after;
                $campaign->status = 'pending';
                $campaign->save();

                $isRescheduled = true;
            }

            log_action('Email campaign rescheduled');

            if (!$isRescheduled) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign could not be rescheduled')
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => __('Campaign rescheduled successfully!')
            ], 200);
        } catch (ValidationException $e) {
            log_action('Validation error occured while creating email campaign');
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

    private function makeCampaignLive($campaignId, $users)
    {
        $companyId = Auth::user()->company_id;
        // Retrieve the campaign instance
        $campaign = QuishingCamp::where('campaign_id', $campaignId)->where('company_id', $companyId)->first();

        foreach ($users as $user) {
            $camp_live = QuishingLiveCamp::create([
                'campaign_id'        => $campaignId,
                'campaign_name'      => $campaign->campaign_name,
                'user_id'            => $user->id,
                'user_name'          => $user->user_name,
                'user_email'         => $user->user_email,
                'training_module'    => $campaign->training_module !== null ? json_decode($campaign->training_module)[array_rand(json_decode($campaign->training_module))] : null,
                'scorm_training'    => $campaign->scorm_training !== null ? json_decode($campaign->scorm_training)[array_rand(json_decode($campaign->scorm_training))] : null,
                'days_until_due'     => $campaign->campaign_type === 'quishing' ? null : $campaign->days_until_due,
                'training_lang'      => $campaign->campaign_type === 'quishing' ? null : $campaign->training_lang,
                'training_type'      => $campaign->campaign_type === 'quishing' ? null : $campaign->training_type,
                'quishing_material'  =>  $campaign->quishing_material !== null ? json_decode($campaign->quishing_material)[array_rand(json_decode($campaign->quishing_material))] : null,
                'sender_profile'     => $campaign->sender_profile ?? null,
                'quishing_lang'      => $campaign->quishing_lang ?? null,
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
                "The campaign ‘{$campaign->campaign_name}’ has been sent to {$user->user_email}",
                'normal'
            );
        }
        log_action("Quishing Campaign running");
        return ['status' => 1, 'msg' => __('Campaign is now live')];
    }
}
