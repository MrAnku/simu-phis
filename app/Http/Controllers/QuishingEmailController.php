<?php

namespace App\Http\Controllers;

use App\Models\QshTemplate;
use Illuminate\Http\Request;
use App\Models\SenderProfile;
use App\Models\PhishingWebsite;

class QuishingEmailController extends Controller
{
    public function index(Request $request)
    {
        $company_id = auth()->user()->company_id;
        
        $senderProfiles = SenderProfile::where('company_id', $company_id)->orWhere('company_id', 'default')
            ->get();

        $phishingWebsites = PhishingWebsite::where('company_id', $company_id)->orWhere('company_id', 'default')
            ->get();
            
        if ($request->has('search')) {
            $search = $request->search;
            $quishingEmails = QshTemplate::where('name', 'like', "%$search%")
                ->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id)
                        ->orWhere('company_id', 'default');
                })
                ->paginate(10);
            return view('quishing-email', compact('quishingEmails', 'senderProfiles', 'phishingWebsites'));
        }

        $quishingEmails = QshTemplate::where('company_id', auth()->user()->company_id)
            ->orWhere('company_id', 'default')
            ->paginate(10);

        return view('quishing-email', compact('quishingEmails', 'senderProfiles', 'phishingWebsites'));
    }

    public function addTemplate(Request $request){
        $request->validate([
            'template_name' => 'required',
            'template_subject' => 'required',
            'difficulty' => 'required',
            'associated_website' => 'required|exists:phishing_websites,id',
            'sender_profile' => 'required|exists:senderprofile,id',
            'template_file' => 'required|mimes:html|max:2048',
        ]);

        //validate a valid html
        $templateContent = file_get_contents($request->file('template_file')->getRealPath());

        if (strpos($templateContent, '{{user_name}}') === false || strpos($templateContent, '{{qr_code}}') === false) {
            return back()->withErrors(['template_file' => 'The template file must contain {{user_name}} and {{qr_code}} shortcodes.']);
        }

        $fileName = uniqid() . '.' . $request->file('template_file')->getClientOriginalExtension();
        $filePath = $request->file('template_file')->storeAs('public/uploads/quishing_templates', $fileName);

        QshTemplate::create([
            'name' => $request->template_name,
            'email_subject' => $request->template_subject,
            'difficulty' => $request->difficulty,

            'file' => $filePath,
            'website' => $request->associated_website,
            'sender_profile' => $request->sender_profile,
            'company_id' => auth()->user()->company_id,
        ]);

        return redirect()->route('quishing.emails')->with('success', 'Template added successfully.');

    }
}
