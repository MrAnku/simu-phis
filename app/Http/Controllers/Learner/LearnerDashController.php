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
        if($hasTraining){
            $companyId = $hasTraining->company_id;
        }
        if($hasPolicy){
            $companyId = $hasPolicy->company_id;
        } 
        
        $isWhitelabeled = new CheckWhitelabelService($companyId);
        if ($isWhitelabeled->isCompanyWhitelabeled()) {
            $whitelabelData = $isWhitelabeled->getWhiteLabelData();
            $learn_domain = "https://" . $whitelabelData->learn_domain;
            $isWhitelabeled->updateSmtpConfig();
        }else{
            $learn_domain = env('SIMUPHISH_LEARNING_URL');
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
        ];

        $trainingModules = TrainingModule::where('company_id', 'default')->inRandomOrder()->take(5)->get();
        // Send email
        Mail::to($request->email)->send(new LearnerSessionRegenerateMail($mailData, $trainingModules));

        // Return success response
        return response()->json(['success' => 'Mail sent successfully']);
    }


    public function startTraining($training_id, $training_lang, $id)
    {
        log_action("Employee started static training", 'learner', 'learner');
        // $training_id = decrypt($training_id);

        return view('learning.training2', ['trainingid' => $training_id, 'training_lang' => $training_lang, 'id' => $id]);

        // return view('learning.training', ['trainingid' => $training_id, 'training_lang' => $training_lang, 'id' => $id]);
    }

    public function loadTraining($training_id, $training_lang)
    {
        // Decode the ID
        $id = decrypt($training_id);
        $training_lang = $training_lang;

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

            // Access the module_language attribute
            $moduleLanguage = $training_lang;

            // You can now use $moduleLanguage as needed
            if ($moduleLanguage !== 'en') {

                $jsonQuiz = json_decode($trainingData->json_quiz, true);

                // $translatedArray = translateArrayValues($jsonQuiz, $moduleLanguage);
                // $translatedJson_quiz = json_encode($translatedArray, JSON_UNESCAPED_UNICODE);

                $translatedJson_quiz = translateQuizUsingAi($trainingData->json_quiz, $moduleLanguage);

                $translatedJson_quiz = json_decode($translatedJson_quiz, true);
                $translatedJson_quiz = changeTranslatedQuizVideoUrl($translatedJson_quiz, $moduleLanguage);

                $trainingData->json_quiz = json_encode($translatedJson_quiz, JSON_UNESCAPED_UNICODE);
                // var_dump($trainingData);
                // echo json_encode($trainingData, JSON_UNESCAPED_UNICODE);
            }

            // Pass data to the view
            return response()->json(['status' => 1, 'jsonData' => $trainingData]);
        }

        if ($trainingData->training_type == 'gamified') {
            $moduleLanguage = $training_lang;

            if ($moduleLanguage !== 'en') {
                $quizInArray = json_decode($trainingData->json_quiz, true);
                $quizInArray['videoUrl'] = changeVideoLanguage($quizInArray['videoUrl'], $moduleLanguage);
                return $this->translateJsonData($quizInArray, $moduleLanguage);
            }

            return response()->json(['status' => 1, 'jsonData' => $trainingData->json_quiz]);
        }
    }

    private function translateJsonData($json, $lang)
    {
        try {
            $prompt = "Translate the following JSON data to " . langName($lang) . " language. The output should only contain JSON data:\n\n" . json_encode($json);

            $response = Http::withOptions(['verify' => false])->withHeaders([
                'Authorization' => 'Bearer ' . env("OPENAI_API_KEY"),
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert JSON translator. Always provide valid JSON data.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 1500,
                'temperature' => 0.7,
            ]);

            if ($response->failed()) {

                log_action("Failed to translate JSON data on topic of prompt: {$prompt}", 'learner', 'learner');

                return response()->json([
                    'status' => 0,
                    'msg' => $response->body(),
                ]);
            }

            $translatedJson = $response['choices'][0]['message']['content'];

            log_action("JSON data translated using AI on topic of prompt: {$prompt}", 'learner', 'learner');

            return response()->json([
                'status' => 1,
                'jsonData' => json_decode($translatedJson, true),
            ]);
        } catch (\Exception $e) {

            log_action("Failed to translate JSON data", 'learner', 'learner');

            return response()->json([
                'status' => 0,
                'msg' => $e->getMessage(),
            ]);
        }
    }

    public function generateCertificatePdf($name, $trainingModule, $trainingId, $date, $userEmail)
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

        // Format and limit name
        if (strlen($name) > 15) {
            $name = mb_substr($name, 0, 12) . '...';
        }

        $pdf->SetFont('Helvetica', '', 50);
        $pdf->SetTextColor(47, 40, 103);
        $pdf->SetXY(100, 115);
        $pdf->Cell(0, 10, ucwords($name), 0, 1, 'L');

        $pdf->SetFont('Helvetica', '', 16);
        $pdf->SetTextColor(169, 169, 169);
        $pdf->SetXY(100, 130);
        $pdf->Cell(210, 10, "For completing $trainingModule", 0, 1, 'L');

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->SetXY(240, 165);
        $pdf->Cell(50, 10, "Completion date: $date", 0, 0, 'R');

        $pdf->SetXY(240, 10);
        $pdf->Cell(50, 10, "Certificate ID: $certificateId", 0, 0, 'R');

        return $pdf->Output('S'); // Output as string
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

                // Send email
                $learnSiteAndLogo = checkWhitelabeled($rowData->company_id);

                $mailData = [
                    'user_name' => $rowData->user_name,
                    'training_name' => $rowData->trainingData->name,
                    'training_score' => $request->trainingScore,
                    'company_name' => $learnSiteAndLogo['company_name'],
                    'logo' => $learnSiteAndLogo['logo']
                ];

                $pdfContent = $this->generateCertificatePdf($rowData->user_name, $rowData->trainingData->name, $rowData->training, $rowData->completion_date, $rowData->user_email);

                $isWhitelabeled = new CheckWhitelabelService($rowData->company_id);
                if ($isWhitelabeled->isCompanyWhitelabeled()) {
                    $isWhitelabeled->updateSmtpConfig();
                }

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
        $pdf->SetXY(100, 130);
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
