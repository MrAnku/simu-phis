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
            $campData = $request->except(['quishing_materials', 'training_modules', 'scorm_training']);
            foreach ($campData as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Invalid input detected'),
                    ], 422);
                }
            }

            // Decode JSON arrays from JS frontend
            // $trainingModules = json_decode($request->training_modules, true);
            // $quishingMaterials = json_decode($request->quishing_materials, true);
            $trainingModules = $request->training_modules;
            $scormTrainings = $request->scorm_training;
            $quishingMaterials = $request->quishing_materials;

            $userIdsJson = UsersGroup::where('group_id', $request->employee_group)->value('users');
            $userIds = json_decode($userIdsJson, true);
            $users = Users::whereIn('id', $userIds)->get();

            if (!$users || $users->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => __('No users found in the selected group'),
                ], 422);
            }

            $campaign_id = Str::random(6);

            QuishingCamp::create([
                'campaign_id'        => $campaign_id,
                'campaign_name'      => $request->campaign_name,
                'campaign_type'      => $request->campaign_type,
                'users_group'        => $request->employee_group,

                'training_module'    => $request->campaign_type === 'quishing' || empty($trainingModules) ? null : json_encode($trainingModules),
                'scorm_training'    => $request->campaign_type === 'quishing' || empty($scormTrainings) ? null : json_encode($scormTrainings),
                'training_assignment' => $request->campaign_type === 'quishing' ? null : $request->training_assignment,
                'days_until_due'     => $request->campaign_type === 'quishing' ? null : $request->days_until_due,
                'training_lang'      => $request->campaign_type === 'quishing' ? null : $request->training_language,
                'training_type'      => $request->campaign_type === 'quishing' ? null : $request->training_type,
                'training_on_click'  => $request->training_on_click == 'false' ? 0 : 1,
                'compromise_on_click'  => $request->compromise_on_click == 'false' ? 0 : 1,
                'quishing_material'  => !empty($quishingMaterials) ? json_encode($quishingMaterials) : null,
                'sender_profile'   => $request->sender_profile ?? null,
                'quishing_lang'      => $request->quishing_language ?? null,
                'status'             => 'running',
                'company_id'         => Auth::user()->company_id,
            ]);

            foreach ($users as $user) {
                $camp_live = QuishingLiveCamp::create([
                    'campaign_id'        => $campaign_id,
                    'campaign_name'      => $request->campaign_name,
                    'user_id'            => $user->id,
                    'user_name'          => $user->user_name,
                    'user_email'         => $user->user_email,

                    'training_module'    => $request->campaign_type === 'quishing' || empty($trainingModules)
                        ? null
                        : $trainingModules[array_rand($trainingModules)],

                    'scorm_training'    => $request->campaign_type === 'quishing' || empty($scormTrainings)
                        ? null
                        : $scormTrainings[array_rand($scormTrainings)],

                    'days_until_due'     => $request->campaign_type === 'quishing' ? null : $request->days_until_due,
                    'training_lang'      => $request->campaign_type === 'quishing' ? null : $request->training_language,
                    'training_type'      => $request->campaign_type === 'quishing' ? null : $request->training_type,

                    'quishing_material'  => $quishingMaterials[array_rand($quishingMaterials)],
                    'sender_profile'     => $request->sender_profile ?? null,

                    'quishing_lang'      => $request->quishing_language ?? null,
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
                    "The campaign ‘{$request->campaign_name}’ has been sent to {$user->user_email}",
                    'normal'
                );
            }

            log_action("Quishing Campaign Created : {$request->campaign_name}");

            return response()->json([
                'success' => true,
                'message' => __('Quishing campaign created successfully'),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->validator->errors()->first(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Campaign creation failed: ' . $e->getMessage()); // 🔍 For debugging
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
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
