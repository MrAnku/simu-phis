<?php

namespace App\Http\Controllers\Api;

use App\Models\Users;
use App\Models\UsersGroup;
use App\Models\WaCampaign;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\WaLiveCampaign;
use App\Models\BlueCollarEmployee;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ApiWaCampaignController extends Controller
{
    public function createCampaign(Request $request)
    {
        try {
            $validated = $request->validate([
                'campaign_name' => 'required|string|max:255',
                'campaign_type' => 'required|in:phishing_and_training,phishing',
                'employee_type' => 'required|in:normal,bluecollar',
                'phishing_website' => 'required|integer',
                'training_module' => 'nullable|integer',
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
                'training_module' => $validated['training_module'] ?? null,
                'training_assignment' => $validated['training_assignment'] ?? null,
                'days_until_due' => $validated['days_until_due'] ?? null,
                'training_lang' => $validated['training_lang'] ?? null,
                'training_type' => $validated['training_type'] ?? null,
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
                    'training_module' => $validated['training_module'] ?? null,
                    'training_assignment' => $validated['training_assignment'] ?? null,
                    'days_until_due' => $validated['days_until_due'] ?? null,
                    'training_lang' => $validated['training_lang'] ?? null,
                    'training_type' => $validated['training_type'] ?? null,
                    'template_name' => $validated['template_name'],
                    'variables' => json_encode($validated['variables']),
                    'company_id' => Auth::user()->company_id,
                ]);
            }

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
                'training_module' => $validated['training_module'] ?? null,
                'training_assignment' => $validated['training_assignment'] ?? null,
                'days_until_due' => $validated['days_until_due'] ?? null,
                'training_lang' => $validated['training_lang'] ?? null,
                'training_type' => $validated['training_type'] ?? null,
                'template_name' => $validated['template_name'],
                'users_group' => $validated['users_group'],
                'schedule_type' => $validated['schedule_type'],
                'launch_time' => $validated['launch_time'],
                'status' => 'pending',
                'variables' => json_encode($validated['variables']),
                'company_id' => Auth::user()->company_id,
            ]);

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
}
