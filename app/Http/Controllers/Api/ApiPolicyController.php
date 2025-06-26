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
use Illuminate\Validation\ValidationException;

class ApiPolicyController extends Controller
{
    public function index(){
        try {
            $policies = Policy::where('company_id', Auth::user()->company_id)->get();
            return response()->json([
                'success' => true,
                'data' => $policies,
                'message' => 'Policies fetched successfully'
            ], 200);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
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

            $file = $request->file('policy_file');

            // Generate a random name for the file
            $randomName = generateRandom(32);
            $extension = $file->getClientOriginalExtension();
            $newFilename = $randomName . '.' . $extension;

            $filePath = $request->file('policy_file')->storeAs('/uploads/policyFile', $newFilename, 's3');

            if($request->has_quiz == true){
                $json_quiz = $request->json_quiz;
            }else{
                $json_quiz = null;
            }

            $policy = Policy::create([
                'policy_name' => $request->policy_name,
                'policy_description' => $request->policy_description,
                'policy_file' => "/" . $filePath,
                'has_quiz' => $request->has_quiz,
                'json_quiz' => $json_quiz,
                'company_id' => Auth::user()->company_id,
            ]);
            log_action("Policy created for company : " . Auth::user()->company_name);
            return response()->json(['success' => true, 'message' => 'Policy added successfully'], 201);
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

    public function editPolicy(Request $request)
    {
        try {
            $request->validate([
                'policy_id' => 'required|exists:policies,id',
                'policy_name' => 'required|string|max:255',
                'policy_description' => 'required|string',
                'policy_file' => 'nullable|file|mimes:pdf|max:10240',
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

            $policy->policy_name = $request->policy_name;
            $policy->policy_description = $request->policy_description;
            $policy->save();

            log_action("Policy updated : " . $policy->policy_name);
            return response()->json(['success' => true, 'message' => 'Policy updated successfully'], 200);
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


    public function fetchAssignedPolicy(Request $request)
    {
        try {
            if (!$request->query('user_email')) {
                return response()->json([
                    'success' => false,
                    'message' => 'User email is required'
                ], 400);
            }
            $user_email = $request->query('user_email');
            $assignedPolicy = AssignedPolicy::where('user_email', $user_email)->get();
            return response()->json([
                'success' => true,
                'data' => $assignedPolicy,
                'message' => 'Assigned policies fetched successfully'
            ], 200);
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
                'user_email' => 'required|email|exists:users,user_email',
                'policy' => 'required|exists:policies,id',
            ]);

            $user_email = $request->user_email;
            $policy_id = $request->policy;

            $isUpdated = AssignedPolicy::where('user_email', $user_email)
                ->where('policy', $policy_id)
                ->update([
                    'accepted' => true,
                    'accepted_at' => now()
                ]);

            if (!$isUpdated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to accept policy.'
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Policy accepted successfully'
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

    public function deletePolicy(Request $request)
    {
        try {
            if(!$request->route('encoded_id')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Policy ID is required'
                ], 422);
            }
            $id = base64_decode($request->route('encoded_id'));
            $policy = Policy::where('id', $id)->first();

            if (!$policy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Policy not found'
                ], 422);
            }
            PolicyCampaign::where('policy', $id)->delete();
            PolicyCampaignLive::where('policy', $id)->delete();
            AssignedPolicy::where('policy', $id)->delete();

            $policy->delete();
            log_action("Policy deleted : " . $policy->policy_name);
            return response()->json([
                'success' => true,
                'message' => 'Policy deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

     public function deletePolicyCampaign(Request $request)
    {
        try {
            if(!$request->route('campaign_id')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign ID is required'
                ], 422);
            }

            $campaign_id = $request->route('campaign_id');
           
            $policyCampaign = PolicyCampaign::where('campaign_id', $campaign_id)->first();

            if (!$policyCampaign) {
                return response()->json([
                    'success' => false,
                    'message' => 'Policy Campaign not found'
                ], 422);
            }
            PolicyCampaignLive::where('campaign_id', $campaign_id)->delete();

            $policyCampaign->delete();
            log_action("Policy Campaign deleted : " . $policyCampaign->campaign_name);
            return response()->json([
                'success' => true,
                'message' => 'Policy Campaign deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
