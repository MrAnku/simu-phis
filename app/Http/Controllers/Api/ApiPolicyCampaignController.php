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

            $campign = PolicyCampaign::create([
                'campaign_name' => $request->campaign_name,
                'campaign_id' => Str::random(6),
                'users_group' => $request->users_group,
                'policy' => $request->policy,
                'scheduled_at' => $request->scheduled_at,
                'company_id' => Auth::user()->company_id,
            ]);
            log_action("Policy campaign created for company: " . Auth::user()->company_id);

            return response()->json(['message' => 'Policy campaign created successfully'], 201);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
