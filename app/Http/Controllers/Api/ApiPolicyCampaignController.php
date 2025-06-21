<?php

namespace App\Http\Controllers\Api;

use App\Models\Users;
use App\Models\UsersGroup;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PolicyCampaign;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ApiPolicyCampaignController extends Controller
{
    public function create(Request $request)
    {
        try {
            $request->validate([
                'campaign_name' => 'required|string|max:255',
                'users_group' => 'required|string',
                'policy' => 'required|exists:policies,id',
                'scheduled_at' => 'required|string',
            ]);
            // check if scheduled_at is less than current time then the status will be running else pending
            $scheduledAt = strtotime($request->scheduled_at);
            $currentTime = time();
            $status = $scheduledAt < $currentTime ? 'running' : 'pending';



            $campaign = PolicyCampaign::create([
                'campaign_name' => $request->campaign_name,
                'campaign_id' => Str::random(6),
                'users_group' => $request->users_group,
                'policy' => $request->policy,
                'status' => $status,
                'scheduled_at' => $request->scheduled_at,
                'company_id' => Auth::user()->company_id,
            ]);
            //send the employees to live table if status is running
            if ($status === 'running') {
                // Retrieve the users in the specified group
                $userIdsJson = UsersGroup::where('group_id', $campaign->users_group)
                    ->where('company_id', Auth::user()->company_id)
                    ->value('users');

                $userIds = json_decode($userIdsJson, true);
                $users = Users::whereIn('id', $userIds)->get();

                // $users = Users::where('group_id', $campaign->users_group)->get();

                // Check if users exist in the group
                if ($users->isEmpty()) {
                    return response()->json(['success' => false, 'message' => __('No employees available in this group')], 404);
                }

                // Iterate through the users and create CampaignLive entries
                foreach ($users as $user) {
                    $campaign->campLive()->create([
                        'campaign_id' => $campaign->campaign_id,
                        'campaign_name' => $campaign->campaign_name,
                        'user_name' => $user->user_name,
                        'user_email' => $user->user_email,
                        'sent' => 0,
                        'policy' => $campaign->policy,
                        'company_id' => Auth::user()->company_id,
                    ]);
                }
            }
            // log_action("Policy campaign created for company: " . Auth::user()->company_id);

            return response()->json([
                'success' => true,
                'message' => 'Policy campaign created successfully',
                'data' => $campaign
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function detail(Request $request)
    {
        try {
            $campaigns = PolicyCampaign::with(['campLive', 'assignedPolicies', 'policyDetail', 'groupDetail'])
                ->where('company_id', Auth::user()->company_id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $campaigns
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
