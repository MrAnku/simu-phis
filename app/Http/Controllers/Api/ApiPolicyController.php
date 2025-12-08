<?php

namespace App\Http\Controllers\Api;

use App\Models\Policy;
use Illuminate\Http\Request;
use App\Models\AssignedPolicy;
use App\Models\PolicyCampaign;
use App\Models\PolicyCampaignLive;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\PolicyGenerateService;
use Illuminate\Validation\ValidationException;


class ApiPolicyController extends Controller
{
    public function index()
    {
        try {
            $policies = Policy::where('company_id', Auth::user()->company_id)->get();
            return response()->json([
                'success' => true,
                'data' => $policies,
                'message' => __('Policies fetched successfully')
            ], 200);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
            ], 500);
        }
    }

    public function addPolicy(Request $request)
    {
        try {
            $request->validate([
                'policy_name' => 'required|string|max:255',
                'policy_description' => 'required|string',
                'policy_file' => 'required|file|mimes:pdf|max:10240',
                'has_quiz' => 'required|boolean',
                'json_quiz' => 'nullable|json',
            ]);

            //  Upload File
            $file = $request->file('policy_file');
            $randomName = generateRandom(32);
            $newFilename = $randomName . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('/uploads/policyFile', $newFilename, 's3');

            // Create Policy Record
            Policy::create([
                'policy_name' => $request->policy_name,
                'policy_description' => $request->policy_description,
                'policy_file' => "/" . $filePath,
                'has_quiz' => $request->has_quiz,
                'json_quiz' => $request->json_quiz,
                'company_id' => Auth::user()->company_id,
            ]);

            // Return response
            return response()->json([
                'success' => true,
                'message' => 'Policy added successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    public function editPolicy(Request $request)
    {
        try {
            $request->validate([
                'policy_id' => 'required|exists:policies,id',
                'policy_name' => 'required|string|max:255',
                'policy_description' => 'required|string',
                'policy_file' => 'nullable|file|mimes:pdf|max:10240',
                'has_quiz' => 'required|boolean',
                'json_quiz' => 'nullable|json',
            ]);

            $policy = Policy::findOrFail($request->policy_id);

            if ($request->hasFile('policy_file')) {
                // Delete the old file from S3 if it exists
                if ($policy->policy_file) {
                    $oldFilePath = ltrim($policy->policy_file, '/');
                    Storage::disk('s3')->delete($oldFilePath);
                }

                $file = $request->file('policy_file');
                $randomName = generateRandom(32);
                $extension = $file->getClientOriginalExtension();
                $newFilename = $randomName . '.' . $extension;

                $filePath = $file->storeAs('/uploads/policyFile', $newFilename, 's3');
                $policy->policy_file = "/" . $filePath;
            }

            if ($request->has_quiz == true) {
                if ($request->json_quiz == null) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Please provide a valid JSON quiz')
                    ], 422);
                }
                $json_quiz = $request->json_quiz;
            } else {
                $json_quiz = null;
            }

            $policy->has_quiz = $request->has_quiz;
            $policy->json_quiz = $json_quiz;
            $policy->policy_name = $request->policy_name;
            $policy->policy_description = $request->policy_description;
            $policy->save();

            log_action("Policy updated : " . $policy->policy_name);
            return response()->json(['success' => true, 'message' => __('Policy updated successfully')], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
            ], 500);
        }
    }


    public function fetchAssignedPolicy(Request $request)
    {
        try {
            if (!$request->query('user_email')) {
                return response()->json([
                    'success' => false,
                    'message' => __('User email is required')
                ], 400);
            }
            $user_email = $request->query('user_email');
            $assignedPolicy = AssignedPolicy::where('user_email', $user_email)->get();
            return response()->json([
                'success' => true,
                'data' => $assignedPolicy,
                'message' => __('Assigned policies fetched successfully')
            ], 200);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
            ], 500);
        }
    }

    public function acceptPolicy(Request $request)
    {
        try {
            $request->validate([
                'user_email' => 'required|email|exists:users,user_email',
                'policy' => 'required|exists:policies,id',
                'json_quiz_response' => 'nullable|json'
            ]);

            $user_email = $request->user_email;
            $policy_id = $request->policy;

            $policy = Policy::find($policy_id);
            if ($policy->has_quiz == true) {
                if ($request->json_quiz_response == null) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Quiz response is required for policies with quizzes')
                    ], 422);
                }
                $json_quiz_response = $request->json_quiz_response;
            } else {
                $json_quiz_response = null;
            }

            $isUpdated = AssignedPolicy::where('user_email', $user_email)
                ->where('policy', $policy_id)
                ->update([
                    'accepted' => true,
                    'accepted_at' => now(),
                    'json_quiz_response' => $json_quiz_response
                ]);

            if (!$isUpdated) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to accept policy.')
                ], 422);
            }

            // Audit log
            audit_log(
                Auth::user()->company_id,
                $user_email,
                null,
                'POLICY_ACCEPTED',
                "'{$user_email}' accepted this policy :  {$policy->policy_name}",
                'normal'
            );
            return response()->json([
                'success' => true,
                'message' => __('Policy accepted successfully')
            ], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
            ], 500);
        }
    }

    public function deletePolicy(Request $request)
    {
        try {
            if (!$request->route('encoded_id')) {
                return response()->json([
                    'success' => false,
                    'message' => __('Policy ID is required')
                ], 422);
            }
            $id = base64_decode($request->route('encoded_id'));
            $policy = Policy::where('id', $id)->first();

            if (!$policy) {
                return response()->json([
                    'success' => false,
                    'message' => __('Policy not found')
                ], 422);
            }

            $policyCampExists = PolicyCampaignLive::where('policy', $policy->id)->where('company_id', Auth::user()->company_id)->exists();

            if ($policyCampExists) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaigns are associated with this policy, delete campaigns first'),
                ], 422);
            }

            $assignedPolicy = AssignedPolicy::where('policy', $policy->id)->where('company_id', Auth::user()->company_id)->exists();
            if ($assignedPolicy) {
                return response()->json([
                    'success' => false,
                    'message' => __('This policy is assigned to users, you cannot delete it.'),
                ], 422);
            }

            $policy->delete();
            log_action("Policy deleted : " . $policy->policy_name);
            return response()->json([
                'success' => true,
                'message' => __('Policy deleted successfully')
            ], 200);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
            ], 500);
        }
    }

    public function deletePolicyCampaign(Request $request)
    {
        try {
            if (!$request->route('campaign_id')) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required')
                ], 422);
            }

            $campaign_id = $request->route('campaign_id');

            $policyCampaign = PolicyCampaign::where('campaign_id', $campaign_id)->first();

            if (!$policyCampaign) {
                return response()->json([
                    'success' => false,
                    'message' => __('Policy Campaign not found')
                ], 422);
            }
            PolicyCampaignLive::where('campaign_id', $campaign_id)->delete();

            $policyCampaign->delete();
            log_action("Policy Campaign deleted : " . $policyCampaign->campaign_name);
            return response()->json([
                'success' => true,
                'message' => __('Policy Campaign deleted successfully')
            ], 200);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
            ], 500);
        }
    }

public function generatePolicy(Request $request)
{
    try {
        $request->validate([
            'prompt' => 'required|string',
        ]);

        // OpenAI se text generate
        $generatedText = PolicyGenerateService::generateText($request->prompt);

        return response()->json([
            'success' => true,
            'generated_text' => $generatedText,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
        ], 500);
    }
}


}
