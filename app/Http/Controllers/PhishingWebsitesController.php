<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PhishingEmail;
use App\Models\PhishingWebsite;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class PhishingWebsitesController extends Controller
{
    //
    public function index()
    {
        $company_id = auth()->user()->company_id;

        $phishingWebsites = PhishingWebsite::where('company_id', $company_id)
            ->orWhere('company_id', 'default')
            ->get();
        return view('phishingWebsites', compact('phishingWebsites'));
    }

    public function deleteWebsite(Request $request)
    {
        $request->validate([
            'websiteid' => 'required|integer',
            'filename' => 'required|string',
        ]);


        $webid = $request->input('websiteid');
        $filename = $request->input('filename');

        DB::transaction(function () use ($webid, $filename) {
            // Delete from phishing_websites
            $isDeleted = PhishingWebsite::where('id', $webid)->delete();

            $company_id = auth()->user()->company_id;

            if ($isDeleted) {
                // Update phishing_emails
                PhishingEmail::where('website', $webid)
                    ->where('company_id', $company_id)
                    ->update(['website' => '0']);
            }

            // Delete the file
            $webfilepath = 'uploads/phishingMaterial/phishing_websites/' . $filename;
            if (Storage::disk('public')->exists($webfilepath)) {
                Storage::disk('public')->delete($webfilepath);
            }
        });
        log_action("Phishing website deleted successfully");
        return redirect()->back()->with('success', 'Phishing website deleted successfully.');
    }

    public function addPhishingWebsite(Request $request)
    {
        $request->validate([
            'webName' => 'required|string|max:255',
            'webFile' => 'required|file|mimes:html',
            'subdomain' => 'nullable|string|max:255',
            'domain' => 'required|string|max:255',
        ]);

        $company_id = auth()->user()->company_id;

        $domain = $request->input('subdomain') ? $request->input('subdomain') . '.' . $request->input('domain') : $request->input('domain');

        // Handle file
        $file = $request->file('webFile');
        $randomName = generateRandom(32); // Generate random name
        $extension = $file->getClientOriginalExtension();
        $newFilename = $randomName . '.' . $extension;
        $targetDir = 'uploads/phishingMaterial/phishing_websites/' . $newFilename;

        // Store the file
        $file->storeAs('uploads/phishingMaterial/phishing_websites', $newFilename, 'public');

        // Injecting tracking code
        $htmlContent = Storage::disk('public')->get($targetDir);
        $injectedCode = '<script src="https://code.jquery.com/jquery-3.6.1.min.js" integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>
    <script src="js/gz.js"></script>';
        $modifiedContent = str_replace('</html>', '</html>' . $injectedCode, $htmlContent);
        Storage::disk('public')->put($targetDir, $modifiedContent);

        // Insert into database
        $phishingWebsite = new PhishingWebsite();
        $phishingWebsite->name = $request->input('webName');
        $phishingWebsite->file = $newFilename;
        $phishingWebsite->domain = $domain;
        $phishingWebsite->company_id = $company_id;
        $phishingWebsite->save();

        log_action("New phishing website is added");
        return redirect()->back()->with('success', 'New phishing website is added');
    }

    public function generateWebsite(Request $request)
    {

        $request->validate([
            'description' => 'required|string',
            'company_name' => 'required|string',
            'logo_url' => 'required|url',
        ]);

        $description = $request->input('description');
        $companyName = $request->input('company_name');
        $logoUrl = $request->input('logo_url');

        $openaiApiKey = env('OPENAI_API_KEY');
        $prompt = 'Based on the following description, modify the given HTML template of a login page. Description: ' . $description;

        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ];

        $response = Http::withOptions(['verify' => false])->withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $openaiApiKey,
        ])->post('https://api.openai.com/v1/chat/completions', $data);

        if ($response->failed()) {
            return response()->json([
                'status' => 0,
                'msg' => $response->body(),
            ]);
        }

        $responseData = $response->json();

        if (!isset($responseData['choices'][0]['message']['content'])) {
            return response()->json([
                'status' => 0,
                'msg' => $responseData,
            ]);
        }

        $generatedContent = $responseData['choices'][0]['message']['content'];

        $timestamp = time();
        $directory = 'ai_site_temp/site_' . $timestamp;

        // Read the predefined template
        $template = Storage::get('public/login_template.html');
        if ($template === false) {
            return response()->json(['status' => 0, 'msg' => 'Could not read the template file.']);
        }

        // Replace placeholders with user-provided values
        $finalContent = str_replace('%company_name%', $companyName, $template);
        $finalContent = str_replace('%logo_url%', $logoUrl, $finalContent);

        // Apply additional modifications based on generated content
        $finalContent = str_replace('<!-- Content -->', $generatedContent, $finalContent);

        // Save the final content to a new file
        Storage::disk('public')->put($directory . '/index.html', $finalContent);

        return response()->json([
            'status' => 1,
            'msg' => Storage::url($directory . '/index.html'),
        ]);
    }

    public function saveGeneratedSite(Request $request)
    {
        $company_id = auth()->user()->company_id;
        // Validate the request
        $request->validate([
            'webName' => 'required|string|max:255',
            'domain' => 'required|string',
            'sitePagePath' => 'required|string'
        ]);

        $webName = $request->input('webName');
        $domain = $request->input('domain');
        $sitePagePath = $request->input('sitePagePath');

        // Define the source and destination paths
        $sourcePath = public_path($sitePagePath);
        $destinationPath = public_path('storage/uploads/phishingMaterial/phishing_websites');

        // Ensure the destination directory exists
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }

        // Generate a new alphanumeric filename
        $newFileName = Str::random(32) . '.html';


        if (File::exists($sourcePath)) {
            File::move($sourcePath, $destinationPath . '/' . $newFileName);

            // Insert into database
            $phishingWebsite = new PhishingWebsite();
            $phishingWebsite->name = $webName;
            $phishingWebsite->file = $newFileName;
            $phishingWebsite->domain = $domain;
            $phishingWebsite->company_id = $company_id;
            $phishingWebsite->save();
        }

        // Save the data in the database or perform other actions as needed

        // Return a response or redirect
        log_action("AI generated phishing website added");
        return redirect()->back()->with('success', 'Website saved successfully.');
    }
}
