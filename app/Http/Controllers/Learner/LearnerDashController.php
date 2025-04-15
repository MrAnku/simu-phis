<?php

namespace App\Http\Controllers\Learner;

use Illuminate\Http\Request;
use App\Models\TrainingModule;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\LearnerSessionRegenerateMail;

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

        return view('learning.dashboard', compact('averageScore', 'assignedTrainingCount', 'completedTrainingCount', 'totalCertificates'));
    }
    public function renewToken(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Encrypt email to generate token
        $token = encrypt($request->email);

        // Construct learning dashboard link
        $learning_dashboard_link = env('SIMUPHISH_LEARNING_URL') . '/training-dashboard/' . $token;

        // Update existing record where email matches
        $updated = DB::table('learnerloginsession')
            ->where('email', $request->email)
            ->update([
                'token' => $token,
                'expiry' => now()->addHours(24), // Change to 'expiry'
                'updated_at' => now()
            ]);


        // return  $learning_dashboard_link;
        // Check if email exists, if not return an error
        if (!$updated) {
            return response()->json(['message' => 'Email not found in database'], 404);
        }

        // Prepare email data
        $mailData = [
            'learning_site' => $learning_dashboard_link,
        ];

        // Send email
        Mail::to($request->email)->send(new LearnerSessionRegenerateMail($mailData));

        // Return success response
        return response()->json(['message' => 'Mail sent successfully', 'token' => $token]);
    }
    public function createNewToken(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $hasTraining = TrainingAssignedUser::where('user_email', $request->email)->exists();

        if (!$hasTraining) {
            return response()->json(['error' => 'No training has been assigned to this email.'], 500);
        }

        // delete old generated tokens from db
        DB::table('learnerloginsession')->where('email', $request->email)->delete();

        // Encrypt email to generate token
        $token = encrypt($request->email);

        // Construct learning dashboard link
        $learning_dashboard_link = env('SIMUPHISH_LEARNING_URL') . '/training-dashboard/' . $token;

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

        // Send email
        Mail::to($request->email)->send(new LearnerSessionRegenerateMail($mailData));

        // Return success response
        return response()->json(['success' => 'Mail sent successfully', 'token' => $token]);
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

    public function updateTrainingScore(Request $request)
    {
        // Validate the request
        $request->validate([
            'trainingScore' => 'required|integer',
            'id' => 'required',
        ]);



        $row_id = base64_decode($request->id);

        $rowData = TrainingAssignedUser::find($row_id);
        if ($rowData && $request->trainingScore > $rowData->personal_best) {
            // Update the column if the current value is greater
            $rowData->personal_best = $request->trainingScore;
            $rowData->save();

            log_action("{$rowData->user_email} scored {$request->trainingScore}% in training", 'learner', 'learner');

            if ($request->trainingScore == 100) {
                $rowData->completed = 1;
                $rowData->completion_date = now()->format('Y-m-d');
                $rowData->save();

                log_action("{$rowData->user_email} scored {$request->trainingScore}% in training", 'learner', 'learner');
            }
        }


        return response()->json(['message' => 'Score updated']);
    }

    public function downloadCertificate(Request $request)
    {
        // Get the necessary input from the request
        $trainingModule = $request->input('training_module');
        $trainingId = $request->input('training_id');
        $completionDate = $request->input('completion_date');
        $username = $request->input('username');

        // Check if the certificate ID already exists for this user and training module
        $certificateId = $this->getCertificateId($trainingModule, $username, $trainingId);

        // If the certificate ID doesn't exist, generate a new one
        if (!$certificateId) {
            $certificateId = $this->generateCertificateId();
            $this->storeCertificateId($trainingModule, $username, $certificateId, $trainingId); // Store the new certificate ID in your database
        }

        // Generate the PDF from the view and include the certificate ID
        $pdf = Pdf::loadView('learning.certificate', compact('trainingModule', 'completionDate', 'username', 'certificateId'));

        // Define the filename with certificate ID
        $fileName = "{$trainingModule}_Certificate_{$certificateId}.pdf";

        log_action("Employee downloaded training certificate", 'learner', 'learner');
        // Return the PDF download response
        return $pdf->download($fileName);
    }

    /**
     * Get the certificate ID from the database (if it exists).
     */
    private function getCertificateId($trainingModule, $username, $trainingId)
    {
        // Check the database for an existing certificate ID for this user and training module
        $certificate = TrainingAssignedUser::where('training', $trainingId)
            ->where('user_email', $username)
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
    private function storeCertificateId($trainingModule, $username, $certificateId, $trainingId)
    {
        // Find the existing record based on training module and username
        $assignedUser = TrainingAssignedUser::where('training', $trainingId)
            ->where('user_email', $username)
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
