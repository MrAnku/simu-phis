<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PhishingEmail;
use App\Models\SenderProfile;
use Illuminate\Http\Request;

class AdminSenderProfileController extends Controller
{
    public function index()
    {

        $senderProfiles = SenderProfile::all();
        return view('admin.senderProfiles', compact('senderProfiles'));
    }

    public function deleteSenderProfile(Request $request)
    {
        $request->validate([
            'senderProfileId' => 'required|integer',
        ]);

        $senderProfileId = $request->input('senderProfileId');

        // Delete the sender profile
        $isDeleted = SenderProfile::where('id', $senderProfileId)
            ->delete();

        if ($isDeleted) {
            // Update phishing_emails table
            PhishingEmail::where('senderProfile', $senderProfileId)
                ->update(['senderProfile' => 0]);

            return redirect()->back()->with('success', 'Sender profile deleted successfully');
        }

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

        $profileName = $request->input('pName');
        $fromName = $request->input('from_name');
        $fromEmail = $request->input('from_email');
        $smtpHost = $request->input('smtp_host');
        $smtpUsername = $request->input('smtp_username');
        $smtpPassword = $request->input('smtp_password');

        $senderProfile = new SenderProfile();
        $senderProfile->profile_name = $profileName;
        $senderProfile->from_name = $fromName;
        $senderProfile->from_email = $fromEmail;
        $senderProfile->host = $smtpHost;
        $senderProfile->username = $smtpUsername;
        $senderProfile->password = $smtpPassword;
        $senderProfile->company_id = 'default';

        if ($senderProfile->save()) {
            return redirect()->back()->with('success', 'Sender Profile Added Successfully!');
        } else {
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


        $senderProfile = SenderProfile::where('id', $request->input('profile_id'))
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

            return redirect()->back()->with('success', 'Sender Profile Updated Successfully!');
        } else {
            return redirect()->back()->with('error', 'Failed to update Sender Profile');
        }
    }
}
