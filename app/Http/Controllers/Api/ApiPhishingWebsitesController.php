<?php

namespace App\Http\Controllers\Api;

use Exception;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Jobs\CloneWebsiteJob;
use App\Models\PhishingEmail;
use App\Models\PhishingWebsite;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\WebsiteCloneJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\DomCrawler\Crawler;

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
                ->paginate(9); // Fetch results as a collection

            $default = PhishingWebsite::where('company_id', 'default')->paginate(9);
            $custom = PhishingWebsite::where('company_id', $company_id)->paginate(9);

            return response()->json([
                'success' => true,
                'message' => __('Phishing websites fetched successfully.'),
                'data' => $phishingWebsites,
                'default' => $default,
                'custom' => $custom,
                'domain' => [env('PHISHING_WEBSITE_DOMAIN')]
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

    public function getWebsiteById(Request $request): JsonResponse
    {
        // Manually validate the request
        $id = $request->route('id');

        // Check if campaignId exists
        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => __('Website id is required.')
            ], 400);
        }

        try {
            $website = PhishingWebsite::find($id);

            if (!$website) {
                return response()->json([
                    'success' => false,
                    'message' => __('Website not found.')
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => __('Website template found.'),
                'data' => $website
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }



    public function deleteWebsite(Request $request): JsonResponse
    {
        try {
            $id = $request->route('encodedId');
            $id = base64_decode($id);

            $website = PhishingWebsite::where('id', $id)
                ->where('company_id', Auth::user()->company_id)
                ->first();
            if (!$website) {
                return response()->json([
                    'success' => false,
                    'message' => __('Phishing website not found.')
                ], 404);
            }

            $file = $website->file;


            if ($file && Storage::disk('s3')->exists($file)) {
                Storage::disk('s3')->delete($file);
            }

            PhishingEmail::where('website', $id)->update(['website' => 0]);

            $website->delete();

            log_action("Phishing website deleted successfully (ID: {$id})");

            return response()->json([
                'success' => true,
                'message' => __('Phishing website deleted successfully.')
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function updateWebsite(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'id' => 'required|integer',
                'name' => 'required|string|max:255',
                'file' => 'required|file|mimes:html',
                'domain' => 'required|string|max:255',
            ]);

            $phishingWebsite = PhishingWebsite::find($data['id']);
            if (!$phishingWebsite) {

                return response()->json([
                    'success' => false,
                    'message' => __('Phishing website not found.')
                ], 404);
            }

            //store new file
            $randomName = generateRandom(32);
            $extension = $request->file('file')->getClientOriginalExtension();
            $newFilename = $randomName . '.' . $extension;

            $filePath = $request->file('file')->storeAs('/uploads/phishingMaterial/phishing_websites', $newFilename, 's3');

            PhishingWebsite::where('id', $data['id'])
                ->update([
                    'name' => $data['name'],
                    'file' => "/" . $filePath,
                    'domain' => $data['domain']
                ]);
            log_action("Phishing website updated successfully (ID: {$data['id']})");

            return response()->json([
                'success' => true,
                'message' => __('Phishing website updated successfully.')
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->validator->errors()->first()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
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
                ->paginate(9);

            $default = PhishingWebsite::where('company_id', 'default')
                ->where('name', 'LIKE', '%' . $searchTerm . '%')
                ->paginate(9);
            $custom = PhishingWebsite::where('company_id', $company_id)
                ->where('name', 'LIKE', '%' . $searchTerm . '%')
                ->paginate(9);

            return response()->json([
                'success' => true,
                'message' => __('Search results fetched successfully.'),
                'data' => $phishingWebsites,
                'default' => $default,
                'custom' => $custom,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
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
            // $fullDomain = $subdomain ? $subdomain . '.' . $domain : $domain;
            $fullDomain = $domain;

            $company_id = Auth::user()->company_id;

            // Handle file
            $file = $request->file('webFile');

            $randomName = generateRandom(32); // Generate random name
            $extension = $file->getClientOriginalExtension();
            $newFilename = $randomName . '.' . $extension;

            // Read the uploaded file content
            $htmlContent = file_get_contents($file->getRealPath());

            // Inject tracking code
            $injectedCode = '<script src="https://code.jquery.com/jquery-3.6.1.min.js" integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>
    <script src="js/gz.js"></script>';
            $modifiedContent = str_replace('</html>', $injectedCode . '</html>', $htmlContent);

            // Store the modified file in S3
            $targetDir = '/uploads/phishingMaterial/phishing_websites/' . $newFilename;
            Storage::disk('s3')->put($targetDir, $modifiedContent);


            // Step 6: Save to Database
            $phishingWebsite = new PhishingWebsite();
            $phishingWebsite->name = $validated['webName'];
            $phishingWebsite->file = $targetDir;
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

    public function saveClonedWebsite(Request $request)
    {
        try {
            $validated = $request->validate([
                'webName' => 'required|string|max:255',
                'domain' => 'required|string|max:255',
                'website_url' => 'required|string|url'
            ]);
            $company_id = Auth::user()->company_id;
            $webName = $validated['webName'];
            $domain = $validated['domain'];
            $websiteUrl = $validated['website_url'];

            $websiteContent = file_get_contents($websiteUrl);
            if ($websiteContent === false) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to fetch the website content.')
                ], 400);
            }
            // inject tracking code
            $injectedCode = '<script src="https://code.jquery.com/jquery-3.6.1.min.js" integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>
            <script src="js/gz.js"></script>';
            $modifiedContent = str_replace('</html>', $injectedCode . '</html>', $websiteContent);

            // Save the modified content to a file or database
            $randomName = generateRandom(32);
            $newFilename = $randomName . '.html';
            $filePath = '/uploads/phishingMaterial/phishing_websites/' . $newFilename;
            Storage::disk('s3')->put($filePath, $modifiedContent);
            // Save to database
            $phishingWebsite = new PhishingWebsite();
            $phishingWebsite->name = $webName;
            $phishingWebsite->file = $filePath;
            $phishingWebsite->domain = $domain;
            $phishingWebsite->company_id = $company_id;
            $phishingWebsite->save();
            log_action("Cloned website saved successfully: {$webName}");

            // Return success response

            return response()->json([
                'success' => true,
                'message' => __('Cloned website saved successfully.')
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->validator->errors()->first()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Something went wrong while saving cloned website.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function deleteClonedWebsite(Request $request): JsonResponse
    {
        try {
            $id = $request->route('encodedId');
            if (!$id) {
                return response()->json([
                    'success' => false,
                    'message' => __('Cloned website ID is required')
                ], 422);
            }

            $id = base64_decode($id);

            $clonedWebsite = WebsiteCloneJob::find($id);
            if (!$clonedWebsite) {
                return response()->json([
                    'success' => false,
                    'message' => __('Cloned website not found')
                ], 404);
            }

            $fileUrl = $clonedWebsite->file_url;
            if ($fileUrl) {
                // Parse the S3 path from the file_url (strip domain and leading slash)
                $parsedUrl = parse_url($fileUrl, PHP_URL_PATH);
                $s3Path = ltrim($parsedUrl, '/');


                // delete the main HTML file
                if (Storage::disk('s3')->exists($s3Path)) {
                    Storage::disk('s3')->delete($s3Path);
                }
            }




            // Delete the database record
            $clonedWebsite->delete();

            log_action("Cloned website deleted successfully (ID: {$id})");

            return response()->json([
                'success' => true,
                'message' => __('Cloned Website deleted successfully.')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
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


    public function duplicate(Request $request)
    {
        try {
            if (!$request->route('id')) {
                return response()->json([
                    'success' => false,
                    'message' => __('Phishing Website ID is required')
                ], 422);
            }
            $id = base64_decode($request->route('id'));

            $phishingEmail = PhishingWebsite::where('id', $id)->first();
            if (!$phishingEmail) {
                return response()->json([
                    'success' => false,
                    'message' => __('Phishing Website not found')
                ], 422);
            }

            $duplicateWebsite = $phishingEmail->replicate(['company_id', 'name']);
            $duplicateWebsite->company_id = Auth::user()->company_id;
            $duplicateWebsite->name = $phishingEmail->name . ' (Copy)';

            $duplicateWebsite->save();

            return response()->json([
                'success' => true,
                'message' => __('Phishing Website duplicated successfully'),
                'data' => $duplicateWebsite
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function checkWebsiteForClone(Request $request)
    {
        try {
            $url = $request->input('url');
            if (!$url) {
                return response()->json([
                    'success' => false,
                    'message' => __('URL is required')
                ], 422);
            }

            // Fetch the website content
            $response = Http::timeout(10)->get($url);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to fetch the website.')
                ], 400);
            }

            $html = $response->body();

            // Check if the HTML is too long (e.g., > 500KB)
            if (strlen($html) > 500 * 1024) {
                return response()->json([
                    'success' => false,
                    'message' => __('The website is too large to clone. Please select a simpler page (like a login or input form).')
                ], 400);
            }

            // Initialize Crawler
            $crawler = new Crawler($html);

            // Check for SPA characteristics
            $isLikelySPA = false;
            $spaMessage = '';

            // 1. Check if HTML is minimal (e.g., < 5KB and contains a single div like id="root" or id="app")
            if (strlen($html) < 5 * 1024) {
                $rootDiv = $crawler->filter('div[id="root"], div[id="app"], div[id="__next"]')->count();
                if ($rootDiv > 0) {
                    $isLikelySPA = true;
                    $spaMessage = __('The website appears to be a Single Page Application (e.g., React or Next.js) with minimal initial HTML, which may not be suitable for cloning.');
                }
            }

            // 2. Check for framework signatures (React, Next.js, or React-specific scripts)
            if (!$isLikelySPA && (
                stripos($html, 'data-reactroot') !== false ||
                stripos($html, '__next') !== false ||
                stripos($html, 'react-dom') !== false || // Check for React scripts
                stripos($html, '/static/js/') !== false // Common for bundled JS in SPAs
            )) {
                $isLikelySPA = true;
                $spaMessage = __('The website appears to use a JavaScript framework like React or Next.js, which may not be suitable for cloning.');
            }

            // 3. Check for CSS availability (look for <style> or <link rel="stylesheet">)
            $hasCss = $crawler->filter('style, link[rel="stylesheet"]')->count() > 0;
            if (!$hasCss) {
                $isLikelySPA = true;
                $spaMessage = __('The website lacks inline or linked CSS in the initial HTML, indicating it may be a Single Page Application that relies on JavaScript for styling.');
            }

            // 4. Check for accessibility features (indicative of dynamic JavaScript)
            $hasAccessibilityFeatures = stripos($html, 'ReadSpeaker') !== false ||
                $crawler->filter('[aria-label*="contrast"], [role="button"][aria-label*="text size"]')->count() > 0;
            if ($hasAccessibilityFeatures) {
                $isLikelySPA = true;
                $spaMessage = __('The website includes dynamic accessibility features (e.g., text resizing, contrast toggle), indicating heavy JavaScript usage, which may not be suitable for cloning.');
            }

            

            // If it's an SPA, return early
            if ($isLikelySPA) {
                return response()->json([
                    'success' => false,
                    'message' => $spaMessage
                ], 400);
            }

            // 6. Check for input fields
            $inputCount = $crawler->filter('input')->count();

            // Fallback: If DomCrawler finds no input, try a simple regex search for <input
            if ($inputCount < 1) {
                preg_match_all('/<input\b/i', $html, $matches);
                $inputCount = count($matches[0]);
            }

          

            // If no input fields are found and not a login page, use the original error
            if ($inputCount < 1) {
                return response()->json([
                    'success' => false,
                    'message' => __('No input fields found on the page. Please select a page that asks for user input (e.g., login or form page).')
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => __('Website is suitable for cloning.'),
                'input_fields' => $inputCount,
                'html' => $html,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }



    public function cloneWebsite(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $url = $request->input('url');
        $companyId = Auth::user()->company_id; // Adjust based on your tenancy setup

        // Dispatch the job
        // CloneWebsiteJob::dispatch($url, $companyId);
        WebsiteCloneJob::create([
            'company_id' => $companyId,
            'url' => $url,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Website cloning job has been queued.',
        ]);
    }

    public function getClonedWebsites(Request $request): JsonResponse
    {
        try {
            $companyId = Auth::user()->company_id;
            $clonedWebsites = WebsiteCloneJob::where('company_id', $companyId)
                ->orderBy('created_at', 'desc')
                ->get();
            return response()->json([
                'success' => true,
                'message' => __('Cloned websites fetched successfully.'),
                'data' => $clonedWebsites,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
