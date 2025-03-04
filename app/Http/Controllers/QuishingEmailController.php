<?php

namespace App\Http\Controllers;

use App\Models\QshTemplate;
use Illuminate\Http\Request;
use App\Models\SenderProfile;
use App\Models\PhishingWebsite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class QuishingEmailController extends Controller
{
    public function index(Request $request)
    {
        $company_id = Auth::user()->company_id;
        
        $senderProfiles = SenderProfile::where('company_id', $company_id)->orWhere('company_id', 'default')
            ->get();

        $phishingWebsites = PhishingWebsite::where('company_id', $company_id)->orWhere('company_id', 'default')
            ->get();
            
        if ($request->has('search')) {
            $search = $request->search;
            $quishingEmails = QshTemplate::where('name', 'like', "%$search%")
                ->where(function ($query) {
                    $query->where('company_id', Auth::user()->company_id)
                        ->orWhere('company_id', 'default');
                })
                ->paginate(10);
            return view('quishing-email', compact('quishingEmails', 'senderProfiles', 'phishingWebsites'));
        }

        $quishingEmails = QshTemplate::with('senderProfile')->where('company_id', Auth::user()->company_id)
            ->orWhere('company_id', 'default')
            ->paginate(10);

        return view('quishing-email', compact('quishingEmails', 'senderProfiles', 'phishingWebsites'));
    }

    public function addTemplate(Request $request){

        //-----xss check start-----------------------------
        $input = $request->only('template_name', 'template_subject');
        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return redirect()->back()->with('error', 'Invalid input detected.');
            }
        }
        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);
        //-----xss check end-----------------------------

        $request->validate([
            'template_name' => 'required|min:5|max:255',
            'template_subject' => 'required|min:5|max:255',
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
            'company_id' => Auth::user()->company_id,
        ]);

        return redirect()->route('quishing.emails')->with('success', 'Template added successfully.');

    }

    public function deleteTemplate(Request $request){
        $id = base64_decode($request->id);
        $template = QshTemplate::where('id', $id)->where('company_id', Auth::user()->company_id)->first();
        if ($template) {
            $template->delete();
            Storage::delete($template->file);
            return response()->json(['success' => 'Template deleted successfully.']);
        }
        return response()->json(['error' => 'Template not found.']);
    }

    public function updateTemplate(Request $request){
        $request->validate([
            'template_id' => 'required',
            'website' => 'required|numeric',
            'sender_profile' => 'required|numeric',
            'difficulty' => 'required',
        ]);

        $id = base64_decode($request->template_id);
        $updated = QshTemplate::find($id)->update([
            'website' => $request->website,
            'sender_profile' => $request->sender_profile,
            'difficulty' => $request->difficulty,
        ]);

        if ($updated) {
            return redirect()->back()->with('success', 'Template updated successfully.');
        }
        return redirect()->back()->withErrors(['error' => 'Failed to update template.']);

    }
}
