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
        return view('senderProfiles', compact('senderProfiles'));
    }

    public function deleteSenderProfile(Request $request)
    {
        $request->validate([
            'senderProfileId' => 'required|integer',
        ]);

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

            return redirect()->back()->with('success', 'Sender profile deleted successfully');
        }
        log_action("Failed to delete sender profile");
        return redirect()->back()->with('error', 'Failed to delete sender profile');
    }

    public function addSenderProfile(Request $request)
    {
        $request->validate([
            'pName' => 'required|string|max:255',
            'from_name' => 'required|string|max:255',
            'from_email' => 'required|email|max:255',
            'smtp_host' => 'required|string|max:255',
            'smtp_username' => 'required|string|max:255',
            'smtp_password' => 'required|string|max:255',
        ]);


        $senderProfile = new SenderProfile();
        $senderProfile->profile_name = $request->input('pName');
        $senderProfile->from_name = $request->input('from_name');
        $senderProfile->from_email = $request->input('from_email');
        $senderProfile->host = $request->input('smtp_host');
        $senderProfile->username = $request->input('smtp_username');
        $senderProfile->password = $request->input('smtp_password');
        $senderProfile->company_id = auth()->user()->company_id;

        if ($senderProfile->save()) {

            log_action("Sender profile added successfully");
            return redirect()->back()->with('success', 'Sender profile added successfully!');
        } else {
            log_action("Failed to add sender profile");
            return redirect()->back()->with('error', 'Failed to add Sender Profile');
        }
    }

    public function getSenderProfile($id)
    {
        $senderprofile = SenderProfile::find($id);

        if ($senderprofile) {
            return response()->json(['status' => 1, 'data' => $senderprofile]);
        } else {
            return response()->json(['status' => 0, 'msg' => 'sender profile not found']);
        }
    }

    public function updateSenderProfile(Request $request)
    {
        $request->validate([
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
            return redirect()->back()->with('success', 'Sender Profile Updated Successfully!');
        } else {
            log_action("Failed to update sender profile");
            return redirect()->back()->with('error', 'Failed to update Sender Profile');
        }
    }
}
