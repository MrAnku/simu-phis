<?php

namespace App\Http\Controllers\Api;

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

            $campaign = PolicyCampaign::create([
                'campaign_name' => $request->campaign_name,
                'campaign_id' => Str::random(6),
                'users_group' => $request->users_group,
                'policy' => $request->policy,
                'scheduled_at' => $request->scheduled_at,
                'company_id' => Auth::user()->company_id,
            ]);
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
