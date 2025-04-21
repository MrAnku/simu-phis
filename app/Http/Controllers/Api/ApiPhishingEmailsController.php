<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\CampaignLive;
use App\Models\PhishingEmail;
use App\Models\SenderProfile;
use App\Models\PhishingWebsite;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ApiPhishingEmailsController extends Controller
{
    //
    public function index(): JsonResponse
    {
        try {
            $company_id = Auth::user()->company_id;

            $phishingEmails = PhishingEmail::with(['web', 'sender_p'])
                ->where('company_id', $company_id)
                ->orWhere('company_id', 'default')
                ->paginate(10);

            $senderProfiles = SenderProfile::where('company_id', $company_id)
                ->orWhere('company_id', 'default')
                ->get();

            $phishingWebsites = PhishingWebsite::where('company_id', $company_id)
                ->orWhere('company_id', 'default')
                ->get();

            return response()->json([
                'status' => true,
                'message' => __('Phishing data fetched successfully.'),
                'data' => [
                    'phishingEmails' => $phishingEmails,
                    'senderProfiles' => $senderProfiles,
                    'phishingWebsites' => $phishingWebsites,
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

    public function searchPhishingEmails(Request $request): JsonResponse
    {
        try {
            $company_id = Auth::user()->company_id;
            $searchTerm = $request->query('search');

            $senderProfiles = SenderProfile::where('company_id', $company_id)
                ->orWhere('company_id', 'default')
                ->get();

            $phishingWebsites = PhishingWebsite::where('company_id', $company_id)
                ->orWhere('company_id', 'default')
                ->get();

            $phishingEmails = PhishingEmail::with(['web', 'sender_p'])
                ->where(function ($query) use ($company_id) {
                    $query->where('company_id', $company_id)
                        ->orWhere('company_id', 'default');
                })
                ->when($searchTerm, function ($query, $searchTerm) {
                    return $query->where('name', 'LIKE', '%' . $searchTerm . '%');
                })
                ->paginate(10);

            return response()->json([
                'status' => true,
                'message' => __('Phishing emails fetched successfully'),
                'data' => [
                    'phishingEmails' => $phishingEmails,
                    'senderProfiles' => $senderProfiles,
                    'phishingWebsites' => $phishingWebsites,
                ],
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
                'status' => false,
                'message' => __('Template id is required.')
            ], 400);
        }

        try {
            $phishingEmail = PhishingEmail::find($id);

            return response()->json([
                'status' => true,
                'message' => __('Phishing email template found.'),
                'data' => $phishingEmail
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }


    public function updateTemplate(Request $request)
    {
        // XSS check
        $input = $request->only('editEtemp', 'difficulty', 'updateESenderProfile', 'updateEAssoWebsite');

        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return response()->json([
                    'status' => 'error',
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
                'editEtemp' => 'required|exists:phishing_emails,id',
                'difficulty' => 'required|string|max:30',
                'updateESenderProfile' => 'required|numeric',
                'updateEAssoWebsite' => 'required|string|max:255'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => __('Error: ') . $e->validator->errors()->first(),
                'errors' => __("Validation failed")
            ], 422);
        }

        try {
            $phishingEmail = PhishingEmail::find($data['editEtemp']);

            $phishingEmail->website = $data['updateEAssoWebsite'];
            $phishingEmail->difficulty = $data['difficulty'];
            $phishingEmail->senderProfile = $data['updateESenderProfile'];

            if ($phishingEmail->save()) {
                log_action("Email template updated successfully (ID: {$data['editEtemp']})");

                return response()->json([
                    'status' => true,
                    'message' => __('Email template updated successfully.')
                ], 200);
            } else {
                log_action("Failed to update email template (ID: {$data['editEtemp']})");

                return response()->json([
                    'status' => false,
                    'message' => __('Failed to update email template.')
                ], 500);
            }
        } catch (\Exception $e) {
            log_action("Exception while updating template: " . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => __('Something went wrong.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteTemplate(Request $request)
    {
        try {
            $request->validate([
                'tempid' => 'required|integer|exists:phishing_emails,id',
                'filelocation' => 'required|string'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => __('Error: ') . $e->validator->errors()->first(),
                'errors' => "Validation Error"
            ], 422);
        }

        $company_id = Auth::user()->company_id;
        $tempid = $request->input('tempid');
        $filelocation = $request->input('filelocation');
        $fileAbsolutePath = storage_path('app/public/' . $filelocation);

        try {
            $isDeleted = PhishingEmail::where('id', $tempid)->delete();

            // Delete related Campaigns
            Campaign::where('phishing_material', $tempid)
                ->where('company_id', $company_id)
                ->delete();

            CampaignLive::where('phishing_material', $tempid)
                ->where('company_id', $company_id)
                ->delete();

            // Delete file if exists
            if (File::exists($fileAbsolutePath)) {
                File::delete($fileAbsolutePath);
            }

            if ($isDeleted) {
                log_action("Email Template (ID: $tempid) deleted successfully");

                return response()->json([
                    'status' => true,
                    'message' => __('Email Template deleted successfully.')
                ], 200);
            } else {
                log_action("Failed to delete Email Template (ID: $tempid)");

                return response()->json([
                    'status' => false,
                    'message' => __('Failed to delete Email Template.')
                ], 500);
            }
        } catch (\Exception $e) {
            log_action("Exception while deleting template: " . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => __('Something went wrong.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addEmailTemplate(Request $request)
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
                return response()->json([
                    'status' => true,
                    'message' => __('Email Template Added Successfully!')
                ], 201); // Created
            } else {
                log_action("Failed to add email template");
                return response()->json([
                    'status' => false,
                    'message' => __('Failed to add Email Template.')
                ], 500); // Internal Server Error
            }
        } catch (\Exception $e) {
            log_action("Failed to add email template");
            return response()->json([
                'status' => false,
                'message' => __('Something went wrong: ') . $e->getMessage()
            ], 500); // Internal Server Error
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
                    'status' => true,
                    'message' => $response->body(),
                ]);
            }

            $html = $response['choices'][0]['message']['content'];

            log_action("Email template generated using AI on topic of prompt: {$prompt}");

            return response()->json([
                'status' => true,
                'html' => $html,
                "message" => __("Successfully fetch message")
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => __('Error: ') . $e->getMessage()
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
}
