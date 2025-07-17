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
use App\Models\WhiteLabelledCompany;
use Illuminate\Support\Facades\Mail;
use App\Models\BlueCollarTrainingUser;
use Illuminate\Support\Facades\Session;
use App\Services\CheckWhitelabelService;
use App\Mail\LearnerSessionRegenerateMail;
use App\Models\ScormAssignedUser;
use Illuminate\Validation\ValidationException;

class ApiLearnControlller extends Controller
{
    public function loginWithToken(Request $request)
    {
        try {
            $token = $request->query('token');

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token is required!'
                ], 422);
            }

            $session = DB::table('learnerloginsession')->where('token', $token)->orderBy('created_at', 'desc') // Ensure the latest session is checked
                ->first();
            if (!$session || now()->greaterThan(Carbon::parse($session->expiry))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your training session has expired!'
                ], 422);
            }

            // Decrypt the email
            $userEmail = decrypt($session->token);

            Session::put('token', $token);

            $isNormalEmployee = Users::where('user_email', $userEmail)->exists();

            if ($isNormalEmployee == 1) {
                $employeeType = 'normal';
                $userName = Users::where('user_email', $userEmail)->value('user_name');
            } else {
                $employeeType = 'bluecollar';
                $userName = BlueCollarEmployee::where('whatsapp', $userEmail)->value('user_name');
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'email' => $userEmail,
                    'employee_type' => $employeeType,
                    'user_name' => $userName,
                ],
                'message' => 'You can Login now'
            ], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
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
                    'success' => false,
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
                    'success' => false,
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
            return response()->json(['success' => true, 'message' => 'Mail sent successfully'], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
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
                    'success' => false,
                    'message' => 'No user found with this email.'
                ], 404);
            }

            $assignedTrainings = TrainingAssignedUser::with('trainingData')
                ->where('user_email', $request->email)
                ->where('completed', 0)->get();

            $completedTrainings = TrainingAssignedUser::with('trainingData')
                ->where('user_email', $request->email)
                ->where('completed', 1)->get();

            $inProgressTrainings = TrainingAssignedUser::with('trainingData')
                ->where('user_email', $request->email)
                ->where('training_started', 1)
                ->where('completed', 0)->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'email' => $request->email,
                    'assigned_trainings' => $assignedTrainings,
                    'completed_trainings' => $completedTrainings,
                    'in_progress_trainings' => $inProgressTrainings,
                    'total_trainings' => TrainingAssignedUser::with('trainingData')
                        ->where('user_email', $request->email)->count(),
                    'total_assigned_trainings' => $assignedTrainings->count(),
                    'total_completed_trainings' => $completedTrainings->count(),
                    'total_in_progress_trainings' => $inProgressTrainings->count(),
                    'avg_in_progress_trainings' => round(TrainingAssignedUser::with('trainingData')
                        ->where('user_email', $request->email)
                        ->where('training_started', 1)
                        ->where('completed', 0)->avg('personal_best')),
                ],
                'message' => 'Courses fetched successfully for ' . $request->email
            ], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
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
                    'success' => false,
                    'message' => 'No blue collar employee found with this WhatsApp number.'
                ], 404);
            }
            $assignedTrainings = BlueCollarTrainingUser::with('trainingData')
                ->where('user_whatsapp', $request->user_whatsapp)
                ->where('completed', 0)->get();

            $completedTrainings = BlueCollarTrainingUser::with('trainingData')
                ->where('user_whatsapp', $request->user_whatsapp)
                ->where('completed', 1)->get();

            $inProgressTrainings = BlueCollarTrainingUser::with('trainingData')
                ->where('user_whatsapp', $request->user_whatsapp)
                ->where('training_started', 1)
                ->where('completed', 0)->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'user_whatsapp' => $request->user_whatsapp,
                    'assigned_trainings' => $assignedTrainings,
                    'completed_trainings' => $completedTrainings,
                    'in_progress_trainings' => $inProgressTrainings,
                    'total_trainings' => BlueCollarTrainingUser::with('trainingData')
                        ->where('user_whatsapp', $request->user_whatsapp)->count(),
                    'total_assigned_trainings' => $assignedTrainings->count(),
                    'total_completed_trainings' => $completedTrainings->count(),
                    'total_in_progress_trainings' => $inProgressTrainings->count(),
                    'avg_in_progress_trainings' => round(BlueCollarTrainingUser::with('trainingData')
                        ->where('user_whatsapp', $request->user_whatsapp)
                        ->where('training_started', 1)
                        ->where('completed', 0)->avg('personal_best')),
                ],
                'message' => 'Courses fetched successfully for blue collar employee'
            ], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
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
                if (!$rowData) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Training not found.'
                    ], 404);
                }
                $user = $rowData->user_whatsapp;
            } else {
                $rowData = TrainingAssignedUser::with('trainingData')->find($row_id);
                if (!$rowData) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Training not found.'
                    ], 404);
                }
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

                    $isWhitelabeled = new CheckWhitelabelService($rowData->company_id);
                    if ($isWhitelabeled->isCompanyWhitelabeled()) {
                        $whitelabelData = $isWhitelabeled->getWhiteLabelData();
                        $companyName = $whitelabelData->company_name;
                        $companyLogo = env('CLOUDFRONT_URL') . $whitelabelData->dark_logo;
                        $favIcon = env('CLOUDFRONT_URL') . $whitelabelData->favicon;
                        $isWhitelabeled->updateSmtpConfig();
                    } else {
                        $companyName = env('APP_NAME');
                        $companyLogo = env('CLOUDFRONT_URL') . '/assets/images/simu-logo-dark.png';
                        $favIcon = env('CLOUDFRONT_URL') . '/assets/images/simu-icon.png';
                    }

                    $mailData = [
                        'user_name' => $rowData->user_name,
                        'training_name' => $rowData->trainingData->name,
                        'training_score' => $request->trainingScore,
                        'company_name' => $companyName,
                        'logo' => $companyLogo
                    ];

                    $pdfContent = $this->generateCertificatePdf($rowData->user_name, $rowData->trainingData->name, $rowData->training, $rowData->completion_date, $rowData->user_email, $companyLogo, $favIcon);


                    Mail::to($user)->send(new TrainingCompleteMail($mailData, $pdfContent));

                    log_action("{$user} scored {$request->trainingScore}% in training", 'learner', 'learner');
                }
            }
            return response()->json(['success' => true, 'message' => 'Score updated'], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateScormTrainingScore(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'scormTrainingScore' => 'required|integer',
                'encoded_id' => 'required',
            ]);

            $row_id = base64_decode($request->encoded_id);

            if (Session::has('bluecollar')) {
                $rowData = BlueCollarTrainingUser::with('trainingData')->find($row_id);
                if (!$rowData) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Training not found.'
                    ], 404);
                }
                $user = $rowData->user_whatsapp;
            } else {
                $rowData = ScormAssignedUser::with('scormTrainingData')->find($row_id);
                if (!$rowData) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Training not found.'
                    ], 404);
                }
                $user = $rowData->user_email;
            }

            if ($rowData && $request->scormTrainingScore > $rowData->personal_best) {
                // Update the column if the current value is greater
                $rowData->personal_best = $request->scormTrainingScore;
                $rowData->save();

                setCompanyTimezone($rowData->company_id);

                log_action("{$user} scored {$request->scormTrainingScore}% in training", 'learner', 'learner');

                $passingScore = (int)$rowData->scormTrainingData->passing_score;

                if ($request->scormTrainingScore >= $passingScore) {
                    $rowData->completed = 1;
                    $rowData->completion_date = now()->format('Y-m-d');
                    $rowData->save();

                    // Send email

                    $isWhitelabeled = new CheckWhitelabelService($rowData->company_id);
                    if ($isWhitelabeled->isCompanyWhitelabeled()) {
                        $whitelabelData = $isWhitelabeled->getWhiteLabelData();
                        $companyName = $whitelabelData->company_name;
                        $companyLogo = env('CLOUDFRONT_URL') . $whitelabelData->dark_logo;
                        $favIcon = env('CLOUDFRONT_URL') . $whitelabelData->favicon;
                        $isWhitelabeled->updateSmtpConfig();
                    } else {
                        $companyName = env('APP_NAME');
                        $companyLogo = env('CLOUDFRONT_URL') . '/assets/images/simu-logo-dark.png';
                        $favIcon = env('CLOUDFRONT_URL') . '/assets/images/simu-icon.png';
                    }

                    $mailData = [
                        'user_name' => $rowData->user_name,
                        'training_name' => $rowData->scormTrainingData->name,
                        'training_score' => $request->scormTrainingScore,
                        'company_name' => $companyName,
                        'logo' => $companyLogo
                    ];

                    $pdfContent = $this->generateScormCertificatePdf($rowData->user_name, $rowData->scormTrainingData->name, $rowData->scorm, $rowData->completion_date, $rowData->user_email, $companyLogo, $favIcon);


                    Mail::to($user)->send(new TrainingCompleteMail($mailData, $pdfContent));

                    log_action("{$user} scored {$request->scormTrainingScore}% in training", 'learner', 'learner');
                }
            }
            return response()->json(['success' => true, 'message' => 'Score updated'], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generateCertificatePdf($name, $trainingModuleName, $trainingId, $date, $userEmail, $logo, $favIcon)
    {
        $certificateId = $this->getCertificateId($userEmail, $trainingId);
        if (!$certificateId) {
            $certificateId = $this->generateCertificateId();
            $this->storeCertificateId($userEmail, $certificateId, $trainingId);
        }

        $pdf = new Fpdi();
        $pdf->AddPage('L', 'A4');
        $pdf->setSourceFile(resource_path('templates/design.pdf'));
        $template = $pdf->importPage(1);
        $pdf->useTemplate($template);

        // Truncate name if too long
        if (strlen($name) > 15) {
            $name = mb_substr($name, 0, 12) . '...';
        }

        // Add user name
        $pdf->SetFont('Helvetica', '', 50);
        $pdf->SetTextColor(47, 40, 103);
        $pdf->SetXY(100, 115);
        $pdf->Cell(0, 10, ucwords($name), 0, 1, 'L');

        // Add training module
        $pdf->SetFont('Helvetica', '', 16);
        $pdf->SetTextColor(169, 169, 169);
        $pdf->SetXY(100, 135);
        $pdf->Cell(210, 10, "For completing $trainingModuleName", 0, 1, 'L');

        // Add date and certificate ID
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->SetXY(240, 165);
        $pdf->Cell(50, 10, "Completion date: $date", 0, 0, 'R');

        $pdf->SetXY(240, 10);
        $pdf->Cell(50, 10, "Certificate ID: $certificateId", 0, 0, 'R');

        if ($logo || file_exists($logo)) {
            // 1. Top-left corner (e.g., branding)
            $pdf->Image($logo, 100, 12, 50); // X=15, Y=12, Width=40mm           
        }

        // 2. Bottom-center badge
        $pdf->Image($favIcon, 110, 163, 15, 15);

        return $pdf->Output('S');
    }

    public function generateScormCertificatePdf($name, $scormName, $scorm, $date, $userEmail, $logo, $favIcon)
    {
        $certificateId = $this->getScormCertificateId($userEmail, $scorm);
        if (!$certificateId) {
            $certificateId = $this->generateCertificateId();
            $this->storeScormCertificateId($userEmail, $certificateId, $scorm);
        }

        $pdf = new Fpdi();
        $pdf->AddPage('L', 'A4');
        $pdf->setSourceFile(resource_path('templates/design.pdf'));
        $template = $pdf->importPage(1);
        $pdf->useTemplate($template);

        // Truncate name if too long
        if (strlen($name) > 15) {
            $name = mb_substr($name, 0, 12) . '...';
        }

        // Add user name
        $pdf->SetFont('Helvetica', '', 50);
        $pdf->SetTextColor(47, 40, 103);
        $pdf->SetXY(100, 115);
        $pdf->Cell(0, 10, ucwords($name), 0, 1, 'L');

        // Add training module
        $pdf->SetFont('Helvetica', '', 16);
        $pdf->SetTextColor(169, 169, 169);
        $pdf->SetXY(100, 135);
        $pdf->Cell(210, 10, "For completing $scormName", 0, 1, 'L');

        // Add date and certificate ID
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->SetXY(240, 165);
        $pdf->Cell(50, 10, "Completion date: $date", 0, 0, 'R');

        $pdf->SetXY(240, 10);
        $pdf->Cell(50, 10, "Certificate ID: $certificateId", 0, 0, 'R');

        if ($logo || file_exists($logo)) {
            // 1. Top-left corner (e.g., branding)
            $pdf->Image($logo, 100, 12, 50); // X=15, Y=12, Width=40mm           
        }

        // 2. Bottom-center badge
        $pdf->Image($favIcon, 110, 163, 15, 15);

        return $pdf->Output('S');
    }

    private function getCertificateId($userEmail, $trainingId)
    {
        // Check the database for an existing certificate ID for this user and training module
        $certificate = TrainingAssignedUser::where('training', $trainingId)
            ->where('user_email', $userEmail)
            ->first();

        return $certificate ? $certificate->certificate_id : null;
    }

    private function getScormCertificateId($userEmail, $scorm)
    {
        // Check the database for an existing certificate ID for this user and training module
        $certificate = ScormAssignedUser::where('scorm', $scorm)
            ->where('user_email', $userEmail)
            ->first();

        return $certificate ? $certificate->certificate_id : null;
    }

    private function generateCertificateId()
    {
        // Generate a unique random ID. You can adjust the format as needed.
        return strtoupper(uniqid('CERT-'));
    }

    private function storeCertificateId($userEmail, $certificateId, $trainingId)
    {
        // Find the existing record based on training module and userEmail
        $trainingAssignedUser = TrainingAssignedUser::where('training', $trainingId)
            ->where('user_email', $userEmail)
            ->first();

        // Check if the record was found
        if ($trainingAssignedUser) {
            // Update only the certificate_id (no need to touch campaign_id)
            $trainingAssignedUser->update([
                'certificate_id' => $certificateId,
            ]);
        }
    }

    private function storeScormCertificateId($userEmail, $certificateId, $scorm)
    {
        $scormAssignedUser = ScormAssignedUser::where('scorm', $scorm)
            ->where('user_email', $userEmail)
            ->first();

        if ($scormAssignedUser) {
            // Update only the certificate_id (no need to touch campaign_id)
            $scormAssignedUser->update([
                'certificate_id' => $certificateId,
            ]);
        }
    }

    public function downloadTrainingCertificate(Request $request)
    {
        $request->validate([
            'user_name' => 'required|string|max:255',
            'training_name' => 'required|string',
            'training_id' => 'required|integer',
            'completion_date' => 'required|date',
            'user_email' => 'required|email',
        ]);

        $name = $request->user_name;
        $trainingModuleName = $request->training_name;
        $trainingId = $request->training_id;
        $date = Carbon::parse($request->completion_date)->format('d F, Y');
        $userEmail = $request->user_email;

        $companyId = Users::where('user_email', $userEmail)->value('company_id');

        $isWhitelabeled = new CheckWhitelabelService($companyId);
        if ($isWhitelabeled->isCompanyWhitelabeled()) {
            $whitelabelData = $isWhitelabeled->getWhiteLabelData();
            // $companyName = $whitelabelData->company_name;
            $companyLogo = env('CLOUDFRONT_URL') . $whitelabelData->dark_logo;
            $favIcon = env('CLOUDFRONT_URL') . $whitelabelData->favicon;
            $isWhitelabeled->updateSmtpConfig();
        } else {
            // $companyName = env('APP_NAME');
            $companyLogo = env('CLOUDFRONT_URL') . '/assets/images/simu-logo-dark.png';
            $favIcon = env('CLOUDFRONT_URL') . '/assets/images/simu-icon.png';
        }


        // Check if the certificate ID already exists for this user and training module
        $certificateId = $this->getCertificateId($userEmail, $trainingId);

        // If the certificate ID doesn't exist, generate a new one
        if (!$certificateId) {
            $certificateId = $this->generateCertificateId();
            $this->storeCertificateId($userEmail, $certificateId, $trainingId);
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
        $pdf->Cell(210, 10, "For completing $trainingModuleName", 0, 1, 'L');

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

     public function downloadScormCertificate(Request $request)
    {
        $request->validate([
            'user_name' => 'required|string|max:255',
            'scorm_name' => 'required|string',
            'scorm_id' => 'required|integer',
            'completion_date' => 'required|date',
            'user_email' => 'required|email',
        ]);

        $name = $request->user_name;
        $scormName = $request->scorm_name;
        $scorm = $request->scorm_id;
        $date = Carbon::parse($request->completion_date)->format('d F, Y');
        $userEmail = $request->user_email;

        $companyId = Users::where('user_email', $userEmail)->value('company_id');

        $isWhitelabeled = new CheckWhitelabelService($companyId);
        if ($isWhitelabeled->isCompanyWhitelabeled()) {
            $whitelabelData = $isWhitelabeled->getWhiteLabelData();
            // $companyName = $whitelabelData->company_name;
            $companyLogo = env('CLOUDFRONT_URL') . $whitelabelData->dark_logo;
            $favIcon = env('CLOUDFRONT_URL') . $whitelabelData->favicon;
            $isWhitelabeled->updateSmtpConfig();
        } else {
            // $companyName = env('APP_NAME');
            $companyLogo = env('CLOUDFRONT_URL') . '/assets/images/simu-logo-dark.png';
            $favIcon = env('CLOUDFRONT_URL') . '/assets/images/simu-icon.png';
        }


        // Check if the certificate ID already exists for this user and training module
        $certificateId = $this->getScormCertificateId($userEmail, $scorm);

        // If the certificate ID doesn't exist, generate a new one
        if (!$certificateId) {
            $certificateId = $this->generateCertificateId();
            $this->storeScormCertificateId($userEmail, $certificateId, $scorm);
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
        $pdf->Cell(210, 10, "For completing $scormName", 0, 1, 'L');

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

    public function fetchNormalEmpScormTrainings(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,user_email',
            ]);

            $email = $request->query('email');

            $assignedTrainings = ScormAssignedUser::with('scormTrainingData')
                ->where('user_email', $request->email)
                ->where('completed', 0)->get();

            $completedTrainings = ScormAssignedUser::with('scormTrainingData')
                ->where('user_email', $request->email)
                ->where('completed', 1)->get();

            $inProgressTrainings = ScormAssignedUser::with('scormTrainingData')
                ->where('user_email', $request->email)
                ->where('scorm_started', 1)
                ->where('completed', 0)->get();

            return response()->json([
                'success' => true,
                'message' => __('Scorm trainings retrieved successfully'),
                'data' => [
                    'email' => $email,
                    'assigned_trainings' => $assignedTrainings,
                    'completed_trainings' => $completedTrainings,
                    'in_progress_trainings' => $inProgressTrainings,
                    'total_trainings' => ScormAssignedUser::with('scormTrainingData')
                        ->where('user_email', $email)->count(),
                    'total_assigned_trainings' => $assignedTrainings->count(),
                    'total_completed_trainings' => $completedTrainings->count(),
                    'total_in_progress_trainings' => $inProgressTrainings->count(),
                    'avg_in_progress_trainings' => round(ScormAssignedUser::with('scormTrainingData')
                        ->where('user_email', $email)
                        ->where('scorm_started', 1)
                        ->where('completed', 0)->avg('personal_best')),
                ]
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }
}
