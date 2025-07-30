<?php

namespace App\Http\Controllers\LearnApi;

use App\Http\Controllers\Controller;
use App\Models\BlueCollarEmployee;
use App\Models\BlueCollarScormAssignedUser;
use App\Models\BlueCollarTrainingUser;
use App\Services\BlueCollarWhatsappService;
use App\Services\CheckWhitelabelService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use setasign\Fpdi\Fpdi;

class ApiLearnBlueCollarController extends Controller
{
    public function createNewToken(Request $request)
    {
        try {
            $request->validate(['user_whatsapp' => 'required|integer']);

            $hasTraining = BlueCollarTrainingUser::where('user_whatsapp', $request->user_whatsapp)->first();

            // $hasPolicy = AssignedPolicy::where('user_email', $request->email)->first();

            if (!$hasTraining) {
                return response()->json([
                    'success' => false,
                    'message' => 'No training has been assigned to this whatsapp number.'
                ], 422);
            }

            // delete old generated tokens from db
            DB::table('blue_collar_learner_login_sessions')->where('whatsapp_number', $request->user_whatsapp)->delete();

            // Encrypt user_whatsapp to generate token
            $token = encrypt($request->user_whatsapp);
            if ($hasTraining) {
                $companyId = $hasTraining->company_id;
            }
            // if ($hasPolicy) {
            //     $companyId = $hasPolicy->company_id;
            // }

            // Insert new record into the database
            $inserted = DB::table('blue_collar_learner_login_sessions')->insert([
                'token' => $token,
                'whatsapp_number' => $request->user_whatsapp,
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

            $whatsappService = new BlueCollarWhatsappService($companyId);
            $whatsapp_response = $whatsappService->sendSessionRegenerate($request->user_whatsapp);

            if ($whatsapp_response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Session regenerated successfully'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send WhatsApp message'
                ], 422);
            }
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

            $session = DB::table('blue_collar_learner_login_sessions')->where('token', $token)->orderBy('created_at', 'desc') // Ensure the latest session is checked
                ->first();
            if (!$session || now()->greaterThan(Carbon::parse($session->expiry))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your training session has expired!'
                ], 422);
            }

            // Decrypt the email
            $userWhatsapp = decrypt($session->token);

            Session::put('token', $token);

            $employeeType = 'bluecollar';
            $userName = BlueCollarEmployee::where('whatsapp', $userWhatsapp)->value('user_name');


