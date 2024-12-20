<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignLive;
use App\Models\PhishingEmail;
use App\Models\PhishingWebsite;
use App\Models\SenderProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class PhishingEmailsController extends Controller
{
    //
    public function index()
    {
        $company_id = auth()->user()->company_id;

        $phishingEmails = PhishingEmail::with(['web', 'sender_p'])
            ->where('company_id', $company_id)
            ->orWhere('company_id', 'default')
            ->get();

        $senderProfiles = SenderProfile::where('company_id', $company_id)->orWhere('company_id', 'default')
            ->get();

        $phishingWebsites = PhishingWebsite::where('company_id', $company_id)->orWhere('company_id', 'default')
            ->get();

        return view('phishingEmails', compact('phishingEmails', 'senderProfiles', 'phishingWebsites'));
    }

    public function getTemplateById(Request $request)
    {

        $phishingEmail = PhishingEmail::find($request->id);

        if ($phishingEmail) {
            return response()->json(['status' => 1, 'data' => $phishingEmail]);
        } else {
            return response()->json(['status' => 0, 'msg' => 'No records found!']);
        }
    }

    public function updateTemplate(Request $request)
    {

        $data = $request->validate([
            'editEtemp' => 'required',
            'difficulty' => 'required',
            'updateESenderProfile' => 'required',
            'updateEAssoWebsite' => 'required'
        ]);

        $phishingEmail = PhishingEmail::find($data['editEtemp']);
        $phishingEmail->website = $data['updateEAssoWebsite'];
        $phishingEmail->difficulty = $data['difficulty'];
        $phishingEmail->senderProfile = $data['updateESenderProfile'];
        $isUpdated = $phishingEmail->save();

        if ($isUpdated) {

            log_action("Email template updated successfully");
            return redirect()->back()->with('success', 'Email template updated successfully');
        } else {
            log_action("Failed to update email template");
            return redirect()->back()->with('error', 'Failed to update email template');
        }
    }

    public function deleteTemplate(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'tempid' => 'required|integer',
            'filelocation' => 'required|string'
        ]);

        $company_id = auth()->user()->company_id;

        $tempid = $request->input('tempid');
        $filelocation = $request->input('filelocation');

        // Delete the phishing email
        $isDeleted = PhishingEmail::where('id', $tempid)->delete();

        // Delete related campaigns
        $isDeletedCampaign = Campaign::where('phishing_material', $tempid)
            ->where('company_id', $company_id)
            ->delete();

        // Delete related live campaigns
        $isDeletedCampaignLive = CampaignLive::where('phishing_material', $tempid)
            ->where('company_id', $company_id)
            ->delete();

        // Construct the absolute path of the file
        $fileAbsolutePath = storage_path('app/public/' . $filelocation); // Correct path for storage

        // Delete the file if it exists
        if (File::exists($fileAbsolutePath)) {
            File::delete($fileAbsolutePath);
        }

        if ($isDeleted) {
            log_action("Email Template deleted successfully");
            return redirect()->back()->with('success', 'Email Template deleted successfully');
        } else {
            log_action("Failed to delete email template");
            return redirect()->back()->with('error', 'Failed to delete email template');
        }
    }

    public function addEmailTemplate(Request $request)
    {
        $request->validate([
            'eMailFile' => 'required|file|mimes:html',
            'eTempName' => 'required|string|max:255',
            'eSubject' => 'required|string|max:255',
            'difficulty' => 'required|string|max:30',
            'eAssoWebsite' => 'required|string|max:255',
            'eSenderProfile' => 'required|string|max:255',
        ]);

        $company_id = auth()->user()->company_id;

        $eTempName = $request->input('eTempName');
        $eSubject = $request->input('eSubject');
        $difficulty = $request->input('difficulty');
        $eAssoWebsite = $request->input('eAssoWebsite');
        $eSenderProfile = $request->input('eSenderProfile');
        $eMailFile = $request->file('eMailFile');

        // Generate a random name for the file
        $randomName = generateRandom(32);
        $extension = $eMailFile->getClientOriginalExtension();
        $newFilename = $randomName . '.' . $extension;
        $targetDir = 'uploads/phishingMaterial/phishing_emails';

        try {
            // Move the uploaded file to the target directory
            $path = $eMailFile->storeAs($targetDir, $newFilename, 'public');

            // Insert data into the database
            $isInserted = PhishingEmail::create([
                'name' => $eTempName,
                'email_subject' => $eSubject,
                'difficulty' => $difficulty,
                'mailBodyFilePath' => $path,
                'website' => $eAssoWebsite,
                'senderProfile' => $eSenderProfile,
                'company_id' => $company_id,
            ]);

            if ($isInserted) {

                log_action("Email Template Added Successfully");
                return redirect()->back()->with('success', 'Email Template Added Successfully!');
            } else {
                log_action("Failed to add email template");
                return redirect()->back()->with('error', 'Failed to add Email Template.');
            }
        } catch (\Exception $e) {
            log_action("Failed to add email template");
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }
}
