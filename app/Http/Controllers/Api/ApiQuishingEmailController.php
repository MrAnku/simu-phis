<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QshTemplate;
use Illuminate\Http\JsonResponse;
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
            } else if($request->has('difficulty')){

                 $difficulty = $request->input('difficulty');
                $quishingEmails = QshTemplate::with('senderProfile')
                    ->where('difficulty', $difficulty)
                    ->where(function ($query) use ($company_id) {
                        $query->where('company_id', $company_id)
                            ->orWhere('company_id', 'default');
                    })->paginate(10);
               
            } else{
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

    public function getTemplateById(Request $request): JsonResponse
    {
        // Manually validate the request
        $id = $request->route('id');

        // Check if campaignId exists
        if (!$id) {
            return response()->json([
                'status' => false,
                'message' => __('Template id is required.')
            ], 400);
        }

        try {
            $qshTemplate = QshTemplate::find($id);

            if (!$qshTemplate) {
                return response()->json([
                    'status' => false,
                    'message' => __('Quishing email template not found.')
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => __('Quishing email template found.'),
                'data' => $qshTemplate
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => __('Error: ') . $e->getMessage()
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
            $template_name = $template->name;

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

            log_action("Template deleted : {$template_name}");

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
        // XSS check
        $input = $request->only(
            'id',
            'name',
            'email_subject',
            'difficulty',
            'sender_profile',
            'phishing_website'
        );

        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return response()->json([
                    'success' => false,
                    'message' => __('Invalid input detected.')
                ], 400);
            }
        }

        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });

        $request->merge($input);

        // Validation
        try {
            $data = $request->validate([
                'id' => 'required|exists:qsh_templates,id',
                'name' => 'required|string|max:30',
                'email_subject' => 'required|string|max:100',
                'difficulty' => 'required|string|max:30',
                'file' => 'required|file|mimes:html',
                'phishing_website' => 'required|numeric',
                'sender_profile' => 'required|numeric'
            ]);

            $quishingEmail = QshTemplate::find($data['id']);
            if (!$quishingEmail) {

                return response()->json([
                    'success' => false,
                    'message' => __('Quishing template not found.')
                ], 404);
            }

            //store new file
            $randomName = generateRandom(32);
            $extension = $request->file('file')->getClientOriginalExtension();
            $newFilename = $randomName . '.' . $extension;

            $filePath = $request->file('file')->storeAs('/uploads/quishing_templates', $newFilename, 's3');

            QshTemplate::where('id', $data['id'])
                ->update([
                    'name' => $data['name'],
                    'email_subject' => $data['email_subject'],
                    'difficulty' => $data['difficulty'],
                    'file' => "/" . $filePath,
                    'website' => $data['phishing_website'],
                    'sender_profile' => $data['sender_profile']
                ]);
            log_action("Quishing template updated successfully (ID: {$data['id']})");

            return response()->json([
                'success' => true,
                'message' => __('Quishing template updated successfully.')
            ], 200);



        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->validator->errors()->first()
            ], 422);
        } catch (\Exception $e) {
            log_action("Exception while updating template: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Something went wrong. '). $e->getMessage()
            ], 500);
        }
    }
}
