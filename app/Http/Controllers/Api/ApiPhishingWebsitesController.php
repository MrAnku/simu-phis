<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PhishingEmail;
use App\Models\PhishingWebsite;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ApiPhishingWebsitesController extends Controller
{
    //
    public function index(): JsonResponse
    {
        try {
            $company_id = Auth::user()->company_id;

            // Get all phishing websites related to the company or default ones
            $phishingWebsites = PhishingWebsite::where('company_id', $company_id)
                ->orWhere('company_id', 'default')
                ->paginate(10); // Fetch results as a collection

            return response()->json([
                'success' => true,
                'message' => __('Phishing websites fetched successfully.'),
                'data' => $phishingWebsites,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to fetch phishing websites.'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAll(): JsonResponse
    {
        try {
            $company_id = Auth::user()->company_id;

            // Get all phishing websites related to the company or default ones
            $phishingWebsites = PhishingWebsite::where('company_id', $company_id)
                ->orWhere('company_id', 'default')
                ->get(); // Fetch results as a collection

            return response()->json([
                'success' => true,
                'message' => __('Phishing websites fetched successfully.'),
                'data' => $phishingWebsites,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to fetch phishing websites.'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function deleteWebsite(Request $request): JsonResponse
    {
        $request->validate([
            'websiteid' => 'required|integer',
            'filename' => 'required|string',
        ]);

        $webid = $request->input('websiteid');
        $filename = $request->input('filename');
        $company_id = Auth::user()->company_id;

        try {
            $deleted = false;

            DB::transaction(function () use (&$deleted, $webid, $filename, $company_id) {
                // Attempt to delete the website record
                $deleted = PhishingWebsite::where('id', $webid)
                    ->where('company_id', $company_id)
                    ->delete();

                if ($deleted) {
                    // Reset references in PhishingEmail table
                    PhishingEmail::where('website', $webid)
                        ->where('company_id', $company_id)
                        ->update(['website' => 0]);

                    // Delete file from storage
                    $filePath = 'uploads/phishingMaterial/phishing_websites/' . $filename;

                    if (Storage::disk('public')->exists($filePath)) {
                        Storage::disk('public')->delete($filePath);
                    }
                }
            });

            if ($deleted) {
                log_action("Phishing website (ID: $webid) deleted by company $company_id");

                return response()->json([
                    'success' => true,
                    'message' => __('Phishing website deleted successfully.')
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('Phishing website not found for the given ID.')
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error("Error deleting phishing website [ID: $webid]: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete phishing website.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }




    public function searchWebsite(Request $request)
    {
        try {
            $request->validate([
                'search' => 'required|string|max:255',
            ]);

            $searchTerm = $request->query('search'); // ✅ Query param se value lena
            $company_id = Auth::user()->company_id;

            $phishingWebsites = PhishingWebsite::where(function ($query) use ($company_id) {
                $query->where('company_id', $company_id)
                    ->orWhere('company_id', 'default');
            })
                ->where(function ($query) use ($searchTerm) {
                    $query->where('name', 'LIKE', '%' . $searchTerm . '%');
                })
                ->paginate(10);

            return response()->json([
                'success' => true,
                'message' => __('Search results fetched successfully.'),
                'data' => $phishingWebsites
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('Something went wrong.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function addPhishingWebsite(Request $request)
    {
        try {
            // Step 1: XSS Protection
            $input = $request->only(['webName', 'subdomain', 'domain']);

            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Invalid input detected (XSS).')
                    ], 400);
                }
            }

            array_walk_recursive($input, function (&$val) {
                $val = strip_tags($val);
            });
            $request->merge($input);

            // Step 2: Validate request
            $validated = $request->validate([
                'webName'   => 'required|string|max:255',
                'webFile'   => 'required|file|mimes:html',
                'subdomain' => 'nullable|string|max:255',
                'domain'    => 'required|string|max:255',
            ]);

            // Step 3: Build full domain
            $subdomain = $request->input('subdomain');
            $domain    = $request->input('domain');
            $fullDomain = $subdomain ? $subdomain . '.' . $domain : $domain;

            $company_id = Auth::user()->company_id;

            // Step 4: File Handling
            $file = $request->file('webFile');
            $randomName = generateRandom(32); // You may want to check this helper
            $extension = $file->getClientOriginalExtension();
            $newFilename = $randomName . '.' . $extension;
            $relativePath = 'uploads/phishingMaterial/phishing_websites/' . $newFilename;

            $file->storeAs('uploads/phishingMaterial/phishing_websites', $newFilename, 'public');

            // Step 5: Inject Tracking Code
            $htmlContent = Storage::disk('public')->get($relativePath);
            $injectedCode = <<<HTML
<script src="https://code.jquery.com/jquery-3.6.1.min.js" integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>
<script src="js/gz.js"></script>
HTML;

            $modifiedContent = str_replace('</html>', $injectedCode . '</html>', $htmlContent);
            Storage::disk('public')->put($relativePath, $modifiedContent);

            // Step 6: Save to Database
            $phishingWebsite = new PhishingWebsite();
            $phishingWebsite->name = $validated['webName'];
            $phishingWebsite->file = $newFilename;
            $phishingWebsite->domain = $fullDomain;
            $phishingWebsite->company_id = $company_id;
            $phishingWebsite->save();

            log_action("New phishing website is added : {$validated['webName']}");

            return response()->json([
                'success' => true,
                'message' => __('New phishing website is added.'),
                'data' => $phishingWebsite
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->validator->errors()->first()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Something went wrong while adding website.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function generateWebsite(Request $request)
    {
        try {
            // Validate input
            $validated = $request->validate([
                'description' => 'required|string',
                'company_name' => 'required|string',
                'logo_url' => 'required|url',
            ]);

            $description = $validated['description'];
            $companyName = $validated['company_name'];
            $logoUrl = $validated['logo_url'];

            // Prepare OpenAI API request
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

            // Check if API request failed
            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => __('OpenAI API call failed.'),
                    'error' => $response->body()
                ], 502);
            }

            $responseData = $response->json();

            // Ensure valid response structure
            if (!isset($responseData['choices'][0]['message']['content'])) {
                return response()->json([
                    'success' => false,
                    'message' => __('Invalid response from OpenAI API.'),
                    'data' => $responseData,
                ], 500);
            }

            $generatedContent = $responseData['choices'][0]['message']['content'];
            $timestamp = time();
            $directory = 'ai_site_temp/site_' . $timestamp;

            // Load template file
            if (!Storage::disk('public')->exists('login_template.html')) {
                return response()->json([
                    'success' => false,
                    'message' => __('Template file not found.'),
                ], 500);
            }

            $template = Storage::disk('public')->get('login_template.html');

            // Replace placeholders
            $finalContent = str_replace(
                ['%company_name%', '%logo_url%', '<!-- Content -->'],
                [$companyName, $logoUrl, $generatedContent],
                $template
            );

            // Save final HTML file
            Storage::disk('public')->put($directory . '/index.html', $finalContent);

            return response()->json([
                'success' => true,
                'message' => __('Website generated successfully.'),
                'url' => Storage::url($directory . '/index.html'),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return validation errors in JSON format
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->validator->errors()->first(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // General exception handler
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }



    public function saveGeneratedSite(Request $request)
    {
        // Validate input data
        $validated = $request->validate([
            'webName' => 'required|string|max:255',
            'domain' => 'required|string',
            'sitePagePath' => 'required|string'
        ]);

        try {
            $company_id = Auth::user()->company_id;

            $webName = $validated['webName'];
            $domain = $validated['domain'];
            $sitePagePath = $validated['sitePagePath'];

            $sourcePath = public_path($sitePagePath);
            $destinationPath = public_path('storage/uploads/phishingMaterial/phishing_websites');

            // Create destination directory if not exists
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }

            $newFileName = Str::random(32) . '.html';

            if (File::exists($sourcePath)) {
                // Move file to the destination
                File::move($sourcePath, $destinationPath . '/' . $newFileName);

                // Save record to database
                $phishingWebsite = new PhishingWebsite();
                $phishingWebsite->name = $webName;
                $phishingWebsite->file = $newFileName;
                $phishingWebsite->domain = $domain;
                $phishingWebsite->company_id = $company_id;
                $phishingWebsite->save();

                log_action("AI generated phishing website added");

                return response()->json([
                    'success' => true,
                    'message' => __('Website saved successfully.'),
                    'data' => [
                        'file' => $newFileName,
                        'path' => 'storage/uploads/phishingMaterial/phishing_websites/' . $newFileName
                    ]
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('Source file not found.')
                ], 404);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return validation errors
            return response()->json([
                'success' => false,
                'message' => __('Validation failed.'),
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            // Catch all other errors
            return response()->json([
                'success' => false,
                'message' => __('An error occurred.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
