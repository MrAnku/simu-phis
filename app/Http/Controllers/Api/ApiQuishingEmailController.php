<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QshTemplate;
use Illuminate\Http\JsonResponse;
use App\Models\SenderProfile;
use App\Models\PhishingWebsite;
use App\Models\QuishingLiveCamp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ApiQuishingEmailController extends Controller
{
    public function index(Request $request)
    {
        try {
            $company_id = Auth::user()->company_id;
            // Handle Search
             // Base queries
            $defaultQuery = QshTemplate::with('senderProfile')
                ->where('company_id', 'default');

            $customQuery = QshTemplate::with('senderProfile')
                ->where('company_id', $company_id);

             // Apply filters
            if ($request->filled('search')) {
                $searchTerm = $request->input('search');
                $defaultQuery->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email_subject', 'like', "%{$searchTerm}%");
                });
                $customQuery->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email_subject', 'like', "%{$searchTerm}%");
                });
            }

            if ($request->filled('difficulty')) {
                $difficulty = $request->input('difficulty');
                $defaultQuery->where('difficulty', $difficulty);
                $customQuery->where('difficulty', $difficulty);
            }

            // Combined (no union, just where-in)
            $combinedQuery = QshTemplate::with('senderProfile')
                ->whereIn('company_id', ['default', $company_id]);

            return response()->json([
                'status' => true,
                'quishing_emails' => $combinedQuery->paginate(9),
                'default' => $defaultQuery->paginate(9),
                'custom' => $customQuery->paginate(9),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function addQuishingTemplateBulk(Request $request)
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
            if (strpos($templateContent, '{{tracker_img}}') === false || strpos($templateContent, '{{qr_code}}') === false || strpos($templateContent, '{{website_url}}') === false) {
                return response()->json([
                    'status' => false,
                    'message' => __('The template file must contain {{tracker_img}}, {{qr_code}}, and {{website_url}} shortcodes.')
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
                'company_id' => 'default',
            ]);

            // log_action("Template added : {$request->template_name}");
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

    public function addTemplate(Request $request)
    {
        try {
            // XSS check
            $input = $request->only('template_name', 'template_subject');
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Invalid input detected.')
                    ], 400);
                }
            }


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
                    'success' => false,
                    'message' =>  __('Error: ') . $validator->errors()->first()
                ], 422);
            }

            // Read file contents
            $templateContent = file_get_contents($request->file('template_file')->getRealPath());

            // Check for placeholders
            if (strpos($templateContent, '{{user_name}}') === false || strpos($templateContent, '{{qr_code}}') === false) {
                return response()->json([
                    'success' => false,
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
                'success' => true,
                'message' => __('Template added successfully.')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function duplicate(Request $request)
    {
        try {
            if (!$request->route('id')) {
                return response()->json([
                    'success' => false,
                    'message' => __('Quishing Template ID is required')
                ], 422);
            }
            $id = base64_decode($request->route('id'));

            $qshTemplate = QshTemplate::where('id', $id)->first();
            if (!$qshTemplate) {
                return response()->json([
                    'success' => false,
                    'message' => __('Quishing Template not found')
                ], 422);
            }

            $originalPath = ltrim($qshTemplate->file, '/');
            $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
            $randomName = generateRandom(32) . '.' . $extension;
            $newPath = 'uploads/quishing_templates/' . $randomName;

            // Check if file exists in S3
            if (!Storage::disk('s3')->exists($originalPath)) {
                return response()->json([
                    'success' => false,
                    'message' => __('Original HTML file not found in S3.')
                ], 404);
            }

            // Get file content as string
            $fullPath = env('CLOUDFRONT_URL') . '/' . $originalPath;

            $fileContent = file_get_contents($fullPath);

            if (empty($fileContent)) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to read original HTML file from S3.')
                ], 500);
            }

            // Save the copied file to S3
            Storage::disk('s3')->put($newPath, $fileContent);

            $duplicateTraining = $qshTemplate->replicate(['company_id', 'name', 'file']);
            $duplicateTraining->company_id = Auth::user()->company_id;
            $duplicateTraining->name = $qshTemplate->name . ' (Copy)';
            $duplicateTraining->file = '/' . $newPath;

            $duplicateTraining->save();

            return response()->json([
                'success' => true,
                'message' => __('Quishing Template duplicated successfully'),
                'data' => $duplicateTraining
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
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
                'success' => false,
                'message' => __('Template id is required.')
            ], 400);
        }

        try {
            $qshTemplate = QshTemplate::find($id);

            if (!$qshTemplate) {
                return response()->json([
                    'success' => false,
                    'message' => __('Quishing email template not found.')
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => __('Quishing email template found.'),
                'data' => $qshTemplate
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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
                'success' => false,
                'message' => "Error: " . $validator->errors()->first(),
            ], 422);
        }
        try {
            // Validate request

            $id = base64_decode($request->id);
            $company_id = Auth::user()->company_id;

            $template = QshTemplate::where('id', $id)
                ->where('company_id', $company_id)
                ->first();


            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => __('Template not found.')
                ], 404);
            }

            $quishCampExists = QuishingLiveCamp::where('quishing_material', $template->id)->where('company_id', $company_id)->exists();

            if ($quishCampExists) {
                return response()->json([
                    'success' => false,
                    'message' => "Campaigns are associated with this template, delete campaigns first",
                ], 422);
            }

            $template_name = $template->name;

            // Delete record from database
            $template->delete();

            // Delete the file from S3
            Storage::disk('s3')->delete($template->file);

            log_action("Template deleted : {$template_name}");

            return response()->json([
                'success' => true,
                'message' => __('Template deleted successfully.')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
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

             // Get the previous file path
            $oldFilePath = ltrim($quishingEmail->file, '/');

            // Get new file content
            $newFileContent = file_get_contents($request->file('file')->getRealPath());

            // Overwrite the previous file in S3
            Storage::disk('s3')->put($oldFilePath, $newFileContent);

            QshTemplate::where('id', $data['id'])
                ->update([
                    'name' => $data['name'],
                    'email_subject' => $data['email_subject'],
                    'difficulty' => $data['difficulty'],
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
                'message' => __('Something went wrong. ') . $e->getMessage()
            ], 500);
        }
    }
}