            return response()->json([
                'success' => true,
                'data' => [
                    'user_whatsapp' => $userWhatsapp,
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

    public function getTranings(Request $request)
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
            $allTrainings = BlueCollarTrainingUser::with('trainingData')
                ->where('user_whatsapp', $request->user_whatsapp)->where('training_type', '!=', 'games')->get();

            $completedTrainings = BlueCollarTrainingUser::with('trainingData')
                ->where('user_whatsapp', $request->user_whatsapp)
                ->where('training_type', '!=', 'games')
                ->where('completed', 1)->get();

            $inProgressTrainings = BlueCollarTrainingUser::with('trainingData')
                ->where('user_whatsapp', $request->user_whatsapp)
                ->where('training_started', 1)
                ->where('completed', 0)->where('training_type', '!=', 'games')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'user_whatsapp' => $request->user_whatsapp,
                    'all_trainings' => $allTrainings,
                    'completed_trainings' => $completedTrainings,
                    'in_progress_trainings' => $inProgressTrainings,
                    'total_trainings' => BlueCollarTrainingUser::with('trainingData')
                        ->where('user_whatsapp', $request->user_whatsapp)->count(),
                    'total_trainings' => $allTrainings->count(),
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

            $rowData = BlueCollarTrainingUser::with('trainingData')->find($row_id);
            if (!$rowData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Training not found.'
                ], 404);
            }
            $user = $rowData->user_whatsapp;

            if ($request->trainingScore == 0 && $rowData->personal_best == 0) {
                $rowData->grade = 'F';
                $rowData->save();
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

                    $totalCompletedTrainings = BlueCollarTrainingUser::where('user_whatsapp', $rowData->user_whatsapp)
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

                    $data = [
                        'user_name' => $rowData->user_name,
                        'training_name' => $rowData->trainingData->name,
                        'completion_date' => $rowData->completion_date,
                        'user_whatsapp' => $rowData->user_whatsapp,
                    ];

                    // Send WhatsApp message
                    $whatsappService = new BlueCollarWhatsappService($rowData->company_id);
                    $whatsapp_response = $whatsappService->sendTrainingComplete($data);

                     $isWhitelabeled = new CheckWhitelabelService($rowData->company_id);
                    if ($isWhitelabeled->isCompanyWhitelabeled()) {
                        $whitelabelData = $isWhitelabeled->getWhiteLabelData();
                        $companyLogo = env('CLOUDFRONT_URL') . $whitelabelData->dark_logo;
                        $favIcon = env('CLOUDFRONT_URL') . $whitelabelData->favicon;
                    } else {
                        $companyLogo = env('CLOUDFRONT_URL') . '/assets/images/simu-logo-dark.png';
                        $favIcon = env('CLOUDFRONT_URL') . '/assets/images/simu-icon.png';
                    }


                    $pdfContent = $this->generateCertificatePdf($rowData->user_name, $rowData->trainingData->name, $rowData->training, $rowData->completion_date, $rowData->user_whatsapp, $companyLogo, $favIcon);

                    $whatsappFolder = $rowData->user_whatsapp;
                    $pdfFileName = 'certificate_' . time() . '.pdf';
                    $relativePath =  'certificates/' . $whatsappFolder . '/' . $pdfFileName;


                    // Save using Storage
                    Storage::disk('s3')->put($relativePath, $pdfContent);
                    $certificate_full_path = Storage::disk('s3')->path($relativePath);

                    $rowData->certificate_path = '/' . $certificate_full_path;
                    $rowData->save();

                    if ($whatsapp_response->successful()) {
                        return response()->json(['success' => true, 'message' => 'Score updated'], 200);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to send WhatsApp message'
                        ], 422);
                    }

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

    public function generateCertificatePdf($name, $trainingModuleName, $trainingId, $date, $user_whatsapp, $logo, $favIcon)
    {
        $certificateId = $this->getCertificateId($user_whatsapp, $trainingId);
        if (!$certificateId) {
            $certificateId = $this->generateCertificateId();
            $this->storeCertificateId($user_whatsapp, $certificateId, $trainingId);
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

    private function getCertificateId($user_whatsapp, $trainingId)
    {
        // Check the database for an existing certificate ID for this user and training module
        $certificate = BlueCollarTrainingUser::where('training', $trainingId)
            ->where('user_whatsapp', $user_whatsapp)
            ->first();

        return $certificate ? $certificate->certificate_id : null;
    }

    private function generateCertificateId()
    {
        // Generate a unique random ID. You can adjust the format as needed.
        return strtoupper(uniqid('CERT-'));
    }

    private function storeCertificateId($user_whatsapp, $certificateId, $trainingId)
    {
        // Find the existing record based on training module and user_whatsapp
        $trainingAssignedUser = BlueCollarTrainingUser::where('training', $trainingId)
            ->where('user_whatsapp', $user_whatsapp)
            ->first();

        // Check if the record was found
        if ($trainingAssignedUser) {
            // Update only the certificate_id (no need to touch campaign_id)
            $trainingAssignedUser->update([
                'certificate_id' => $certificateId,
            ]);
        }

        $scormAssignedUser = BlueCollarScormAssignedUser::where('scorm', $trainingId)
            ->where('user_whatsapp', $user_whatsapp)
            ->first();

        if ($scormAssignedUser) {
            // Update only the certificate_id (no need to touch campaign_id)
            $scormAssignedUser->update([
                'certificate_id' => $certificateId,
            ]);
        }
    }
}
