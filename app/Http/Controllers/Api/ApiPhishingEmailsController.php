<?php

namespace App\Http\Controllers\Api;

use App\Models\CampaignLive;
use Illuminate\Http\Request;
use App\Models\PhishingEmail;
use App\Models\SenderProfile;
use App\Models\PhishingWebsite;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\TprmCampaignLive;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ApiPhishingEmailsController extends Controller
{
    //
    public function index(Request $request): JsonResponse
    {
        try {
            $company_id = Auth::user()->company_id;

            // Base queries
            $defaultQuery = PhishingEmail::with(['web', 'sender_p'])
                ->where('company_id', 'default');

            $customQuery = PhishingEmail::with(['web', 'sender_p'])
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
            $combinedQuery = PhishingEmail::with(['web', 'sender_p'])
                ->whereIn('company_id', ['default', $company_id]);

            // Return JSON
            return response()->json([
                'status' => true,
                'message' => __('Phishing data fetched successfully.'),
                'data' => [
                    'phishingEmails' => $combinedQuery->paginate(9), // all together
                    'default'        => $defaultQuery->paginate(9),
                    'custom'         => $customQuery->paginate(9),
                ]
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => __('Error: ') . $e->validator->errors()->first()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => __('Error: ') . $e->getMessage()
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
            $phishingEmail = PhishingEmail::find($id);

            if (!$phishingEmail) {
                return response()->json([
                    'success' => false,
                    'message' => __('Phishing email template not found.')
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => __('Phishing email template found.'),
                'data' => $phishingEmail
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


        // Validation
        try {
            $data = $request->validate([
                'id' => 'required|exists:phishing_emails,id',
                'name' => 'required|string|max:30',
                'email_subject' => 'required|string|max:100',
                'difficulty' => 'required|string|max:30',
                'file' => 'required|file|mimes:html',
                'phishing_website' => 'required|numeric',
                'sender_profile' => 'required|numeric'
            ]);

            $phishingEmail = PhishingEmail::find($data['id']);
            if (!$phishingEmail) {

                return response()->json([
                    'success' => false,
                    'message' => __('Phishing email template not found.')
                ], 404);
            }

            // Get the previous file path
            $oldFilePath = ltrim($phishingEmail->mailBodyFilePath, '/');

            // Get new file content
            $newFileContent = file_get_contents($request->file('file')->getRealPath());

            // Overwrite the previous file in S3
            Storage::disk('s3')->put($oldFilePath, $newFileContent);

            // Update other fields in the database
            PhishingEmail::where('id', $data['id'])->update([
                'name' => $data['name'],
                'email_subject' => $data['email_subject'],
                'difficulty' => $data['difficulty'],
                'website' => $data['phishing_website'],
                'senderProfile' => $data['sender_profile']
            ]);

            log_action("Email template updated successfully (ID: {$data['id']})");

            return response()->json([
                'success' => true,
                'message' => __('Email template updated successfully.')
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

    public function deleteTemplate(Request $request)
    {
        try {
            $request->validate([
                'tempid' => 'required|integer|exists:phishing_emails,id'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->validator->errors()->first()
            ], 422);
        }

        $tempid = $request->input('tempid');
        $company_id = Auth::user()->company_id;

        try {
            $template = PhishingEmail::where('id', $tempid)->where('company_id', $company_id)->first();

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => __('Phishing email template does not added by this user.')
                ], 404);
            }

            $emailCampExists = CampaignLive::where('phishing_material', $template->id)->where('company_id', $company_id)->exists();
            $tprmCampExists = TprmCampaignLive::where('phishing_material', $template->id)->where('company_id', $company_id)->exists();

            if ($emailCampExists || $tprmCampExists) {
                return response()->json([
                    'success' => false,
                    'message' => "Campaigns are associated with this template, delete campaigns first",
                ], 422);
            }

            $isDeleted = $template->delete();

            // Delete the file from S3
            Storage::disk('s3')->delete($template->mailBodyFilePath);

            if ($isDeleted) {
                log_action("Email Template (ID: $tempid) deleted successfully");

                return response()->json([
                    'success' => true,
                    'message' => __('Email Template deleted successfully.')
                ], 200);
            } else {
                log_action("Failed to delete Email Template (ID: $tempid)");

                return response()->json([
                    'success' => false,
                    'message' => __('Failed to delete Email Template.')
                ], 500);
            }
        } catch (\Exception $e) {
            log_action("Exception while deleting template: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function addEmailTemplate(Request $request)
    {
        try {

            // XSS check start
            $input = $request->only('eTempName', 'eSubject');

            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Invalid input detected.')
                    ], 400); // Bad Request
                }
            }
            // XSS check end

            // Validation
            $request->validate([
                'eMailFile' => 'required|file|mimes:html',
                'eTempName' => 'required|string|max:255',
                'eSubject' => 'required|string|max:255',
                'difficulty' => 'required|string|max:30',
                'eAssoWebsite' => 'required|string|max:255',
                'eSenderProfile' => 'required|string|max:255',
            ]);

            $company_id = Auth::user()->company_id;
            $eMailFile = $request->file('eMailFile');

            // Generate a random name for the file
            $randomName = generateRandom(32);
            $extension = $eMailFile->getClientOriginalExtension();
            $newFilename = $randomName . '.' . $extension;
            // Move the uploaded file to the target directory
            $filePath = $request->file('eMailFile')->storeAs('/uploads/phishingMaterial/phishing_emails', $newFilename, 's3');

            // Insert data into the database
            PhishingEmail::create([
                'name' => $request->input('eTempName'),
                'email_subject' => $request->input('eSubject'),
                'difficulty' => $request->input('difficulty'),
                'mailBodyFilePath' =>  "/" . $filePath,
                'website' => $request->input('eAssoWebsite'),
                'senderProfile' => $request->input('eSenderProfile'),
                'company_id' => $company_id,
            ]);

            log_action("Email Template Added Successfully");
            return response()->json([
                'success' => true,
                'message' => __('Email Template Added Successfully!')
            ], 201); // Created

        } catch (\Illuminate\Validation\ValidationException $e) {
            log_action("Validation error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->validator->errors()->first()
            ], 422); // Unprocessable Entity

        } catch (\Exception $e) {
            log_action("Failed to add email template");
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500); // Internal Server Error
        }
    }

    public function addEmailTemplateBulk(Request $request)
    {
        // XSS check start
        $input = $request->only('eTempName', 'eSubject');

        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return response()->json([
                    'status' => false,
                    'message' => __('Invalid input detected.')
                ], 400); // Bad Request
            }
        }

        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);
        // XSS check end

        // Validation
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

        try {
            // Move the uploaded file to the target directory
            $filePath = $request->file('eMailFile')->storeAs('/uploads/phishingMaterial/phishing_emails', $newFilename, 's3');

            // Insert data into the database
            $isInserted = PhishingEmail::create([
                'name' => $eTempName,
                'email_subject' => $eSubject,
                'difficulty' => $difficulty,
                'mailBodyFilePath' =>  "/" . $filePath,
                'website' => $eAssoWebsite,
                'senderProfile' => $eSenderProfile,
                'company_id' => 'default',
            ]);

            if ($isInserted) {
                // log_action("Email Template Added Successfully");
                return response()->json([
                    'status' => true,
                    'message' => __('Email Template Added Successfully!')
                ], 201); // Created
            } else {
                // log_action("Failed to add email template");
                return response()->json([
                    'status' => false,
                    'message' => __('Failed to add Email Template.')
                ], 500); // Internal Server Error
            }
        } catch (\Exception $e) {
            // log_action("Failed to add email template");
            return response()->json([
                'status' => false,
                'message' => __('Something went wrong: ') . $e->getMessage()
            ], 500); // Internal Server Error
        }
    }


    public function generateTemplate(Request $request)
    {
        try {
            // Validate input
            if (empty($request->prompt)) {
                throw new \Exception('Prompt cannot be empty');
            }

            // Define a structured system prompt for inline CSS email template
            $systemPrompt = <<<EOT
You are an expert email template generator. Generate a valid, professional HTML email template that strictly adheres to the following requirements:
1. Uses responsive design with a max-width of 600px
2. Includes proper HTML email boilerplate (DOCTYPE, meta tags, etc.)
3. Uses ONLY inline CSS (style attributes directly on HTML elements, no <style> tags)
4. Uses a single-column, div-based layout (ABSOLUTELY NO <table> tags or table-based layouts)
5. Includes a header, body, and footer section
6. Is clean, professional, and follows email best practices
7. Is mobile-friendly with a fluid, single-column layout
8. Includes placeholder text for dynamic content (e.g., [COMPANY_NAME], [CONTENT])
9. Returns only the HTML code without any markdown code fences (```), explanations, or comments
10. Ensures compatibility with major email clients (Outlook, Gmail, Apple Mail, etc.)
11. Uses div elements with inline CSS for layout, avoiding any table, tr, td, or other table-related tags
EOT;

            // Combine user prompt with additional instructions
            $userPrompt = <<<EOT
Generate a valid, professional HTML email template based on the following request:
{$request->prompt}

Ensure the template strictly adheres to:
- A professional header with a logo placeholder (e.g., [LOGO_URL])
- A main content area with the requested content
- A footer with unsubscribe link and company information (e.g., [COMPANY_NAME], [COMPANY_ADDRESS])
- Inline CSS styling ONLY (style attributes on elements, no <style> tags)
- A single-column, div-based layout (NO <table> tags or table-based layouts)
- Responsive design for mobile devices using inline CSS
- No markdown code fences (```), comments, or non-HTML content in the output
- Compatibility with major email clients
EOT;

            // Make API request to GPT-4o
            $response = Http::withOptions(['verify' => false])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                    'Content-Type' => 'application/json',
                ])
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'max_tokens' => 2000,
                    'temperature' => 0.3, // Lowered further for strict adherence to instructions
                    'top_p' => 0.9, // Added to focus on high-probability outputs
                ]);

            // Check for API failure
            if ($response->failed()) {
                $errorMessage = $response->body();
                log_action("Failed to generate AI Email Template on topic of prompt: {$request->prompt}. Error: {$errorMessage}");

                return response()->json([
                    'status' => false,
                    'message' => 'Failed to generate email template: ' . $errorMessage,
                ], 500);
            }

            // Extract HTML content
            $html = $response['choices'][0]['message']['content'] ?? '';

            // Clean up potential markdown fences
            $html = preg_replace('/^```html\s*|\s*```$/m', '', trim($html));

            // Validate HTML structure and ensure no <style> or <table> tags
            if (empty($html) || !str_contains(strtolower($html), '<html') || !str_contains(strtolower($html), '<body')) {
                log_action("Invalid HTML structure generated for prompt: {$request->prompt}");
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid HTML template structure',
                ], 500);
            }

            // Strict check for <style> tags
            if (preg_match('/<style\b[^>]*>/i', $html)) {
                log_action("Generated HTML contains <style> tags for prompt: {$request->prompt}");
                return response()->json([
                    'status' => false,
                    'message' => 'Generated template contains unsupported <style> tags',
                ], 500);
            }

            // Strict check for <table> tags
            if (preg_match('/<table\b[^>]*>/i', $html)) {
                log_action("Generated HTML contains <table> tags for prompt: {$request->prompt}");
                return response()->json([
                    'status' => false,
                    'message' => 'Generated template contains unsupported <table> tags',
                ], 500);
            }

            // Log success
            log_action("Email template generated using AI on topic of prompt: {$request->prompt}");

            return response()->json([
                'status' => true,
                'html' => $html,
                'message' => __('Successfully generated email template'),
            ]);
        } catch (\Exception $e) {
            log_action("Error occurred while generating AI Email Template on topic of prompt: {$request->prompt}. Error: " . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function saveAIPhishTemplate(Request $request)
    {
        // Only allow specific fields for input
        $input = $request->only(
            'template_name',
            'template_subject',
            'difficulty',
            'template_website',
            'template_sender_profile'
        );

        // Initial validation (checking ID existence and basic structure)
        $validator = Validator::make($request->all(), [
            // 'id' => 'required|integer|exists:phishing_emails,id',
            'template_name' => 'required|string|max:255',
            'template_subject' => 'required|string|max:255',
            'difficulty' => 'required|string|max:30',
            'template_website' => 'required|string|max:255',
            'template_sender_profile' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        // XSS protection: Check and sanitize input
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

        // Validate 'html' input separately
        try {
            $request->validate([
                'html' => 'required|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            $company_id = Auth::user()->company_id;
            $randomName = generateRandom(32);
            $html = $request->input('html');

            $filename = $randomName . '.html';
            $targetDir = 'uploads/phishingMaterial/phishing_emails';
            $storagePath = storage_path("app/public/{$targetDir}");

            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0777, true);
            }

            $fullPath = "{$storagePath}/{$filename}";
            file_put_contents($fullPath, $html);

            PhishingEmail::create([
                'name' => $request->template_name,
                'email_subject' => $request->template_subject,
                'difficulty' => $request->difficulty,
                'mailBodyFilePath' => "{$targetDir}/{$filename}",
                'website' => $request->template_website,
                'senderProfile' => $request->template_sender_profile,
                'company_id' => $company_id,
            ]);

            log_action("AI-generated email template saved: {$request->template_name}");

            return response()->json([
                'status' => true,
                'message' => 'Template saved successfully.'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function duplicate(Request $request)
    {
        try {
            if (!$request->route('id')) {
                return response()->json([
                    'success' => false,
                    'message' => __('Phishing Email ID is required')
                ], 422);
            }
            $id = base64_decode($request->route('id'));

            $phishingEmail = PhishingEmail::where('id', $id)->first();
            if (!$phishingEmail) {
                return response()->json([
                    'success' => false,
                    'message' => __('Phishing Email not found')
                ], 422);
            }
            if (!$request->route('id')) {
                return response()->json([
                    'success' => false,
                    'message' => __('Phishing Email ID is required')
                ], 422);
            }
            $id = base64_decode($request->route('id'));

            $phishingEmail = PhishingEmail::where('id', $id)->first();
            if (!$phishingEmail) {
                return response()->json([
                    'success' => false,
                    'message' => __('Phishing Email not found')
                ], 422);
            }

            $originalPath = ltrim($phishingEmail->mailBodyFilePath, '/');
            $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
            $randomName = generateRandom(32) . '.' . $extension;
            $newPath = 'uploads/phishingMaterial/phishing_emails/' . $randomName;

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

            // Duplicate the DB record
            $duplicateTraining = $phishingEmail->replicate(['company_id', 'name', 'mailBodyFilePath']);
            $duplicateTraining->company_id = Auth::user()->company_id;
            $duplicateTraining->name = $phishingEmail->name . ' (Copy)';
            $duplicateTraining->mailBodyFilePath = '/' . $newPath;
            $duplicateTraining->save();


            return response()->json([
                'success' => true,
                'message' => __('Phishing Email duplicated successfully'),
                'data' => $duplicateTraining
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
