<?php

namespace App\Http\Controllers\Learner;

use setasign\Fpdi\Fpdi;
use Illuminate\Http\Request;
use App\Models\AssignedPolicy;
use App\Models\TrainingModule;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpParser\Node\Expr\Assign;
use App\Models\WhiteLabelledSmtp;
use App\Mail\TrainingCompleteMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\TrainingAssignedUser;
use App\Models\WhiteLabelledCompany;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Models\BlueCollarTrainingUser;
use Illuminate\Support\Facades\Session;
use App\Services\CheckWhitelabelService;
use App\Mail\LearnerSessionRegenerateMail;
use App\Models\BlueCollarLearnerLoginSession;

class LearnerDashController extends Controller
{
    public function index()
    {

        $userEmail = session('learner')->login_username;

        $averageScore = DB::table('training_assigned_users')
            ->where('user_email', $userEmail)
            ->avg('personal_best');


        $assignedTrainingCount = TrainingAssignedUser::with('trainingData')
            ->where('user_email', $userEmail)
            ->where('completed', 0)
            ->get();
        // return $assignedTrainingCount;

        $completedTrainingCount = TrainingAssignedUser::with('trainingData')
            ->where('user_email', $userEmail)
            ->where('completed', 1)
            ->get();

        $totalCertificates = TrainingAssignedUser::where('user_email', $userEmail)
            ->where('completed', 1)
            ->where('personal_best', 100)
            ->count();

        // return $assignedTrainingCount;

        return view('learning.dashboard', compact('averageScore', 'assignedTrainingCount', 'completedTrainingCount', 'totalCertificates'));
    }

    public function trainingWithoutLogin(Request $request)
    {
        $token = $request->route('token');


        // Fetch the token and expiry time from learnerloginsession
        $session = DB::table('learnerloginsession')
            ->where('token', $token)
            ->orderBy('created_at', 'desc') // Ensure the latest session is checked
            ->first();

        // Check if session exists and if the token is expired
        if (!$session || now()->greaterThan(Carbon::parse($session->expiry))) {
            return view('learning.login', ['msg' => 'Your training session has expired!']); // Stop execution
        }

        // Decrypt the email
        $userEmail = decrypt($session->token);

        $averageScore = DB::table('training_assigned_users')
            ->where('user_email', $userEmail)
            ->avg('personal_best');

        $assignedTrainingCount = TrainingAssignedUser::with('trainingData')
            ->where('user_email', $userEmail)
            ->where('completed', 0)
            ->get();

        $completedTrainingCount = TrainingAssignedUser::with('trainingData')
            ->where('user_email', $userEmail)
            ->where('completed', 1)
            ->get();

        $totalCertificates = TrainingAssignedUser::where('user_email', $userEmail)
            ->where('completed', 1)
            ->where('personal_best', 100)
            ->count();

        Session::put('token', $token);

        return view('learning.dashboard', compact('averageScore', 'assignedTrainingCount', 'completedTrainingCount', 'totalCertificates', 'userEmail'));
    }

    public function policyWithoutLogin(Request $request)
    {
        $token = $request->route('token');


        // Fetch the token and expiry time from learnerloginsession
        $session = DB::table('learnerloginsession')
            ->where('token', $token)
            ->orderBy('created_at', 'desc') // Ensure the latest session is checked
            ->first();

        // Check if session exists and if the token is expired
        if (!$session || now()->greaterThan(Carbon::parse($session->expiry))) {
            return view('learning.login', ['msg' => 'Your training session has expired!']); // Stop execution
        }

        // Decrypt the email
        $userEmail = decrypt($session->token);

        $assignedPolicies = AssignedPolicy::with('policyData')
            ->where('user_email', $userEmail)
            ->get();

        Session::put('token', $token);
        // Calculate the average score for policies
        return view('learning.policy-dashboard', compact('assignedPolicies', 'userEmail'));
    }

