<?php

namespace App\Http\Controllers\LearnApi;

use App\Models\Users;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\AssignedPolicy;
use App\Models\TrainingModule;
use Illuminate\Support\Carbon;
use App\Mail\TrainingCompleteMail;
use App\Models\BlueCollarEmployee;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\Mail;
use App\Models\BlueCollarTrainingUser;
use Illuminate\Support\Facades\Session;
use App\Services\CheckWhitelabelService;
use App\Mail\LearnerSessionRegenerateMail;
use Illuminate\Validation\ValidationException;

class ApiLearnControlller extends Controller
{
    public function loginWithToken(Request $request)
    {
        try {
            $token = $request->query('token');

            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Token is required!'
                ], 422);
            }

            $session = DB::table('learnerloginsession')->where('token', $token)->orderBy('created_at', 'desc') // Ensure the latest session is checked
                ->first();
            if (!$session || now()->greaterThan(Carbon::parse($session->expiry))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your training session has expired!'
                ], 422);
            }

            // Decrypt the email
            $userEmail = decrypt($session->token);

            $averageScore = DB::table('training_assigned_users')
                ->where('user_email', $userEmail)
                ->avg('personal_best');

            $assignedTraining = TrainingAssignedUser::with('trainingData')
                ->where('user_email', $userEmail)
                ->where('completed', 0)
                ->get();

            $completedTraining = TrainingAssignedUser::with('trainingData')
                ->where('user_email', $userEmail)
                ->where('completed', 1)
                ->get();

            $totalCertificates = TrainingAssignedUser::where('user_email', $userEmail)
                ->where('completed', 1)
                ->where('personal_best', 100)
                ->count();

            Session::put('token', $token);

            return response()->json([
                'status' => true,
                'data' => [
                    'email' => $userEmail,
                    'average_score' => $averageScore,
                    'assigned_training' => $assignedTraining,
                    'completed_training' => $completedTraining,
                    'total_certificates' => $totalCertificates,
                ],
                'message' => 'You can Login now'
            ], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createNewToken(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email']);

            $hasTraining = TrainingAssignedUser::where('user_email', $request->email)->first();

            $hasPolicy = AssignedPolicy::where('user_email', $request->email)->first();

            if (!$hasTraining && !$hasPolicy) {
                return response()->json([
                    'status' => false,
                    'message' => 'No training or policy has been assigned to this email.'
                ], 422);
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
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to create token'
                ], 422);
            }

            // Prepare email data
            $mailData = [
                'learning_site' => $learning_dashboard_link,
                'company_name' => $companyName,
                'company_dark_logo' => $companyDarkLogo
            ];

            $trainingModules = TrainingModule::where('company_id', 'default')->inRandomOrder()->take(5)->get();
            // Send email
            Mail::to($request->email)->send(new LearnerSessionRegenerateMail($mailData, $trainingModules));

            // Return success response
            return response()->json(['status' => true, 'message' => 'Mail sent successfully'], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getNormalEmpTranings(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $user = Users::where('user_email', $request->email)->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'No user found with this email.'
                ], 404);
            }

            $assignedTrainings = TrainingAssignedUser::with('trainingData')
                ->where('user_email', $request->email)
                ->where('completed', 0)->get();

            $completedTrainings = TrainingAssignedUser::with('trainingData')
                ->where('user_email', $request->email)
                ->where('completed', 1)->get();

            return response()->json([
                'status' => true,
                'data' => [
                    'email' => $request->email,
                    'assigned_trainings' => $assignedTrainings,
                    'completed_trainings' => $completedTrainings,
                ],
                'message' => 'Courses fetched successfully for ' . $request->email
            ], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getBlueCollarEmpTranings(Request $request)
    {
        try {
            $request->validate([
                'user_whatsapp' => 'required|integer',
            ]);

            $blueCollarUser = BlueCollarEmployee::where('whatsapp', $request->user_whatsapp)->first();
            if (!$blueCollarUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'No blue collar employee found with this WhatsApp number.'
                ], 404);
            }
            $assignedTrainings = BlueCollarTrainingUser::with('trainingData')
                ->where('user_whatsapp', $request->user_whatsapp)
                ->where('completed', 0)->get();

            $completedTrainings = BlueCollarTrainingUser::with('trainingData')
                ->where('user_whatsapp', $request->user_whatsapp)
                ->where('completed', 1)->get();

            return response()->json([
                'status' => true,
                'data' => [
                    'user_whatsapp' => $request->user_whatsapp,
                    'assigned_trainings' => $assignedTrainings,
                    'completed_trainings' => $completedTrainings,
                ],
                'message' => 'Courses fetched successfully for blue collar employee'
            ], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateTrainingScore(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'trainingScore' => 'required|integer',
                'encoded_id' => 'required',
            ]);

            $row_id = base64_decode($request->encoded_id);

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
            return response()->json(['status' => true, 'message' => 'Score updated'], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
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

    private function getCertificateId($trainingModule, $userEmail, $trainingId)
    {
        // Check the database for an existing certificate ID for this user and training module
        $certificate = TrainingAssignedUser::where('training', $trainingId)
            ->where('user_email', $userEmail)
            ->first();
        return $certificate ? $certificate->certificate_id : null;
    }

    private function generateCertificateId()
    {
        // Generate a unique random ID. You can adjust the format as needed.
        return strtoupper(uniqid('CERT-'));
    }

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
}
