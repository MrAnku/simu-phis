<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignLive;
use Illuminate\Http\Request;
use App\Models\PhishingEmail;
use App\Models\SenderProfile;
use App\Models\PhishingWebsite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class PhishingEmailsController extends Controller
{
    //
    public function index()
    {
        $company_id = Auth::user()->company_id;

        $phishingEmails = PhishingEmail::with(['web', 'sender_p'])
            ->where('company_id', $company_id)
            ->orWhere('company_id', 'default')
            ->paginate(10);

        $senderProfiles = SenderProfile::where('company_id', $company_id)->orWhere('company_id', 'default')
            ->get();

        $phishingWebsites = PhishingWebsite::where('company_id', $company_id)->orWhere('company_id', 'default')
            ->get();

        return view('phishingEmails', compact('phishingEmails', 'senderProfiles', 'phishingWebsites'));
    }

    public function searchPhishingEmails(Request $request)
    {
        $company_id = Auth::user()->company_id;
        $searchTerm = $request->query('search');

        $senderProfiles = SenderProfile::where('company_id', $company_id)->orWhere('company_id', 'default')
            ->get();

        $phishingWebsites = PhishingWebsite::where('company_id', $company_id)->orWhere('company_id', 'default')
            ->get();

        $phishingEmails = PhishingEmail::with(['web', 'sender_p'])
            ->where(function ($query) use ($company_id, $searchTerm) {
                $query->where('company_id', $company_id)
                    ->orWhere('company_id', 'default');
            })
            ->where('name', 'LIKE', '%' . $searchTerm . '%')
            ->paginate(10);

        return view('phishingEmails', compact('phishingEmails', 'senderProfiles', 'phishingWebsites'));
    }

    public function getTemplateById(Request $request)
    {

        $phishingEmail = PhishingEmail::find($request->id);

        if ($phishingEmail) {
            return response()->json(['status' => 1, 'data' => $phishingEmail]);
        } else {
            return response()->json(['status' => 0, 'msg' => __('No records found!')]);
        }
    }

    public function updateTemplate(Request $request)
    {
        //xss check start
        
        $input = $request->only('editEtemp', 'difficulty', 'updateESenderProfile', 'updateEAssoWebsite');
        
        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return redirect()->back()->with('error', __('Invalid input detected.'));
            }
        }
        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);

        //xss check end

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
            return redirect()->back()->with('success', __('Email template updated successfully'));
        } else {
            log_action("Failed to update email template");
            return redirect()->back()->with('error', __('Failed to update email template'));
        }
    }

    public function deleteTemplate(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'tempid' => 'required|integer',
            'filelocation' => 'required|string'
        ]);

        $company_id = Auth::user()->company_id;

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
            return redirect()->back()->with('success', __('Email Template deleted successfully'));
        } else {
            log_action("Failed to delete email template");
            return redirect()->back()->with('error', __('Failed to delete email template'));
        }
    }

    public function addEmailTemplate(Request $request)
    {
        //xss check start
        
        $input = $request->only('eTempName', 'eSubject');
        
        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return redirect()->back()->with('error', __('Invalid input detected.'));
            }
        }
        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);

        //xss check end


        $request->validate([
            'eMailFile' => 'required|file|mimes:html',
            'eTempName' => 'required|string|max:255',
            'eSubject' => 'required|string|max:255',
            'difficulty' => 'required|string|max:30',
            'eAssoWebsite' => 'required|string|max:255',
            'eSenderProfile' => 'required|string|max:255',
        ]);

        $company_id = Auth::user()->company_id;

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
                return redirect()->back()->with('success', __('Email Template Added Successfully!'));
            } else {
                log_action("Failed to add email template");
                return redirect()->back()->with('error', __('Failed to add Email Template.'));
            }
        } catch (\Exception $e) {
            log_action("Failed to add email template");
            return redirect()->back()->with('error', __('Something went wrong: ') . $e->getMessage());
        }
    }

    public function generateTemplate(Request $request)
    {
        try {
            $prompt = "Generate a valid, professional HTML email template based on the following request. The output should only contain HTML code:\n\n{$request->prompt}";

            $response = Http::withOptions(['verify' => false])->withHeaders([
                'Authorization' => 'Bearer ' . env("OPENAI_API_KEY"),
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => __('You are an expert email template generator. Always provide valid HTML code.')],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 1500,
                'temperature' => 0.7,
            ]);

            if ($response->failed()) {

                log_action("Failed to generate AI Email Template on topic of prompt: {$prompt}");

                return response()->json([
                    'status' => 0,
                    'msg' => $response->body(),
                ]);
            }

            $html = $response['choices'][0]['message']['content'];

            log_action("Email template generated using AI on topic of prompt: {$prompt}");

            return response()->json([
                'status' => 1,
                'html' => $html,
            ]);
        } catch (\Exception $e) {

            log_action("Failed to generate email template");

            return response()->json([
                'status' => 0,
                'msg' => $e->getMessage(),
            ]);
        }
    }

    public function saveAIPhishTemplate(Request $request)
    {
        //xss check start
        
        $input = $request->only('template_name', 'template_subject', 'difficulty', 'template_website', 'template_sender_profile');
        
        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return redirect()->back()->with('error', __('Invalid input detected.'));
            }
        }
        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);

        //xss check end

        try {
            $request->validate([
                'html' => 'required|string',
                'template_name' => 'required|string',
                'template_subject' => 'required|string',
                'difficulty' => 'required|string',
                'template_website' => 'required|string',
                'template_sender_profile' => 'required|numeric',
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 0, 'msg' => $e->getMessage()]);
        }

        $company_id = Auth::user()->company_id;
        $randomName = generateRandom(32);

        $html = $request->input('html');
        $filename = $randomName . '.html';
        $targetDir = 'uploads/phishingMaterial/phishing_emails';

        $path = storage_path('app/public/' . $targetDir . '/' . $filename);


        if (!is_dir(storage_path('app/public/' . $targetDir))) {
            mkdir(storage_path('app/public/' . $targetDir), 0777, true);
        }

        file_put_contents($path, $html);

        PhishingEmail::create([
            'name' => $request->template_name,
            'email_subject' => $request->template_subject,
            'difficulty' => $request->difficulty,
            'mailBodyFilePath' => $targetDir . '/' . $filename,
            'website' => $request->template_website,
            'senderProfile' => $request->template_sender_profile,
            'company_id' => $company_id,
        ]);

        return response()->json(['status' => 1, 'msg' => __('Template saved successfully')]);
    }
}
