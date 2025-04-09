<?php

namespace App\Http\Controllers;

use App\Models\PhishingEmail;
use App\Models\SenderProfile;
use Illuminate\Http\Request;

class SenderProfileController extends Controller
{
    //
    public function index()
    {
        $company_id = auth()->user()->company_id;

        $senderProfiles = SenderProfile::where('company_id', $company_id)
            ->orWhere('company_id', 'default')
            ->get();

        return response()->json([
            'senderProfiles' => $senderProfiles
        ]);
    }

    public function deleteSenderProfile(Request $request)
    {
        $request->validate([
            'senderProfileId' => 'required|integer',
        ]);

        try {
            $senderProfileId = $request->input('senderProfileId');
            $companyId = auth()->user()->company_id;

            // Delete the sender profile
            $isDeleted = SenderProfile::where('id', $senderProfileId)
                ->where('company_id', $companyId)
                ->delete();

            if ($isDeleted) {
                // Update phishing_emails table
                PhishingEmail::where('senderProfile', $senderProfileId)
                    ->where('company_id', $companyId)
                    ->update(['senderProfile' => 0]);

                log_action("Sender profile deleted successfully");

                return response()->json([
                    'status' => true,
                    'message' => __('Sender profile deleted successfully'),
                ], 200);
            }

            log_action("Failed to delete sender profile");

            return response()->json([
                'status' => false,
                'message' => 'Failed to delete sender profile',
            ], 400);
        } catch (\Exception $e) {
            log_action("Exception during sender profile delete: " . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function addSenderProfile(Request $request)
    {
        // XSS check start
        $input = $request->all();

        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
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

        $senderProfile = new SenderProfile();
        $senderProfile->profile_name = $validated['pName'];
        $senderProfile->from_name = $validated['from_name'];
        $senderProfile->from_email = $validated['from_email'];
        $senderProfile->host = $validated['smtp_host'];
        $senderProfile->username = $validated['smtp_username'];
        $senderProfile->password = $validated['smtp_password'];
        $senderProfile->company_id = auth()->user()->company_id;

        if ($senderProfile->save()) {
            log_action("Sender profile added successfully");
            return response()->json([
                'success' => true,
                'message' => __('Sender profile added successfully!'),
                'data' => $senderProfile
            ]);
        } else {
            log_action("Failed to add sender profile");
            return response()->json([
                'success' => false,
                'message' => __('Failed to add Sender Profile')
            ], 500);
        }
    }


    public function getSenderProfile($id)
    {
        $senderprofile = SenderProfile::find($id);

        if ($senderprofile) {
            return response()->json(['status' => 1, 'data' => $senderprofile]);
        } else {
            return response()->json(['status' => 0, 'msg' => __('sender profile not found')]);
        }
    }

    public function updateSenderProfile(Request $request)
    {
        // XSS check start
        $input = $request->all();

        foreach ($input as $key => $value) {
            if (is_string($value) && preg_match('/<[^>]*>|<\?php/', $value)) {
                return response()->json([
                    'status' => false,
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
            'profile_id' => 'required|integer',
            'pName' => 'required|string|max:255',
            'from_name' => 'required|string|max:255',
            'from_email' => 'required|email|max:255',
            'smtp_host' => 'required|string|max:255',
            'smtp_username' => 'required|string|max:255',
            'smtp_password' => 'required|string|max:255',
        ]);

        $companyId = auth()->user()->company_id;

        $senderProfile = SenderProfile::where('id', $request->input('profile_id'))
            ->where('company_id', $companyId)
            ->first();

        if ($senderProfile) {
            $senderProfile->update([
                'profile_name' => $request->input('pName'),
                'from_name' => $request->input('from_name'),
                'from_email' => $request->input('from_email'),
                'host' => $request->input('smtp_host'),
                'username' => $request->input('smtp_username'),
                'password' => $request->input('smtp_password'),
            ]);

            log_action("Sender profile updated successfully");

            return response()->json([
                'status' => true,
                'message' => __('Sender Profile Updated Successfully!')
            ]);
        } else {
            log_action("Failed to update sender profile");

            return response()->json([
                'status' => false,
                'message' => __('Failed to update Sender Profile')
            ], 404);
        }
    }
}
