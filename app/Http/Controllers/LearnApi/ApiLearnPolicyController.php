<?php

namespace App\Http\Controllers\LearnApi;

use App\Http\Controllers\Controller;
use App\Models\AssignedPolicy;
use App\Models\BlueCollarEmployee;
use App\Models\Policy;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
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
            $assignedPolicies = AssignedPolicy::with('policyData')->where('user_email', $email)
            ->where('accepted', 0)->get();

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
            ]);
            $encodedId = $request->input('encoded_id');
            $policyId = base64_decode($encodedId);

            $assignedPolicy = AssignedPolicy::find($policyId);
            if (!$assignedPolicy) {
                return response()->json(['success' => false, 'message' => 'Policy not found'], 404);
            }

            $policy = Policy::where('id', $assignedPolicy->policy)->first();

            if($policy->has_quiz == 1){
                $request->validate([
                    'json_quiz_response' => 'required'
                ]);
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

    public function policyLoginWithToken(Request $request)
    {
        try {
            $token = $request->query('token');

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token is required!'
                ], 422);
            }

            $session = DB::table('learnerloginsession')->where('token', $token)->orderBy('created_at', 'desc') // Ensure the latest session is checked
                ->first();
            if (!$session || now()->greaterThan(Carbon::parse($session->expiry))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your policy session has expired!'
                ], 422);
            }

            // Decrypt the email
            $userEmail = decrypt($session->token);

            Session::put('token', $token);

            $isNormalEmployee = Users::where('user_email', $userEmail)->exists();

            if ($isNormalEmployee == 1) {
                $employeeType = 'normal';
                $userName = Users::where('user_email', $userEmail)->value('user_name');
            } else {
                $employeeType = 'bluecollar';
                $userName = BlueCollarEmployee::where('whatsapp', $userEmail)->value('user_name');
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'email' => $userEmail,
                    'employee_type' => $employeeType,
                    'user_name' => $userName,
                ],
                'message' => 'You can Login now'
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

    public function fetchAcceptedPolicies(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email'
            ]);
            $email = $request->email;
            $acceptedPolicies = AssignedPolicy::with('policyData')->where('user_email', $email)
            ->where('accepted', 1)->get();

            if ($acceptedPolicies->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No policy has been accepted by this email.'
                ], 422);
            }

            return response()->json(['success' => true, 'data' => $acceptedPolicies, 'message' => 'Accepted Policies fetched successfully'], 200);
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
