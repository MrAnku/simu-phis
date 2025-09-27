<?php

namespace App\Http\Controllers\Api;

use App\Models\Users;
use App\Models\UsersGroup;
use Illuminate\Support\Str;
use App\Models\Inforgraphic;
use Illuminate\Http\Request;
use App\Models\InfoGraphicCampaign;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\InfoGraphicLiveCampaign;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class InforgraphicsController extends Controller
{
    public function index()
    {
        try {
            $infographics = Inforgraphic::where('company_id', Auth::user()->company_id)
                ->orderBy('created_at', 'desc')
                ->get();
            return response()->json([
                'success' => true,
                'data' => $infographics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch infographics'
            ], 500);
        }
    }

    public function saveInfographics(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string|max:2000',
                'category' => 'required|string|max:255',
                'file' => 'required|file|mimes:jpg,jpeg,png|max:6048',
            ]);

            $file = $request->file('file');
            $filePath = $file->storeAs(
                '/uploads/inforgraphics',
                uniqid() . '.' . $file->getClientOriginalExtension(),
                's3'
            );
            Inforgraphic::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'category' => $data['category'],
                'file_path' => "/" . $filePath,
                'company_id' => Auth::user()->company_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Infographic saved successfully'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->validator->errors()->first(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 422);
        }
    }

    public function deleteInfographics(Request $request)
    {
        try {
            $id = $request->route('encodedId');
            if (!$id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Infographic ID is required'
                ], 400);
            }
            $id = base64_decode($id);

            $infographic = Inforgraphic::findOrFail($id);
            if(!$infographic){
                return response()->json([
                    'success' => false,
                    'message' => 'Infographic not found'
                ], 404);
            }

            // $infographic->delete();

            $hasCampaign = InfoGraphicLiveCampaign::where('infographic', $id)
                ->where('company_id', Auth::user()->company_id)
                ->exists();
            if ($hasCampaign) {
                return response()->json([
                    'success' => false,
                    'message' => 'Infographic is being used in a campaign and cannot be deleted. Please delete the associated campaign first.'
                ], 400);
            }

            // Delete the file from S3
            if ($infographic->file_path) {
                Storage::disk('s3')->delete(ltrim($infographic->file_path, '/'));
            }   
            $infographic->delete();

            return response()->json([
                'success' => true,
                'message' => 'Infographic deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createCampaign(Request $request)
    {
        try {
            $request->validate([
                'campaign_name' => 'required|string|max:255',
                'users_group' => 'required|string',
                'infographics' => 'required|array',
                'scheduled_at' => 'required|string',
                'schedule_type' => 'required|string|in:immediate,schedule'
            ]);
            if ($request->schedule_type === 'immediate') {
                $scheduledAt = Carbon::now()->toDateTimeString();
            } else {
                $scheduledAt = Carbon::parse($request->scheduled_at)->toDateTimeString();
            }

            $status = $request->schedule_type === 'immediate' ? 'running' : 'pending';

            //check if the user group has users
            $groupExists = UsersGroup::where('group_id', $request->users_group)
                ->where('company_id', Auth::user()->company_id)
                ->whereNotNull('users')
                ->where('users', '!=', '[]')
                ->exists();
            if (!$groupExists) {
                return response()->json(['success' => false, 'message' => __('This division does not have any employees')], 404);
            }

            $campaign = InfoGraphicCampaign::create([
                'campaign_name' => $request->campaign_name,
                'campaign_id' => Str::random(6),
                'users_group' => $request->users_group,
                'inforgraphics' => json_encode($request->infographics),
                'status' => $status,
                'scheduled_at' => $scheduledAt,
                'company_id' => Auth::user()->company_id,
            ]);
            //send the employees to live table if status is running
            if ($status === 'running') {
                $groupExists = UsersGroup::where('group_id', $campaign->users_group)
                    ->where('company_id', Auth::user()->company_id)->exists();
                if (!$groupExists) {
                    return response()->json(['success' => false, 'message' => __('Division not found')], 404);
                }
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
                        'infographic' => collect($request->infographics)->random(),
                        'company_id' => Auth::user()->company_id,
                    ]);

                    // Audit log
                    audit_log(
                        Auth::user()->company_id,
                        $user->user_email,
                        null,
                        'INFOGRAPHICS_CAMPAIGN_SIMULATED',
                        "The campaign ‘{$campaign->campaign_name}’ has been sent to {$user->user_email}",
                        'normal'
                    );
                }
            }
            log_action("Infographic campaign created for company:");

            return response()->json([
                'success' => true,
                'message' => 'Infographic campaign created successfully',
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
    public function campaignIndex()
    {
        try {
            $campaigns = InfoGraphicCampaign::where('company_id', Auth::user()->company_id)
                ->orderBy('created_at', 'desc')
                ->get();
            return response()->json([
                'success' => true,
                'data' => $campaigns
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch campaigns: ' . $e->getMessage()
            ], 500);
        }
    }
    public function campaignDetail($campaign_id)
    {
        if (!$campaign_id) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign ID is required'
            ], 400);
        }

        try {
            $campaign = InfoGraphicCampaign::with('campLive', 'campLive.infographicData')
                ->where('campaign_id', $campaign_id)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $campaign
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch campaign details: ' . $e->getMessage()
            ], 500);
        }
    }


    public function deleteCampaign($campaign_id)
    {
        try {
            if (!$campaign_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign ID is required'
                ], 400);
            }
            InfoGraphicCampaign::where('campaign_id', $campaign_id)
                ->where('company_id', Auth::user()->company_id)
                ->delete();

            InfoGraphicLiveCampaign::where('campaign_id', $campaign_id)
                ->where('company_id', Auth::user()->company_id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Campaign deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete campaign: ' . $e->getMessage()
            ], 500);
        }
    }
}
