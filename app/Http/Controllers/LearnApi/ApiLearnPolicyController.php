<?php

namespace App\Http\Controllers\LearnApi;

use App\Http\Controllers\Controller;
use App\Models\AssignedPolicy;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApiLearnPolicyController extends Controller
{
    public function fetchAssignedPolicies(Request $request)
    {
        try {
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
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function acceptPolicy(Request $request)
    {
        try {
            $request->validate([
                'encoded_id' => 'required',
                'json_quiz_response' => 'required'
            ]);
            $encodedId = $request->input('encoded_id');
            $policyId = base64_decode($encodedId);

            $assignedPolicy = AssignedPolicy::find($policyId);
            if (!$assignedPolicy) {
                return response()->json(['success' => false, 'message' => 'Policy not found'], 404);
            }

            $companyId = $assignedPolicy->company_id;
            setCompanyTimezone($companyId);

            $responses = null;
            if ($request->input('json_quiz_response')) {
                $responses = $request->input('json_quiz_response');

                if (!is_array($responses)) {
                    return response()->json(['success' => false, 'message' => 'Invalid quiz response format'], 422);
                }
            }

            $assignedPolicy->update([
                'accepted' => 1,
                'accepted_at' => now(),
                'json_quiz_response' => json_encode($responses),
            ]);

            log_action("Policy with ID {$policyId} accepted by user", 'learner', 'learner');

            return response()->json([
                'success' => true,
                'message' => 'Policy accepted and quiz response saved successfully.'
            ], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
