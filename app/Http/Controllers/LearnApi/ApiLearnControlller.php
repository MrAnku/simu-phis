<?php

namespace App\Http\Controllers\LearnApi;

use App\Models\Badge;
use App\Models\Users;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\AssignedPolicy;
use App\Models\TrainingModule;
use Illuminate\Support\Carbon;
use App\Models\ScormAssignedUser;
use App\Mail\TrainingCompleteMail;
use App\Models\BlueCollarEmployee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\TrainingAssignedUser;
use App\Models\WhiteLabelledCompany;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use App\Models\BlueCollarTrainingUser;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
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

            $allAssignedTrainings = TrainingAssignedUser::with('trainingData')
                ->where('user_email', $request->email)->get();

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
                    'assigned_trainings' => $allAssignedTrainings,
                    'completed_trainings' => $completedTrainings,
                    'in_progress_trainings' => $inProgressTrainings,
                    'total_trainings' => TrainingAssignedUser::with('trainingData')
                        ->where('user_email', $request->email)->count(),
                    'total_assigned_trainings' => $allAssignedTrainings->count(),
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

                // Assign Grade based on score
                if ($request->trainingScore >= 90) {
                    $rowData->grade = 'A+';
                } elseif ($request->trainingScore >= 80) {
                    $rowData->grade = 'A';
                } elseif ($request->trainingScore >= 70) {
                    $rowData->grade = 'B';
                } elseif ($request->trainingScore >= 60) {
                    $rowData->grade = 'C';
                } else {
                    $rowData->grade = 'D';
                }

                $badge = getMatchingBadge('score', $request->trainingScore);
                // This helper function accepts a criteria type and value, and returns the first matching badge

                if ($badge) {
                    // Decode existing badges (or empty array if null)
                    $existingBadges = json_decode($rowData->badge, true) ?? [];

                    // Avoid duplicates
                    if (!in_array($badge, $existingBadges)) {
                        $existingBadges[] = $badge; // Add new badge
                    }

                    // Save back to the model
                    $rowData->badge = json_encode($existingBadges);
                }
                $rowData->save();

                setCompanyTimezone($rowData->company_id);

                log_action("{$user} scored {$request->trainingScore}% in training", 'learner', 'learner');

                $passingScore = (int)$rowData->trainingData->passing_score;

                if ($request->trainingScore >= $passingScore) {
                    $rowData->completed = 1;
                    $rowData->completion_date = now()->format('Y-m-d');

                    $totalCompletedTrainings = TrainingAssignedUser::where('user_email', $rowData->user_email)
                        ->where('completed', 1)->count();

                    $badge = getMatchingBadge('courses_completed', $totalCompletedTrainings);
                    // This helper function accepts a criteria type and value, and returns the first matching badge

                    if ($badge) {
                        // Decode existing badges (or empty array if null)
                        $existingBadges = json_decode($rowData->badge, true) ?? [];

                        // Avoid duplicates
                        if (!in_array($badge, $existingBadges)) {
                            $existingBadges[] = $badge; // Add new badge
                        }

                        // Save back to the model
                        $rowData->badge = json_encode($existingBadges);
                    }
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

                    $emailFolder = $rowData->user_email;
                    $pdfFileName = 'certificate_' . time() . '.pdf';
                    $relativePath =  'certificates/' . $emailFolder . '/' . $pdfFileName;


                    // Save using Storage
                    Storage::disk('s3')->put($relativePath, $pdfContent);
                    $certificate_full_path = Storage::disk('s3')->path($relativePath);

                    $rowData->certificate_path = '/' . $certificate_full_path;
                    $rowData->save();

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

                // Assign Grade based on score
                if ($request->scormTrainingScore >= 90) {
                    $rowData->grade = 'A+';
                } elseif ($request->scormTrainingScore >= 80) {
                    $rowData->grade = 'A';
                } elseif ($request->scormTrainingScore >= 70) {
                    $rowData->grade = 'B';
                } elseif ($request->scormTrainingScore >= 60) {
                    $rowData->grade = 'C';
                } else {
                    $rowData->grade = 'D';
                }

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

                    $emailFolder = $rowData->user_email;
                    $pdfFileName = 'certificate_' . time() . '.pdf';
                    $relativePath = 'certificates/' . $emailFolder . '/' . $pdfFileName;


                    // Save using Storage
                    Storage::disk('s3')->put($relativePath, $pdfContent);
                    $certificate_full_path = Storage::disk('s3')->path($relativePath);

                    $rowData->certificate_path = '/' . $certificate_full_path;
                    $rowData->save();


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
       
         $scormAssignedUser = ScormAssignedUser::where('scorm', $trainingId)
                ->where('user_email', $userEmail)
                ->first();

            if ($scormAssignedUser) {
                // Update only the certificate_id (no need to touch campaign_id)
                $scormAssignedUser->update([
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
            'training_id' => 'required|integer',
            'user_email' => 'required|email',
        ]);

        $training = TrainingAssignedUser::find($request->training_id);

        if ($training == null) {
            return response()->json([
                'success' => false,
                'message' => __('Training not found.'),
            ], 404);
        }

        if ($training->completed == 0) {
            return response()->json([
                'success' => false,
                'message' => __('Training is not completed yet.'),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => __('Certificate Path retrieved successfully'),
            'data' => [
                'certificate_path' => $training->certificate_path,
            ]
        ], 200);
    }

    public function downloadScormCertificate(Request $request)
    {
        $request->validate([
            'scorm_id' => 'required|integer',
            'user_email' => 'required|email',
        ]);

        $training = ScormAssignedUser::find($request->scorm_id);

        if ($training == null) {
            return response()->json([
                'success' => false,
                'message' => __('Scorm not found.'),
            ], 404);
        }

        if ($training->completed == 0) {
            return response()->json([
                'success' => false,
                'message' => __('Scorm is not completed yet.'),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => __('Certificate Path retrieved successfully'),
            'data' => [
                'certificate_path' => $training->certificate_path,
            ]
        ], 200);
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

    public function fetchScoreBoard(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,user_email',
            ]);

            $allAssignedTrainingMods = TrainingAssignedUser::where('user_email', $request->email)->get();

            $allAssignedScorms = ScormAssignedUser::where('user_email', $request->email)->get();

            foreach ($allAssignedTrainingMods as $trainingMod) {
                $allAssignedTrainings[] = [
                    'training_name' => $trainingMod->trainingData->name,
                    'score' => $trainingMod->personal_best,
                    'completed' => $trainingMod->completed ? 'Yes' : 'No',
                    'training_due_date' => $trainingMod->training_due_date,
                    'completion_date' => $trainingMod->completion_date,
                    'training_type' => 'Training Module',
                ];
            }

            foreach ($allAssignedScorms as $scorm) {
                $allAssignedTrainings[] = [
                    'training_name' => $scorm->scormTrainingData->name,
                    'score' => $scorm->personal_best,
                    'completed' => $scorm->completed ? 'Yes' : 'No',
                    'training_due_date' => $scorm->scorm_due_date,
                    'completion_date' => $scorm->completion_date,
                    'training_type' => 'Scorm',
                ];
            }

            return response()->json([
                'success' => true,
                'message' => __('Scoreboard retrieved successfully'),
                'data' => [
                    'scoreboard' => $allAssignedTrainings ?? [],
                    'total_trainings' => count($allAssignedTrainings),
                    'avg_score' => count($allAssignedTrainings) > 0 ? round(array_sum(array_column($allAssignedTrainings, 'score')) / count($allAssignedTrainings)) : 0,
                ]
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function fetchLeaderBoard(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,user_email',
            ]);

            $currentUserEmail = $request->email;

            $trainingUsers = TrainingAssignedUser::all();
            $scormUsers = ScormAssignedUser::all();

            $allUsers = $trainingUsers->merge($scormUsers);

            $grouped = $allUsers->groupBy('user_email')->map(function ($group, $email) use ($currentUserEmail) {
                $average = $group->avg('personal_best');

                return [
                    'email' => $email,
                    'name' => strtolower($email) == strtolower($currentUserEmail) ? 'You' : ($group->first()->user_name ?? 'N/A'),
                    'average_score' => round($average, 2),
                ];
            })->sortByDesc('average_score')->values();


            // Add leaderboard rank
            $leaderboard = $grouped->map(function ($user, $index) {
                $user['leaderboard_rank'] = $index + 1;
                return $user;
            });

            $currentUserRank = optional($leaderboard->firstWhere('email', $currentUserEmail))['leaderboard_rank'] ?? null;

            // Limit to top 10 users for leaderboard
            $leaderboard = $leaderboard->take(10);

            return response()->json([
                'success' => true,
                'message' => __('Leaderboard retrieved successfully'),
                'data' => [
                    'leaderboard' => $leaderboard,
                    'total_users' => $leaderboard->count(),
                    'current_user_rank' => $currentUserRank,
                ]
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function fetchTrainingGrades(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,user_email',
            ]);

            $trainingUsers = TrainingAssignedUser::where('user_email', $request->email)
                ->where('personal_best', '>', 0)->get();

            $assignedTrainingModules = [];
            $assignedScormModules = [];

            foreach ($trainingUsers as $user) {
                $assignedTrainingModules[] = [
                    'training_name' => $user->trainingData->name,
                    'score' => $user->personal_best,
                    'grade' => $user->grade,
                    'assigned_date' => $user->assigned_date,
                ];
            }
            $avgTrainingScore = count($assignedTrainingModules) > 0 ? round(array_sum(array_column($assignedTrainingModules, 'score')) / count($assignedTrainingModules)) : 0;
            if ($avgTrainingScore >= 90) {
                $avgTrainingGrade = 'A+';
            } elseif ($avgTrainingScore >= 80) {
                $avgTrainingGrade = 'A';
            } elseif ($avgTrainingScore >= 70) {
                $avgTrainingGrade = 'B';
            } elseif ($avgTrainingScore >= 60) {
                $avgTrainingGrade = 'C';
            } else {
                $avgTrainingGrade = 'D';
            }

            $scormUsers = ScormAssignedUser::where('user_email', $request->email)
                ->where('personal_best', '>', 0)->get();

            foreach ($scormUsers as $user) {
                $assignedScormModules[] = [
                    'training_name' => $user->scormTrainingData->name,
                    'score' => $user->personal_best,
                    'grade' => $user->grade,
                    'assigned_date' => $user->assigned_date,
                ];
            }

            $avgScormScore = count($assignedScormModules) > 0 ? round(array_sum(array_column($assignedScormModules, 'score')) / count($assignedScormModules)) : 0;

            if ($avgScormScore >= 90) {
                $avgScormGrade = 'A+';
            } elseif ($avgScormScore >= 80) {
                $avgScormGrade = 'A';
            } elseif ($avgScormScore >= 70) {
                $avgScormGrade = 'B';
            } elseif ($avgScormScore >= 60) {
                $avgScormGrade = 'C';
            } else {
                $avgScormGrade = 'D';
            }

            return response()->json([
                'success' => true,
                'message' => __('Training grades retrieved successfully'),
                'data' => [
                    'assigned_training_modules' => $assignedTrainingModules ?? [],
                    'total_assigned_training_mod' => count($assignedTrainingModules),
                    'avg_training_score' => count($assignedTrainingModules) > 0 ? round(array_sum(array_column($assignedTrainingModules, 'score')) / count($assignedTrainingModules)) : 0,
                    'avg_training_grade' => $avgTrainingGrade,
                    'assigned_scorm_modules' => $assignedScormModules ?? [],
                    'total_assigned_scorm_mod' => count($assignedScormModules),
                    'avg_scorm_score' => count($assignedScormModules) > 0 ? round(array_sum(array_column($assignedScormModules, 'score')) / count($assignedScormModules)) : 0,
                    'avg_scorm_grade' => $avgScormGrade,
                    'total_trainings' => count($assignedTrainingModules) + count($assignedScormModules),
                    'total_passed_trainings' => TrainingAssignedUser::where('user_email', $request->email)
                        ->where('completed', 1)->count() + ScormAssignedUser::where('user_email', $request->email)
                        ->where('completed', 1)->count(),
                    'current_avg' => (TrainingAssignedUser::where('user_email', $request->email)
                    ->where('training_started', 1)
                        ->sum('personal_best') + ScormAssignedUser::where('user_email', $request->email)
                        ->where('scorm_started', 1)
                        ->sum('personal_best')) / (TrainingAssignedUser::where('user_email', $request->email)
                        ->count() + ScormAssignedUser::where('user_email', $request->email)
                        ->count()),
                ]
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function fetchTrainingBadges(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,user_email',
            ]);

            $allBadgeIds = [];

            // Collect badge IDs from training
            $trainingWithBadges = TrainingAssignedUser::where('user_email', $request->email)
                ->whereNotNull('badge')
                ->get();

            foreach ($trainingWithBadges as $training) {
                $badgeIds = json_decode($training->badge, true) ?? [];
                $allBadgeIds = array_merge($allBadgeIds, $badgeIds);
            }

            // Collect badge IDs from SCORM
            $scormWithBadges = ScormAssignedUser::where('user_email', $request->email)
                ->whereNotNull('badge')
                ->get();

            foreach ($scormWithBadges as $scorm) {
                $badgeIds = json_decode($scorm->badge, true) ?? [];
                $allBadgeIds = array_merge($allBadgeIds, $badgeIds);
            }

            // Remove duplicate badge IDs
            $uniqueBadgeIds = array_unique($allBadgeIds);

            // Fetch badges
            $badges = Badge::whereIn('id', $uniqueBadgeIds)->get();

            return response()->json([
                'success' => true,
                'message' => __('Badges retrieved successfully'),
                'data' => [
                    'badges' => $badges,
                    'total_badges' => count($badges),
                ]
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function fetchTrainingGoals(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,user_email',
            ]);

            $trainingGoals = TrainingAssignedUser::with('trainingData')->where('user_email', $request->email)->where('personal_best', '<', 70)->get();

            $scormGoals = ScormAssignedUser::with('scormTrainingData')->where('user_email', $request->email)->where('personal_best', '<', 70)->get();

            $activeGoals =  TrainingAssignedUser::with('trainingData')->where('user_email', $request->email)->where('personal_best', '<', 70)->where('training_started', 1)->get();

            return response()->json([
                'success' => true,
                'message' => __('Training goals retrieved successfully'),
                'data' => [
                    'all_training_goals' => $trainingGoals ?? [],
                    'all_scorm_goals' => $scormGoals ?? [],
                    'active_goals' => $activeGoals ?? [],
                    'total_active_goals' => count($activeGoals),
                    'avg_progress_training' => round(TrainingAssignedUser::with('trainingData')
                        ->where('user_email', $request->email)
                        ->where('training_started', 1)
                        ->where('completed', 1)->avg('personal_best')),
                    'total_training_goals' => count($trainingGoals) + count($scormGoals),
                    'avg_in_progress_trainings' => round(TrainingAssignedUser::with('trainingData')
                        ->where('user_email', $request->email)
                        ->where('training_started', 1)
                        ->where('completed', 0)->avg('personal_best')),
                ]
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function fetchTrainingAchievements(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,user_email',
            ]);

            $allBadgeIds = [];

            // Collect badge IDs from training
            $trainingWithBadges = TrainingAssignedUser::where('user_email', $request->email)
                ->whereNotNull('badge')
                ->get();

            foreach ($trainingWithBadges as $training) {
                $badgeIds = json_decode($training->badge, true) ?? [];
                $allBadgeIds = array_merge($allBadgeIds, $badgeIds);
            }

            // Collect badge IDs from SCORM
            $scormWithBadges = ScormAssignedUser::where('user_email', $request->email)
                ->whereNotNull('badge')
                ->get();

            foreach ($scormWithBadges as $scorm) {
                $badgeIds = json_decode($scorm->badge, true) ?? [];
                $allBadgeIds = array_merge($allBadgeIds, $badgeIds);
            }

            // Remove duplicate badge IDs
            $uniqueBadgeIds = array_unique($allBadgeIds);

            // Fetch badges
            $badges = Badge::whereIn('id', $uniqueBadgeIds)->get();

            $certificates = TrainingAssignedUser::where('user_email', $request->email)
                ->where('certificate_path', '!=', null)
                ->pluck('certificate_path');

            return response()->json([
                'success' => true,
                'message' => __('Training achivements retrieved successfully'),
                'data' => [
                    'badges' => $badges,
                    'certificates' => $certificates,
                ]
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function fetchAllAssignedTrainings(Request $request){
        try{
            $request->validate([
                'email' => 'required|email|exists:users,user_email',
            ]);

            $assignedTrainings = TrainingAssignedUser::where('user_email', $request->email)
                ->with('trainingData')
                ->where('personal_best', '>', 0)
                ->get();

                foreach($assignedTrainings as $training){
                    $allAssignments[] = [
                        'training_name' => $training->trainingData->name,
                        'type' => $training->trainingData->training_type,
                        'score' => $training->personal_best,
                        'assigned_date' => $training->assigned_date,
                        'grade' => $training->grade,
                    ];
                }

                $assignedScorm = ScormAssignedUser::where('user_email', $request->email)
                ->with('scormTrainingData')
                ->where('personal_best', '>', 0)
                ->get();


                foreach($assignedScorm as $scorm){
                    $allAssignments[] = [
                        'training_name' => $scorm->scormTrainingData->name,
                        'type' => 'Scorm',
                        'score' => $scorm->personal_best,
                        'assigned_date' => $scorm->assigned_date,
                        'grade' => $scorm->grade,
                    ];
                }

            return response()->json([
                'success' => true,
                'message' => __('Assigned trainings retrieved successfully'),
                'data' => $allAssignments
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function startTrainingModule(Request $request){
        try{
            $request->validate([
                'training_id' => 'required|integer',
            ]);

            $training = TrainingAssignedUser::find($request->training_id);

            if (!$training) {
                return response()->json(['success' => false, 'message' => __('Training not found.')], 404);
            }

            if ($training->completed == 1) {
                return response()->json(['success' => false, 'message' => __('Training already completed.')], 422);
            }

            $training->training_started = 1;
            $training->save();

            return response()->json(['success' => true, 'message' => __('Training started successfully.')], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function startScorm(Request $request){
        try{
            $request->validate([
                'scorm_id' => 'required|integer',
            ]);

            $scorm = ScormAssignedUser::find($request->scorm_id);

            if (!$scorm) {
                return response()->json(['success' => false, 'message' => __('Scorm not found.')], 404);
            }

            if ($scorm->completed == 1) {
                return response()->json(['success' => false, 'message' => __('Scorm already completed.')], 422);
            }

            $scorm->scorm_started = 1;
            $scorm->save();

            return response()->json(['success' => true, 'message' => __('Scorm started successfully.')], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }
}
