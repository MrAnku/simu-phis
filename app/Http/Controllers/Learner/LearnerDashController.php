<?php

namespace App\Http\Controllers\Learner;

use Illuminate\Http\Request;
use App\Models\TrainingModule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\TrainingAssignedUser;
use Barryvdh\DomPDF\Facade\Pdf;

class LearnerDashController extends Controller
{
    public function index()
    {

        $userEmail = session('learner')->login_username; // Assuming you're using Laravel's built-in authentication

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

        // return $assignedTrainingCount;

        return view('learning.dashboard', compact('averageScore', 'assignedTrainingCount', 'completedTrainingCount', 'totalCertificates'));
    }

    public function startTraining($training_id, $training_lang, $id)
    {
        log_action("Employee started static training", 'learner', 'learner');
        // $training_id = decrypt($training_id);

        return view('learning.training', ['trainingid' => $training_id, 'training_lang'=>$training_lang, 'id'=>$id]);
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

        // Access the module_language attribute
        $moduleLanguage = $training_lang;

        // You can now use $moduleLanguage as needed
        if ($moduleLanguage !== 'en') {

            $jsonQuiz = json_decode($trainingData->json_quiz, true);

            $translatedArray = translateArrayValues($jsonQuiz, $moduleLanguage);
            $translatedJson_quiz = json_encode($translatedArray, JSON_UNESCAPED_UNICODE);
            // var_dump($translatedArray);

            $trainingData->json_quiz = $translatedJson_quiz;
            // var_dump($trainingData);
            // echo json_encode($trainingData, JSON_UNESCAPED_UNICODE);
        }

        // Pass data to the view
        return response()->json(['status' => 1, 'jsonData' => $trainingData]);
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

            if($request->trainingScore == 100){
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

    public function startAiTraining($topic, $language, $id){
        log_action("AI training started", 'learner', 'learner');
        return view('learning.ai-training', ['topic' => $topic, 'language'=>$language, 'id'=>$id]);
    }

    public function startGamifiedTraining($training_id, $id){
        log_action("Gamified training started", 'learner', 'learner');
        $training_id = decrypt($training_id);
        $training = TrainingModule::find($training_id);
        if(!$training){
            return redirect()->back()->with('error', 'Training module not found');
        }
        
        return view('learning.gamified-training', compact('training', 'id'));
    }
}