    public function acceptPolicy(Request $request)
    {
        try {
            $encodedId = $request->input('id');
            $policyId = base64_decode($encodedId);

            $assignedPolicy = AssignedPolicy::findOrFail($policyId);
            if (!$assignedPolicy) {
                return response()->json(['success' => false, 'message' => 'Policy not found'], 404);
            }

            $companyId = $assignedPolicy->company_id;
            setCompanyTimezone($companyId);

            $responses = null;
            if ($request->input('responses')) {
                $responses = $request->input('responses');

                if (!is_array($responses)) {
                    return response()->json(['success' => false, 'message' => 'Invalid quiz response format'], 422);
                }
            }

            $assignedPolicy->update([
                'accepted' => 1,
                'accepted_at' => now(),
                'json_quiz_response' => json_encode($responses),
            ]);

            log_action("Policy with ID {$policyId} accepted by user", 'learner', 'learner');

            return response()->json([
                'success' => true,
                'message' => 'Policy accepted and quiz response saved successfully.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error'], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }


    public function startBlueCollarTraining(Request $request)
    {
        $token = $request->route('token');

        // Fetch the token and expiry time from blue_collar_learner_login_sessions

        $session = BlueCollarLearnerLoginSession::where('token', $token)
            ->orderBy('created_at', 'desc') // Ensure the latest session is checked
            ->first();

        // Check if session exists and if the token is expired
        if (!$session || now()->greaterThan(Carbon::parse($session->expiry))) {
            return view('learning.session-expired', ['msg' => 'Your training session has expired!']); // Stop execution
        }

        // Decrypt the whatsapp number
        $userWhatsapp = decrypt($session->token);

        $averageScore = BlueCollarTrainingUser::where('user_whatsapp', $userWhatsapp)
            ->avg('personal_best');

        $assignedTrainingCount = BlueCollarTrainingUser::with('trainingData')
            ->where('user_whatsapp', $userWhatsapp)
            ->where('completed', 0)
            ->get();

        $completedTrainingCount = BlueCollarTrainingUser::with('trainingData')
            ->where('user_whatsapp', $userWhatsapp)
            ->where('completed', 1)
            ->get();

        $totalCertificates = BlueCollarTrainingUser::where('user_whatsapp', $userWhatsapp)
            ->where('completed', 1)
            ->where('personal_best', 100)
            ->count();

        Session::put('token', $token);
        Session::put('bluecollar', true);

        return view('learning.dashboard', compact('averageScore', 'assignedTrainingCount', 'completedTrainingCount', 'totalCertificates', 'userWhatsapp'));
    }

    public function createNewToken(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $hasTraining = TrainingAssignedUser::where('user_email', $request->email)->first();

        $hasPolicy = AssignedPolicy::where('user_email', $request->email)->first();

        if (!$hasTraining && !$hasPolicy) {
            return response()->json(['error' => 'No training or policy has been assigned to this email.'], 500);
        }

        // delete old generated tokens from db
        DB::table('learnerloginsession')->where('email', $request->email)->delete();

        // Encrypt email to generate token
        $token = encrypt($request->email);
        if ($hasTraining) {
            $companyId = $hasTraining->company_id;
        }
        if ($hasPolicy) {
            $companyId = $hasPolicy->company_id;
        }

        $isWhitelabeled = new CheckWhitelabelService($companyId);
        if ($isWhitelabeled->isCompanyWhitelabeled()) {
            $whitelabelData = $isWhitelabeled->getWhiteLabelData();
            $learn_domain = "https://" . $whitelabelData->learn_domain;
            $isWhitelabeled->updateSmtpConfig();
            $companyName = $whitelabelData->company_name;
            $companyDarkLogo = env('CLOUDFRONT_URL') . $whitelabelData->dark_logo;
        } else {
            $isWhitelabeled->clearSmtpConfig();
            $learn_domain = env('SIMUPHISH_LEARNING_URL');
            $companyName = env('APP_NAME');
            $companyDarkLogo = env('CLOUDFRONT_URL') . '/assets/images/simu-logo-dark.png';
        }


        if ($hasPolicy && !$hasTraining) {
            $learning_dashboard_link = $learn_domain . '/policies/' . $token;
        } else {
            $learning_dashboard_link = $learn_domain . '/training-dashboard/' . $token;
        }

        // Insert new record into the database
        $inserted = DB::table('learnerloginsession')->insert([
            'email' => $request->email,
            'token' => $token,
            'expiry' => now()->addHours(24),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Check if the record was inserted successfully
        if (!$inserted) {
            return response()->json(['error' => 'Failed to create token'], 500);
        }

        // Prepare email data
        $mailData = [
            'learning_site' => $learning_dashboard_link,
            'company_name' => $companyName,
            'company_dark_logo' => $companyDarkLogo,
            'company_id' => $companyId,
        ];

        $trainingModules = TrainingModule::where('company_id', 'default')->inRandomOrder()->take(5)->get();
        // Send email
        Mail::to($request->email)->send(new LearnerSessionRegenerateMail($mailData, $trainingModules));

        // Return success response
        return response()->json(['success' => 'Mail sent successfully']);
    }


    public function startTraining($training_id, $training_lang, $id)
    {
        TrainingAssignedUser::where('id', base64_decode($id))->update(['training_started' => 1]);
        log_action("Employee started static training", 'learner', 'learner');
        // $training_id = decrypt($training_id);

        return view('learning.training2', ['trainingid' => $training_id, 'training_lang' => $training_lang, 'id' => $id]);

        // return view('learning.training', ['trainingid' => $training_id, 'training_lang' => $training_lang, 'id' => $id]);
    }

    // public function loadTraining($training_id, $training_lang)
    // {
    //     // Decode the ID
    //     $id = decrypt($training_id);
    //     $training_lang = $training_lang;

    //     // Validate the ID
    //     if ($id === false) {
    //         return response()->json(['status' => 0, 'msg' => 'Invalid training module ID.']);
    //     }

    //     // Fetch the training data
    //     $trainingData = TrainingModule::find($id);

    //     // Check if the training module exists
    //     if (!$trainingData) {
    //         return response()->json(['status' => 0, 'msg' => 'Training Module Not Found']);
    //     }

    //     if ($trainingData->training_type == 'static_training') {

    //         // Access the module_language attribute
    //         $moduleLanguage = $training_lang;

    //         // You can now use $moduleLanguage as needed
    //         if ($moduleLanguage !== 'en') {

    //             $jsonQuiz = json_decode($trainingData->json_quiz, true);

    //             // $translatedArray = translateArrayValues($jsonQuiz, $moduleLanguage);
    //             // $translatedJson_quiz = json_encode($translatedArray, JSON_UNESCAPED_UNICODE);

    //             $translatedJson_quiz = translateQuizUsingAi($trainingData->json_quiz, $moduleLanguage);

    //             $translatedJson_quiz = json_decode($translatedJson_quiz, true);
    //             $translatedJson_quiz = changeTranslatedQuizVideoUrl($translatedJson_quiz, $moduleLanguage);

    //             $trainingData->json_quiz = json_encode($translatedJson_quiz, JSON_UNESCAPED_UNICODE);
    //             // var_dump($trainingData);
    //             // echo json_encode($trainingData, JSON_UNESCAPED_UNICODE);
    //         }

    //         // Pass data to the view
    //         return response()->json(['status' => 1, 'jsonData' => $trainingData]);
    //     }

    //     if ($trainingData->training_type == 'gamified') {
    //         $moduleLanguage = $training_lang;

    //         if ($moduleLanguage !== 'en') {
    //             $quizInArray = json_decode($trainingData->json_quiz, true);
    //             $quizInArray['videoUrl'] = changeVideoLanguage($quizInArray['videoUrl'], $moduleLanguage);
    //             return $this->translateJsonData($quizInArray, $moduleLanguage);
    //         }

    //         return response()->json(['status' => 1, 'jsonData' => $trainingData->json_quiz]);
    //     }
    // }


    public function loadTraining($training_id, $training_lang)
{
    try {
        // Decode the ID
        $id = decrypt($training_id);
        
        // Validate the ID
        if ($id === false) {
            return response()->json(['status' => 0, 'msg' => 'Invalid training module ID.']);
        }

        // Fetch the training data
        $trainingData = TrainingModule::find($id);

        // Check if the training module exists
        if (!$trainingData) {
            return response()->json(['status' => 0, 'msg' => 'Training Module Not Found']);
        }

        if ($trainingData->training_type == 'static_training') {
            $moduleLanguage = $training_lang;

            if ($moduleLanguage !== 'en') {
                try {
                    $translatedJson_quiz = translateQuizUsingAi($trainingData->json_quiz, $moduleLanguage);
                    $translatedArray = json_decode($translatedJson_quiz, true);
                    
                    if ($translatedArray) {
                        $translatedArray = changeTranslatedQuizVideoUrl($translatedArray, $moduleLanguage);
                        $trainingData->json_quiz = json_encode($translatedArray, JSON_UNESCAPED_UNICODE);
                    }
                } catch (\Exception $e) {
                    Log::error('Translation failed in loadTraining', [
                        'error' => $e->getMessage(),
                        'training_id' => $id,
                        'lang' => $moduleLanguage
                    ]);
                    // Continue with original content if translation fails
                }
            }

            return response()->json(['status' => 1, 'jsonData' => $trainingData]);
        }

        if ($trainingData->training_type == 'gamified') {
            $moduleLanguage = $training_lang;

            if ($moduleLanguage !== 'en') {
                try {
                    $quizInArray = json_decode($trainingData->json_quiz, true);
                    $quizInArray['videoUrl'] = changeVideoLanguage($quizInArray['videoUrl'], $moduleLanguage);
                    return $this->translateJsonData($quizInArray, $moduleLanguage);
                } catch (\Exception $e) {
                    Log::error('Gamified translation failed', [
                        'error' => $e->getMessage(),
                        'training_id' => $id,
                        'lang' => $moduleLanguage
                    ]);
                    return response()->json(['status' => 1, 'jsonData' => $trainingData->json_quiz]);
                }
            }

            return response()->json(['status' => 1, 'jsonData' => $trainingData->json_quiz]);
        }
    } catch (\Exception $e) {
        Log::error('loadTraining failed', [
           'error' => $e->getMessage(),
            'training_id' => $training_id,
            'lang' => $training_lang
        ]);
        return response()->json(['status' => 0, 'msg' => 'An error occurred while loading the training module.']);
    }
}

    // private function translateJsonData($json, $lang)
    // {
    //     try {
    //         $prompt = "Translate the following JSON data to " . langName($lang) . " language. The output should only contain JSON data:\n\n" . json_encode($json);

    //         $response = Http::withOptions(['verify' => false])->withHeaders([
    //             'Authorization' => 'Bearer ' . env("OPENAI_API_KEY"),
    //         ])->post('https://api.openai.com/v1/chat/completions', [
    //             'model' => 'gpt-3.5-turbo',
    //             'messages' => [
    //                 ['role' => 'system', 'content' => 'You are an expert JSON translator. Always provide valid JSON data.'],
    //                 ['role' => 'user', 'content' => $prompt],
    //             ],
    //             'max_tokens' => 1500,
    //             'temperature' => 0.7,
    //         ]);

    //         if ($response->failed()) {

    //             log_action("Failed to translate JSON data on topic of prompt: {$prompt}", 'learner', 'learner');

    //             return response()->json([
    //                 'status' => 0,
    //                 'msg' => $response->body(),
    //             ]);
    //         }

    //         $translatedJson = $response['choices'][0]['message']['content'];

    //         log_action("JSON data translated using AI on topic of prompt: {$prompt}", 'learner', 'learner');

    //         return response()->json([
    //             'status' => 1,
    //             'jsonData' => json_decode($translatedJson, true),
    //         ]);
    //     } catch (\Exception $e) {

    //         log_action("Failed to translate JSON data", 'learner', 'learner');

    //         return response()->json([
    //             'status' => 0,
    //             'msg' => $e->getMessage(),
    //         ]);
    //     }
    // }



    private function translateJsonData($json, $lang)
    {
        try {
            $langName = langName($lang);

            // Debug: Check what language name is being used
            Log::info("Translation attempt", [
                'lang_code' => $lang,
                'lang_name' => $langName,
                'input_json' => $json
            ]);

            // Special handling for Amharic
            $isAmharic = strtolower($langName) === 'amharic' || $lang === 'am' || $lang === 'amh';

            if ($isAmharic) {
                // More specific prompt for Amharic
                $prompt = "Translate the text values in this JSON to Amharic (አማርኛ). " .
                    "Keep the JSON structure exactly the same. Only translate the VALUES, not the KEYS. " .
                    "Use proper Amharic script (Ge'ez script). " .
                    "Return ONLY the JSON, no explanations:\n\n" .
                    json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } else {
                $prompt = "Translate ONLY the text values in the following JSON to {$langName}. " .
                    "Keep all JSON structure and keys exactly the same. " .
                    "Return only valid JSON:\n\n" .
                    json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }

            // Use different models based on language complexity
            $model = $isAmharic ? 'gpt-4' : 'gpt-3.5-turbo';

            Log::info("Using model and prompt", [
                'model' => $model,
                'is_amharic' => $isAmharic,
                'prompt_length' => strlen($prompt)
            ]);

            $response = Http::withOptions(['verify' => false])
                ->timeout(60) // Longer timeout for complex translations
                ->withHeaders([
                    'Authorization' => 'Bearer ' . env("OPENAI_API_KEY"),
                    'Content-Type' => 'application/json',
                ])->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $isAmharic ?
                                'You are an expert Amharic translator. You must translate English text to proper Amharic (አማርኛ) using Ge\'ez script. Return only valid JSON with translated values.' :
                                'You are an expert translator. Return only valid JSON with translated text values. Preserve all JSON structure and keys exactly.'
                        ],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => $isAmharic ? 3000 : 2000,
                    'temperature' => 0.2, // Very low for consistency
                ]);

            // Debug API response
            Log::info("API Response Status", [
                'status' => $response->status(),
                'successful' => $response->successful()
            ]);

            if ($response->failed()) {
                $errorDetails = [
                    'status' => $response->status(),
                    'headers' => $response->headers(),
                    'body' => $response->body(),
                    'lang' => $lang
                ];

                Log::error("Translation API failed", $errorDetails);
                log_action("Failed to translate JSON data for language: {$langName}", 'learner', 'learner');

                return response()->json([
                    'status' => 0,
                    'msg' => 'Translation service failed. Status: ' . $response->status(),
                    'debug' => $errorDetails
                ]);
            }

            $responseData = $response->json();

            // Debug full response
            Log::info("Full API Response", ['response' => $responseData]);

            if (!isset($responseData['choices'][0]['message']['content'])) {
                Log::error("Invalid API response structure", ['response' => $responseData]);
                return response()->json([
                    'status' => 0,
                    'msg' => 'Invalid response structure from translation service',
                    'debug' => $responseData
                ]);
            }

            $translatedContent = trim($responseData['choices'][0]['message']['content']);

            // Debug raw translated content
            Log::info("Raw translated content", [
                'content' => $translatedContent,
                'length' => strlen($translatedContent)
            ]);

            // Clean up the response more aggressively
            $translatedContent = preg_replace('/^```json\s*/i', '', $translatedContent);
            $translatedContent = preg_replace('/^```\s*/i', '', $translatedContent);
            $translatedContent = preg_replace('/\s*```$/i', '', $translatedContent);

            // Remove any explanatory text before/after JSON
            if (preg_match('/\{.*\}/s', $translatedContent, $matches)) {
                $translatedContent = $matches[0];
            }

            Log::info("Cleaned translated content", [
                'content' => $translatedContent
            ]);

            // Validate JSON
            $translatedData = json_decode($translatedContent, true);
            $jsonError = json_last_error();

            if ($jsonError !== JSON_ERROR_NONE) {
                Log::error("JSON decode error", [
                    'error' => json_last_error_msg(),
                    'error_code' => $jsonError,
                    'content' => $translatedContent,
                    'lang' => $lang
                ]);

                // Try to fix common JSON issues
                $fixedContent = $this->attemptJsonFix($translatedContent);
                if ($fixedContent) {
                    $translatedData = json_decode($fixedContent, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        Log::info("JSON fixed successfully");
                    } else {
                        return response()->json([
                            'status' => 0,
                            'msg' => 'Invalid JSON returned: ' . json_last_error_msg(),
                            'debug' => [
                                'original_content' => $translatedContent,
                                'fixed_content' => $fixedContent
                            ]
                        ]);
                    }
                } else {
                    return response()->json([
                        'status' => 0,
                        'msg' => 'Invalid JSON returned: ' . json_last_error_msg(),
                        'debug' => ['content' => $translatedContent]
                    ]);
                }
            }

            // Validate that we actually got translations
            if ($isAmharic && $this->validateAmharicTranslation($json, $translatedData)) {
                Log::info("Amharic translation validation passed");
            } elseif ($isAmharic) {
                Log::warning("Amharic translation may not contain proper Amharic text");
            }

            log_action("JSON data successfully translated to {$langName}", 'learner', 'learner');

            return response()->json([
                'status' => 1,
                'jsonData' => $translatedData,
            ]);
        } catch (\Exception $e) {
            Log::error("Translation exception", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'lang' => $lang,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            log_action("Exception during JSON translation: " . $e->getMessage(), 'learner', 'learner');

            return response()->json([
                'status' => 0,
                'msg' => 'Translation failed: ' . $e->getMessage(),
                'debug' => [
                    'exception_class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ]);
        }
    }

    public function generateCertificatePdf($name, $trainingModule, $trainingId, $date, $userEmail, $logo)
    {
        $certificateId = $this->getCertificateId($trainingModule, $userEmail, $trainingId);
        if (!$certificateId) {
            $certificateId = $this->generateCertificateId();
            $this->storeCertificateId($trainingModule, $userEmail, $certificateId, $trainingId);
        }

        $pdf = new Fpdi();
        $pdf->AddPage('L', 'A4');
        $pdf->setSourceFile(resource_path('templates/design.pdf'));
        $template = $pdf->importPage(1);
        $pdf->useTemplate($template);

        // Name
        if (strlen($name) > 15) {
            $name = mb_substr($name, 0, 12) . '...';
        }

        $pdf->SetFont('Helvetica', '', 50);
        $pdf->SetTextColor(47, 40, 103);
        $pdf->SetXY(100, 115);
        $pdf->Cell(0, 10, ucwords($name), 0, 1, 'L');

        // Training info
        $pdf->SetFont('Helvetica', '', 16);
        $pdf->SetTextColor(169, 169, 169);
        $pdf->SetXY(100, 130);
        $pdf->Cell(210, 10, "For completing $trainingModule", 0, 1, 'L');

        // Date
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->SetXY(240, 165);
        $pdf->Cell(50, 10, "Completion date: $date", 0, 0, 'R');

        // Certificate ID
        $pdf->SetXY(240, 10);
        $pdf->Cell(50, 10, "Certificate ID: $certificateId", 0, 0, 'R');

        // ✅ Insert dynamic logo (bottom left area)
        if ($logo && file_exists($logo)) {
            // You may adjust coordinates (X, Y) and size (W, H) as needed
            $pdf->Image($logo, 90, 150, 30, 30); // X=90, Y=150, Width=30mm, Height=30mm
        }

        return $pdf->Output('S'); // Return as string
    }

    private function attemptJsonFix($content)
    {
        // Remove BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // Fix common JSON issues
        $fixes = [
            // Remove trailing commas
            '/,(\s*[}\]])/m' => '$1',
            // Fix unescaped quotes in strings (basic attempt)
            '/([^\\\\])"([^"]*)"([^,}\]:])/' => '$1\\"$2\\"$3',
        ];

        foreach ($fixes as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        // Test if it's valid now
        json_decode($content);
        return json_last_error() === JSON_ERROR_NONE ? $content : false;
    }

    private function validateAmharicTranslation($original, $translated)
    {
        // Check if the translated content contains Amharic characters
        $hasAmharic = false;

        foreach ($translated as $key => $value) {
            if (is_string($value)) {
                // Check for Ge'ez script characters (Amharic uses Unicode range 1200-137F)
                if (preg_match('/[\x{1200}-\x{137F}]/u', $value)) {
                    $hasAmharic = true;
                    break;
                }
            } elseif (is_array($value)) {
                // Recursively check nested arrays
                if ($this->validateAmharicTranslation([], $value)) {
                    $hasAmharic = true;
                    break;
                }
            }
        }

        return $hasAmharic;
    }

    public function updateTrainingScore(Request $request)
    {
        // Validate the request
        $request->validate([
            'trainingScore' => 'required|integer',
            'id' => 'required',
        ]);

        $row_id = base64_decode($request->id);

        if (Session::has('bluecollar')) {
            $rowData = BlueCollarTrainingUser::with('trainingData')->find($row_id);
            $user = $rowData->user_whatsapp;
        } else {
            $rowData = TrainingAssignedUser::with('trainingData')->find($row_id);
            $user = $rowData->user_email;
        }

        if ($rowData && $request->trainingScore > $rowData->personal_best) {
            // Update the column if the current value is greater
            $rowData->personal_best = $request->trainingScore;
            $rowData->save();

            setCompanyTimezone($rowData->company_id);

            log_action("{$user} scored {$request->trainingScore}% in training", 'learner', 'learner');

            $passingScore = (int)$rowData->trainingData->passing_score;

            if ($request->trainingScore >= $passingScore) {
                $rowData->completed = 1;
                $rowData->completion_date = now()->format('Y-m-d');
                $rowData->save();

                $isWhitelabeled = new CheckWhitelabelService($rowData->company_id);
                if ($isWhitelabeled->isCompanyWhitelabeled()) {
                    
                    $whitelabelData = $isWhitelabeled->getWhiteLabelData();
                    $mailData = [
                        'user_name' => $rowData->user_name,
                        'training_name' => $rowData->trainingData->name,
                        'training_score' => $request->trainingScore,
                        'company_name' => $whitelabelData->company_name,
                        'logo' => env('CLOUDFRONT_URL') . $whitelabelData->dark_logo,
                        'company_id' => $rowData->company_id,
                    ];

                    $isWhitelabeled->updateSmtpConfig();
                } else {
                    $isWhitelabeled->clearSmtpConfig();
                    // return $learnSiteAndLogo['logo'];
                    $mailData = [
                        'user_name' => $rowData->user_name,
                        'training_name' => $rowData->trainingData->name,
                        'training_score' => $request->trainingScore,
                        'company_name' => env('APP_NAME'),
                        'logo' => env('CLOUDFRONT_URL') . '/assets/images/simu-logo-dark.png',
                        'company_id' => $rowData->company_id,
                    ];
                }


                $pdfContent = $this->generateCertificatePdf(
                    $rowData->user_name, 
                    $rowData->trainingData->name, 
                    $rowData->training, 
                    $rowData->completion_date, 
                    $rowData->user_email, 
                    $mailData['logo']
                );



                Mail::to($user)->send(new TrainingCompleteMail($mailData, $pdfContent));

                log_action("{$user} scored {$request->trainingScore}% in training", 'learner', 'learner');
            }
        }
        return response()->json(['message' => 'Score updated']);
    }

    public function downloadCertificate(Request $request)
    {
        $name = $request->input('user_name');
        $trainingModule = $request->input('training_module');
        $trainingId = $request->input('training_id');
        $date = Carbon::parse($request->input('completion_date'))->format('d F, Y');
        $userEmail = $request->input('user_email');


        $companyId = TrainingAssignedUser::where('user_email', $userEmail)->value('company_id');

        $isWhitelabeled = new CheckWhitelabelService($companyId);
        if ($isWhitelabeled->isCompanyWhitelabeled()) {
            $whitelabelData = $isWhitelabeled->getWhiteLabelData();
            // $companyName = $whitelabelData->company_name;
            $companyLogo = env('CLOUDFRONT_URL') . $whitelabelData->dark_logo;
            $favIcon = env('CLOUDFRONT_URL') . $whitelabelData->favicon;
            $isWhitelabeled->updateSmtpConfig();
        } else {
            $isWhitelabeled->clearSmtpConfig();
            // $companyName = env('APP_NAME');
            $companyLogo = env('CLOUDFRONT_URL') . '/assets/images/simu-logo-dark.png';
            $favIcon = env('CLOUDFRONT_URL') . '/assets/images/simu-icon.png';
        }


        // Check if the certificate ID already exists for this user and training module
        $certificateId = $this->getCertificateId($trainingModule, $userEmail, $trainingId);

        // If the certificate ID doesn't exist, generate a new one
        if (!$certificateId) {
            $certificateId = $this->generateCertificateId();
            $this->storeCertificateId($trainingModule, $userEmail, $certificateId, $trainingId);
        }

        $pdf = new \setasign\Fpdi\Fpdi();

        // Load template
        $pdf->AddPage('L', 'A4');
        $pdf->setSourceFile(resource_path('templates/design.pdf'));
        $template = $pdf->importPage(1);
        $pdf->useTemplate($template);

        // Set color and fonts
        $pdf->SetTextColor(26, 13, 171);

        // Limit name length to avoid UI break
        $maxLength = 15; // Adjust based on font size and layout width
        if (strlen($name) > $maxLength) {
            $name = mb_substr($name, 0, $maxLength - 3) . '...';
        }

        // --------------------------
        // 1. NAME
        $pdf->SetFont('Helvetica', '', 50);
        $pdf->SetTextColor(47, 40, 103);
        $pdf->SetXY(100, 115);
        $pdf->Cell(0, 10, ucwords($name), 0, 1, 'L'); // 'L' for left-align

        // --------------------------
        // 2. TRAINING TITLE
        $pdf->SetFont('Helvetica', '', 16);
        $pdf->SetTextColor(169, 169, 169);
        $pdf->SetXY(100, 135);
        $pdf->Cell(210, 10, "For completing $trainingModule", 0, 1, 'L');

        // --------------------------
        // 3. DATE centered below the badge
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->SetXY(240, 165);
        $pdf->Cell(50, 10, "Completion date: $date", 0, 0, 'R');

        // 4. CERTIFICATE ID at top right
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->SetXY(240, 10);
        $pdf->Cell(50, 10, "Certificate ID: $certificateId", 0, 0, 'R');

        if ($companyLogo || file_exists($companyLogo)) {
            // 1. Top-left corner (e.g., branding)
            $pdf->Image($companyLogo, 100, 12, 50); // X=15, Y=12, Width=40mm           
        }

        // 2. Bottom-center badge
        $pdf->Image($favIcon, 110, 163, 15, 15);

        log_action("Employee downloaded training certificate", 'learner', 'learner');

        return response($pdf->Output('S', 'certificate.pdf'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="certificate.pdf"');
    }

    /**
     * Get the certificate ID from the database (if it exists).
     */
    private function getCertificateId($trainingModule, $userEmail, $trainingId)
    {
        // Check the database for an existing certificate ID for this user and training module
        $certificate = TrainingAssignedUser::where('training', $trainingId)
            ->where('user_email', $userEmail)
            ->first();
        return $certificate ? $certificate->certificate_id : null;
    }

    /**
     * Generate a random unique certificate ID.
     */
    private function generateCertificateId()
    {
        // Generate a unique random ID. You can adjust the format as needed.
        return strtoupper(uniqid('CERT-'));
    }

    /**
     * Store the generated certificate ID in the database.
     */
    private function storeCertificateId($trainingModule, $userEmail, $certificateId, $trainingId)
    {
        // Find the existing record based on training module and userEmail
        $assignedUser = TrainingAssignedUser::where('training', $trainingId)
            ->where('user_email', $userEmail)
            ->first();

        // Check if the record was found
        if ($assignedUser) {

            // Update only the certificate_id (no need to touch campaign_id)
            $assignedUser->update([
                'certificate_id' => $certificateId,
            ]);
        }
    }

    public function startAiTraining($topic, $language, $id)
    {
        log_action("AI training started", 'learner', 'learner');
        return view('learning.ai-training2', ['topic' => $topic, 'language' => $language, 'id' => $id]);
        // return view('learning.ai-training', ['topic' => $topic, 'language' => $language, 'id' => $id]);
    }

    public function startGamifiedTraining($training_id, $id, $lang)
    {
        log_action("Gamified training started", 'learner', 'learner');
        $training_id = decrypt($training_id);
        $training = TrainingModule::find($training_id);
        if (!$training) {
            return redirect()->back()->with('error', 'Training module not found');
        }

        return view('learning.gamified-training', compact('training', 'id', 'lang'));
    }

    public function appLangChange($locale)
    {
        // return $locale;
        if (in_array($locale, ['en', 'ar', 'ru'])) {
            session(['locale' => $locale]);
        }
        return redirect()->back();
    }
}
