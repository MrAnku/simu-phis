<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignLive;
use App\Models\PhishingEmail;
use App\Models\PhishingWebsite;
use App\Models\SenderProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AdminPhishingEmailController extends Controller
{
    //
    public function index()
    {

        $phishingEmails = PhishingEmail::with(['web', 'sender_p'])->get();

        $senderProfiles = SenderProfile::all();

        $phishingWebsites = PhishingWebsite::all();

        return view('admin.phishingEmails', compact('phishingEmails', 'senderProfiles', 'phishingWebsites'));
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
            'updateESenderProfile' => 'required',
            'difficulty' => 'required',
            'updateEAssoWebsite' => 'required'
        ]);

        $phishingEmail = PhishingEmail::find($data['editEtemp']);
        $phishingEmail->website = $data['updateEAssoWebsite'];
        $phishingEmail->difficulty = $data['difficulty'];
        $phishingEmail->senderProfile = $data['updateESenderProfile'];
        $isUpdated = $phishingEmail->save();

        if ($isUpdated) {
            return redirect()->back()->with('success', 'Email template updated successfully');
        } else {
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


        $tempid = $request->input('tempid');
        $filelocation = $request->input('filelocation');

        // Delete the phishing email
        $isDeleted = PhishingEmail::where('id', $tempid)->delete();

        // Delete related campaigns
        $isDeletedCampaign = Campaign::where('phishing_material', $tempid)
            ->delete();

        // Delete related live campaigns
        $isDeletedCampaignLive = CampaignLive::where('phishing_material', $tempid)
            ->delete();

        // Construct the absolute path of the file
        $fileAbsolutePath = storage_path('app/public/' . $filelocation); // Correct path for storage

        // Delete the file if it exists
        if (File::exists($fileAbsolutePath)) {
            File::delete($fileAbsolutePath);
        }

        if ($isDeleted) {
            return redirect()->back()->with('success', 'Template deleted successfully');
        } else {
            return redirect()->back()->with('error', 'Failed to delete template');
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
                'company_id' => 'default',
            ]);

            if ($isInserted) {
                return redirect()->back()->with('success', 'Email Template Added Successfully!');
            } else {
                return redirect()->back()->with('error', 'Failed to add Email Template.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }
}
