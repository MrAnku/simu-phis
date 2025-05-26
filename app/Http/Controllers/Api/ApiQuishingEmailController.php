<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QshTemplate;
use App\Models\SenderProfile;
use App\Models\PhishingWebsite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ApiQuishingEmailController extends Controller
{
    public function index(Request $request)
    {
        try {
            $company_id = Auth::user()->company_id;

            // // Fetch Sender Profiles
            // $senderProfiles = SenderProfile::where(function ($query) use ($company_id) {
            //     $query->where('company_id', $company_id)
            //         ->orWhere('company_id', 'default');
            // })->get();

            // // Fetch Phishing Websites
            // $phishingWebsites = PhishingWebsite::where(function ($query) use ($company_id) {
            //     $query->where('company_id', $company_id)
            //         ->orWhere('company_id', 'default');
            // })->get();

            // Handle Search
            if ($request->has('search')) {
                $search = $request->input('search');
                $quishingEmails = QshTemplate::with('senderProfile')
                    ->where('name', 'like', "%$search%")
                    ->where(function ($query) use ($company_id) {
                        $query->where('company_id', $company_id)
                            ->orWhere('company_id', 'default');
                    })->paginate(10);
            } else {
                // All QshTemplates if no search
                $quishingEmails = QshTemplate::with('senderProfile')
                    ->where(function ($query) use ($company_id) {
                        $query->where('company_id', $company_id)
                            ->orWhere('company_id', 'default');
                    })->paginate(10);
            }

            return response()->json([
                'status' => true,
                'quishing_emails' => $quishingEmails,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function addTemplate(Request $request)
    {
        try {
            // XSS check
            $input = $request->only('template_name', 'template_subject');
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json([
                        'status' => false,
                        'message' => __('Invalid input detected.')
                    ], 400);
                }
            }
            array_walk_recursive($input, function (&$value) {
                $value = strip_tags($value);
            });
            $request->merge($input);

            // Validation
            $validator = Validator::make($request->all(), [
                'template_name' => 'required|min:5|max:255',
                'template_subject' => 'required|min:5|max:255',
                'difficulty' => 'required',
                'associated_website' => 'required|exists:phishing_websites,id',
                'sender_profile' => 'required|exists:senderprofile,id',
                'template_file' => 'required|mimes:html|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' =>  __('Error: ') . $validator->errors()->first()
                ], 422);
            }

            // Read file contents
            $templateContent = file_get_contents($request->file('template_file')->getRealPath());

            // Check for placeholders
            if (strpos($templateContent, '{{user_name}}') === false || strpos($templateContent, '{{qr_code}}') === false) {
                return response()->json([
                    'status' => false,
                    'message' => __('The template file must contain {{user_name}} and {{qr_code}} shortcodes.')
                ], 422);
            }

            // Store the file
            $randomName = generateRandom(32);
            $extension = $request->file('template_file')->getClientOriginalExtension();
            $newFilename = $randomName . '.' . $extension;

            $filePath = $request->file('template_file')->storeAs('/uploads/quishing_templates', $newFilename, 's3');

            // Create template
            QshTemplate::create([
                'name' => $request->template_name,
                'email_subject' => $request->template_subject,
                'difficulty' => $request->difficulty,
                'file' => "/" . $filePath,
                'website' => $request->associated_website,
                'sender_profile' => $request->sender_profile,
                'company_id' => Auth::user()->company_id,
            ]);

            log_action("Template added : {$request->template_name}");
            return response()->json([
                'status' => true,
                'message' => __('Template added successfully.')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => __('Failed to add template.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function deleteTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }
        try {
            // Validate request

            $id = base64_decode($request->id);
            $template = QshTemplate::where('id', $id)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$template) {
                return response()->json([
                    'status' => false,
                    'message' => __('Template not found.')
                ], 404);
            }

            // Delete record from database
            $template->delete();

            // Delete the file from S3
            Storage::disk('s3')->delete($template->file);

            log_action("Template deleted : {$template->name}");

            return response()->json([
                'status' => true,
                'message' => __('Template deleted successfully.')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => __('Something went wrong.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateTemplate(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'template_id' => 'required',
                'website' => 'required|numeric',
                'sender_profile' => 'required|numeric',
                'difficulty' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => __('Validation failed.'),
                    'errors' => $validator->errors()
                ], 422);
            }

            // $id = base64_decode($request->template_id);
            $template = QshTemplate::find($request->template_id);

            if (!$template) {
                return response()->json([
                    'status' => false,
                    'message' => __('Template not found.')
                ], 404);
            }

            $updated = $template->update([
                'website' => $request->website,
                'sender_profile' => $request->sender_profile,
                'difficulty' => $request->difficulty,
            ]);

            if ($updated) {
                log_action("Template updated : {$template->name}");
                return response()->json([
                    'status' => true,
                    'message' => __('Template updated successfully.')
                ], 200);
            }

            return response()->json([
                'status' => false,
                'message' => __('Failed to update template.')
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => __('Something went wrong.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
