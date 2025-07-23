<?php

namespace App\Http\Controllers\LearnApi;

use App\Http\Controllers\Controller;
use App\Models\AssignedPolicy;
use Illuminate\Http\Request;

class ApiLearnPolicyController extends Controller
{
    public function fetchAssignedPolicies(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);
        $email = $request->email;
        $assignedPolicies = AssignedPolicy::with('policyData')->where('user_email', $email)->get();

        if ($assignedPolicies->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No policy has been assigned to this email.'
            ], 422);
        }

        return response()->json(['success' => true, 'data' => $assignedPolicies, 'message' => 'Assigned Policies fetched successfully'], 200);
    }
}
