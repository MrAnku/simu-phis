<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\PhishingWebsite;
use App\Http\Controllers\Controller;
use App\Models\PhishingEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminPhishingWebsiteController extends Controller
{
    public function index()
    {

        $phishingWebsites = PhishingWebsite::all();
        return view('admin.phishingWebsites', compact('phishingWebsites'));
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


            if ($isDeleted) {
                // Update phishing_emails
                PhishingEmail::where('website', $webid)
                    ->update(['website' => '0']);
            }

            // Delete the file
            $webfilepath = 'uploads/phishingMaterial/phishing_websites/' . $filename;
            if (Storage::disk('public')->exists($webfilepath)) {
                Storage::disk('public')->delete($webfilepath);
            }
        });

        return redirect()->back()->with('success', 'Website deleted successfully.');
    }

    public function addPhishingWebsite(Request $request)
    {
        $request->validate([
            'webName' => 'required|string|max:255',
            'webFile' => 'required|file|mimes:html',
            'subdomain' => 'nullable|string|max:255',
            'domain' => 'required|string|max:255',
        ]);


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
        $phishingWebsite->company_id = 'default';
        $phishingWebsite->save();

        return redirect()->back()->with('success', 'New website is added');
    }
}
