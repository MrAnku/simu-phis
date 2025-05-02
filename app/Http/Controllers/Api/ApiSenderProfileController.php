<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhishingEmail;
use App\Models\SenderProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ApiSenderProfileController extends Controller
{
    //
    public function index()
    {
        try {
            $company_id = Auth::user()->company_id;

            $senderProfiles = SenderProfile::where('company_id', $company_id)
                ->orWhere('company_id', 'default')
                ->get();

            if ($senderProfiles->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => __('No sender profiles found for this company.'),
                    'data' => []
                ], 200); // ✅ 200 OK but no data
            }

            return response()->json([
                'success' => true,
                'message' => __('Sender profiles fetched successfully.'),
                'data' => $senderProfiles
            ], 200); // ✅ 200 OK

        } catch (\Exception $e) {
            // Log for debug
            Log::error('Error fetching sender profiles: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('An error occurred while fetching sender profiles.'),
                'error' => $e->getMessage()
            ], 500); // ❌ 500 Internal Server Error
        }
    }

    public function deleteSenderProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'senderProfileId' => 'required|integer|exists:senderprofile,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $validator->errors()->first(),
            ], 422); // ✅ 422 Unprocessable Entity
        }

        try {
            $senderProfileId = $request->input('senderProfileId');
            $companyId = Auth::user()->company_id;

            $senderProfile = SenderProfile::where('id', $senderProfileId)
                ->where('company_id', $companyId)
                ->first();

            if (!$senderProfile) {
                return response()->json([
                    'success' => false,
                    'message' => __('Sender profile not found or access denied.'),
                ], 404); // ✅ 404 Not Found
            }

            $isDeleted = $senderProfile->delete();

            if ($isDeleted) {
                PhishingEmail::where('senderProfile', $senderProfileId)
                    ->where('company_id', $companyId) // this should be treated as a string
                    ->update(['senderProfile' => 0]);

                log_action("Sender profile deleted successfully");

                return response()->json([
                    'success' => true,
                    'message' => __('Sender profile deleted successfully.'),
                ], 200);
            }

            log_action("Failed to delete sender profile");

            return response()->json([
                'success' => false,
                'message' => __('Failed to delete sender profile.'),
            ], 500);
        } catch (\Exception $e) {
            log_action("Exception during sender profile delete: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('An error occurred while deleting the sender profile.'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function addSenderProfile(Request $request)
    {
        try {
            // XSS Check start
            $input = $request->all();

            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Invalid input detected.'),
                    ], 400); // Bad Request
                }
            }

            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);
            // XSS Check end

            // Validation
            $validator = Validator::make($request->all(), [
                'pName' => 'required|string|max:255',
                'from_name' => 'required|string|max:255',
                'from_email' => 'required|email|max:255',
                'smtp_host' => 'required|string|max:255',
                'smtp_username' => 'required|string|max:255',
                'smtp_password' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Error: ') . $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422); // Unprocessable Entity
            }

            $validated = $validator->validated();

            $senderProfile = new SenderProfile();
            $senderProfile->profile_name = $validated['pName'];
            $senderProfile->from_name = $validated['from_name'];
            $senderProfile->from_email = $validated['from_email'];
            $senderProfile->host = $validated['smtp_host'];
            $senderProfile->username = $validated['smtp_username'];
            $senderProfile->password = $validated['smtp_password'];
            $senderProfile->company_id = Auth::user()->company_id;

            if ($senderProfile->save()) {
                log_action("Sender profile added successfully");
                return response()->json([
                    'success' => true,
                    'message' => __('Sender profile added successfully!'),
                    'data' => $senderProfile,
                ], 201); // Created
            }

            log_action("Failed to add sender profile");

            return response()->json([
                'success' => false,
                'message' => __('Failed to add sender profile'),
            ], 500); // Internal Server Error

        } catch (\Exception $e) {
            log_action("Exception while adding sender profile: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('An error occurred while adding the sender profile.'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function getSenderProfile($id)
    {
        try {
            $senderProfile = SenderProfile::find($id);

            if ($senderProfile) {
                return response()->json([
                    'success' => true,
                    'message' => __('Sender profile retrieved successfully'),
                    'data' => $senderProfile
                ], 200); // OK
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('Sender profile not found'),
                ], 404); // Not Found
            }
        } catch (\Exception $e) {
            log_action("Exception while fetching sender profile: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('An error occurred while fetching the sender profile.'),
                'error' => $e->getMessage()
            ], 500); // Internal Server Error
        }
    }


    public function updateSenderProfile(Request $request, $id)
    {
        try {
            // XSS check start
            $input = $request->all();

            foreach ($input as $key => $value) {
                if (is_string($value) && preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Invalid input detected.')
                    ], 400);
                }
            }

            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });

            $request->merge($input);
            // XSS check end

            $validated = $request->validate([
                'pName' => 'required|string|max:255',
                'from_name' => 'required|string|max:255',
                'from_email' => 'required|email|max:255',
                'smtp_host' => 'required|string|max:255',
                'smtp_username' => 'required|string|max:255',
                'smtp_password' => 'required|string|max:255',
            ]);

            $companyId = Auth::user()->company_id;

            $senderProfile = SenderProfile::where('id', $id)
                ->where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId)
                        ->orWhere('company_id', 'default');
                })
                ->first();

            if (!$senderProfile) {
                log_action("Sender profile not found for update");

                return response()->json([
                    'success' => false,
                    'message' => __('Sender profile not found.'),
                ], 404);
            }

            $senderProfile->update([
                'profile_name' => $validated['pName'],
                'from_name' => $validated['from_name'],
                'from_email' => $validated['from_email'],
                'host' => $validated['smtp_host'],
                'username' => $validated['smtp_username'],
                'password' => $validated['smtp_password'],
            ]);

            log_action("Sender profile updated successfully");

            return response()->json([
                'success' => true,
                'message' => __('Sender Profile Updated Successfully!'),
                'data' => $senderProfile
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: '),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            log_action("Exception during sender profile update: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('An error occurred while updating the sender profile.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
