<?php

namespace App\Http\Controllers\LearnApi;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\BlueCollarEmployee;
use App\Models\BlueCollarScormAssignedUser;
use App\Models\BlueCollarTrainingUser;
use App\Models\WaLiveCampaign;
use App\Services\BlueCollarEmpLearnService;
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
            $request->validate(['user_whatsapp' => 'required|integer|exists:blue_collar_employees,whatsapp']);

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
            $whatsapp_response = $whatsappService->sendSessionRegenerate($request->user_whatsapp, $token);

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

            // Decrypt the user_whatsapp
            $userWhatsapp = decrypt($session->token);

            Session::put('token', $token);

            $employeeType = 'bluecollar';
            $user = BlueCollarEmployee::where('whatsapp', $userWhatsapp)->first();
            if (!$user) {
                BlueCollarTrainingUser::where('user_whatsapp', $userWhatsapp)->delete();
                BlueCollarScormAssignedUser::where('user_whatsapp', $userWhatsapp)->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'You are no longer an employee on this platform.'
                ], 404);
            }

            $userName = $user->user_name;

            return response()->json([
                'success' => true,
                'data' => [
                    'user_whatsapp' => $userWhatsapp,
                    'employee_type' => $employeeType,
                    'user_name' => $userName
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

    public function getDashboardMetrics(Request $request)
    {
        try {
            $request->validate([
                'user_whatsapp' => 'required|integer|exists:blue_collar_employees,whatsapp',
            ]);

            $user = BlueCollarEmployee::where('whatsapp', $request->user_whatsapp)->first();

            // Calculate Risk score
            $riskData = $this->calculateRiskScore($user);
            $riskScore = $riskData['riskScore'];
            $riskLevel = $riskData['riskLevel'];

            // Calculate current rank

            $leaderboardRank = $this->calculateLeaderboardRank($request->user_whatsapp);
            $currentUserRank = $leaderboardRank['current_user_rank'];

            return response()->json([
                'success' => true,
                'data' => [
                    'riskScore' => $riskScore,
                    'riskLevel' => $riskLevel,
                    'currentUserRank' => $currentUserRank,
                ],
                'message' => 'Fetched dashboard metrics successfully'
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

    private function calculateLeaderboardRank($user_whatsapp)
    {

        $companyId = BlueCollarEmployee::where('whatsapp', $user_whatsapp)->value('company_id');

        $currentUserWhatsapp = $user_whatsapp;

        $trainingUsers = BlueCollarTrainingUser::where('company_id', $companyId)->get();
        $scormUsers = BlueCollarScormAssignedUser::where('company_id', $companyId)->get();

        $allUsers = $trainingUsers->merge($scormUsers);

        $grouped = $allUsers->groupBy('user_whatsapp')->map(function ($group, $user_whatsapp) use ($currentUserWhatsapp) {
            $average = $group->avg('personal_best');
            $assignedTrainingsCount = $group->count();

            return [
                'user_whatsapp' => $user_whatsapp,
                'name' => strtolower($user_whatsapp) == strtolower($currentUserWhatsapp) ? 'You' : ($group->first()->user_name ?? 'N/A'),
                'average_score' => round($average, 2),
                'assigned_trainings_count' => $assignedTrainingsCount,
            ];
        })->filter(function ($user) {
            return $user['average_score'] >= 10; // Filter users with score >= 10
        })->sortByDesc('average_score')->values();

        // Add leaderboard rank
        $leaderboard = $grouped->map(function ($user, $index) {
            $user['leaderboard_rank'] = $index + 1;
            return $user;
        });

        $currentUserRank = optional($leaderboard->firstWhere('user_whatsapp', $currentUserWhatsapp))['leaderboard_rank'] ?? null;
        return [
            'leaderboard' => $leaderboard,
            'current_user_rank' => $currentUserRank,
        ];
    }


    private function calculateRiskScore($user)
    {
        $riskScoreRanges = [
            'poor' => [0, 20],
            'fair' => [21, 40],
            'good' => [41, 60],
            'veryGood' => [61, 80],
            'excellent' => [81, 100],
        ];

        $whatsappCampaigns = WaLiveCampaign::where('user_id', $user->id)
            ->where('company_id', $user->company_id);

        $totalWhatsapp = $whatsappCampaigns->count();
        $compromisedWhatsapp = $whatsappCampaigns->where('compromised', 1)->count();

        // Risk score calculation
        $riskScore = null;
        $riskLevel = null;

        $totalAll = $totalWhatsapp;
        $compromisedAll = $compromisedWhatsapp;

        $riskScore = $totalAll > 0 ? 100 - round(($compromisedAll / $totalAll) * 100) : 100;

        // Determine risk level
        foreach ($riskScoreRanges as $label => [$min, $max]) {
            if ($riskScore >= $min && $riskScore <= $max) {
                $riskLevel = $label;
                break;
            }
        }

        return [
            'riskScore' => $riskScore,
            'riskLevel' => $riskLevel,
        ];
    }

    public function getTranings(Request $request)
    {
        try {
            $request->validate([
                'user_whatsapp' => 'required|integer|exists:blue_collar_employees,whatsapp',
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

            $user = $rowData->user_whatsapp;

            if ($request->trainingScore == 0 && $rowData->personal_best == 0) {
                $rowData->grade = 'F';
                $rowData->save();
            }

            $blueCollarEmpLearnService = new BlueCollarEmpLearnService();

            if ($rowData && $request->trainingScore > $rowData->personal_best) {
                // Update the column if the current value is greater
                $rowData->personal_best = $request->trainingScore;

                // Assign Grade based on score
                assignGrade($rowData, $request->trainingScore);

                $badge = getMatchingBadge('score', $request->trainingScore);
                // This helper function accepts a criteria type and value, and returns the first matching badge

                if ($badge) {
                    assignBadge($rowData, $badge);
                }

                $rowData->save();

                setCompanyTimezone($rowData->company_id);

                log_action("{$user} scored {$request->trainingScore}% in training", 'learner', 'learner');

                $passingScore = (int)$rowData->trainingData->passing_score;

                if ($request->trainingScore >= $passingScore) {
                    $rowData->completed = 1;
                    $rowData->completion_date = now()->format('Y-m-d');

                    // Audit log
                    audit_log(
                        $rowData->company_id,
                        null,
                        $rowData->user_whatsapp,
                        'TRAINING COMPLETED',
                        "{$rowData->user_whatsapp} completed training : '{$rowData->trainingData->name}'.",
                        'bluecollar'
                    );

                    $totalCompletedTrainings = BlueCollarTrainingUser::where('user_whatsapp', $rowData->user_whatsapp)
                        ->where('completed', 1)->count();

                    // This helper function accepts a criteria type and value, and returns the first matching badge
                    $badge = getMatchingBadge('courses_completed', $totalCompletedTrainings);

                    if ($badge) {
                        assignBadge($rowData, $badge);
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

                    $pdfContent = $blueCollarEmpLearnService->generateCertificatePdf($rowData, $companyLogo, $favIcon);

                    $blueCollarEmpLearnService->saveCertificatePdf($pdfContent, $rowData);

                    $rowData->save();

                    if ($whatsapp_response->successful()) {
                        return response()->json(['success' => true, 'message' => 'Score updated'], 200);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to send WhatsApp message'
                        ], 422);
                    }
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

    public function updateTrainingFeedback(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'feedback' => 'required|string|min:5|max:1000',
                'encoded_id' => 'required',
            ]);

            $trainingId = base64_decode($request->encoded_id);

            $trainingData = BlueCollarTrainingUser::find($trainingId);
            if (!$trainingData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Training not found.'
                ], 404);
            }

            $trainingData->update([
                'feedback' => $request->feedback,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Feedback updated successfully.'
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

    public function fetchScormTrainings(Request $request)
    {
        try {
            $request->validate([
                'user_whatsapp' => 'required|integer|exists:blue_collar_employees,whatsapp',
            ]);

            $user_whatsapp = $request->query('user_whatsapp');

            $allTrainings = BlueCollarScormAssignedUser::with('scormTrainingData')
                ->where('user_whatsapp', $request->user_whatsapp)->get();

            if ($allTrainings->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No SCORM trainings found for this WhatsApp number.'
                ], 422);
            }

            $completedTrainings = BlueCollarScormAssignedUser::with('scormTrainingData')
                ->where('user_whatsapp', $request->user_whatsapp)
                ->where('completed', 1)->get();

            $inProgressTrainings = BlueCollarScormAssignedUser::with('scormTrainingData')
                ->where('user_whatsapp', $request->user_whatsapp)
                ->where('scorm_started', 1)
                ->where('completed', 0)->get();

            return response()->json([
                'success' => true,
                'message' => __('Scorm trainings retrieved successfully'),
                'data' => [
                    'user_whatsapp' => $user_whatsapp,
                    'all_trainings' => $allTrainings,
                    'completed_trainings' => $completedTrainings,
                    'in_progress_trainings' => $inProgressTrainings,
                    'total_trainings' => BlueCollarScormAssignedUser::with('scormTrainingData')
                        ->where('user_whatsapp', $user_whatsapp)->count(),
                    'total_trainings' => $allTrainings->count(),
                    'total_completed_trainings' => $completedTrainings->count(),
                    'total_in_progress_trainings' => $inProgressTrainings->count(),
                    'avg_in_progress_trainings' => round(BlueCollarScormAssignedUser::with('scormTrainingData')
                        ->where('user_whatsapp', $user_whatsapp)
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

    public function downloadTrainingCertificate(Request $request)
    {
        $request->validate([
            'training_id' => 'required|integer',
            'user_whatsapp' => 'required|integer|exists:blue_collar_employees,whatsapp',
        ]);

        $training = BlueCollarTrainingUser::find($request->training_id);

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
            'user_whatsapp' => 'required|integer|exists:blue_collar_employees,whatsapp',

        ]);

        $training = BlueCollarScormAssignedUser::find($request->scorm_id);

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

    public function updateScormTrainingScore(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'scormTrainingScore' => 'required|integer',
                'encoded_id' => 'required',
            ]);

            $row_id = base64_decode($request->encoded_id);

            $rowData = BlueCollarScormAssignedUser::with('scormTrainingData')->find($row_id);

            $user = $rowData->user_whatsapp;

            if ($request->scormTrainingScore == 0 && $rowData->personal_best == 0) {
                $rowData->grade = 'F';
                $rowData->save();
            }

            if ($rowData && $request->scormTrainingScore > $rowData->personal_best) {
                // Update the column if the current value is greater
                $rowData->personal_best = $request->scormTrainingScore;

                $blueCollarEmpLearnService = new BlueCollarEmpLearnService();

                // Assign Grade based on score
                assignGrade($rowData, $request->trainingScore);

                $badge = getMatchingBadge('score', $request->trainingScore);
                // This helper function accepts a criteria type and value, and returns the first matching badge

                if ($badge) {
                    assignBadge($rowData, $badge);
                }

                $rowData->save();

                setCompanyTimezone($rowData->company_id);

                log_action("{$user} scored {$request->scormTrainingScore}% in training", 'learner', 'learner');

                $passingScore = (int)$rowData->scormTrainingData->passing_score;

                if ($request->scormTrainingScore >= $passingScore) {
                    $rowData->completed = 1;
                    $rowData->completion_date = now()->format('Y-m-d');

                    // Audit log
                    audit_log(
                        $rowData->company_id,
                        null,
                        $rowData->user_whatsapp,
                        'TRAINING COMPLETED',
                        "{$rowData->user_whatsapp} completed training : '{$rowData->scormTrainingData->name}'.",
                        'bluecollar'
                    );

                    $totalCompletedTrainings = BlueCollarScormAssignedUser::where('user_whatsapp', $rowData->user_whatsapp)
                        ->where('completed', 1)->count();

                    // This helper function accepts a criteria type and value, and returns the first matching badge
                    $badge = getMatchingBadge('courses_completed', $totalCompletedTrainings);

                    if ($badge) {
                        assignBadge($rowData, $badge);
                    }
                    $rowData->save();

                    $data = [
                        'user_name' => $rowData->user_name,
                        'training_name' => $rowData->scormTrainingData->name,
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

                    $pdfContent = $blueCollarEmpLearnService->generateScormCertificatePdf($rowData, $companyLogo, $favIcon);

                    $blueCollarEmpLearnService->saveCertificatePdf($pdfContent, $rowData);

                    $rowData->save();

                    if ($whatsapp_response->successful()) {
                        return response()->json(['success' => true, 'message' => 'Score updated'], 200);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to send WhatsApp message'
                        ], 422);
                    }

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

    public function updateScormTrainingFeedback(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'feedback' => 'required|string|min:5|max:1000',
                'encoded_id' => 'required',
            ]);

            $scormId = base64_decode($request->encoded_id);

            $scormData = BlueCollarScormAssignedUser::find($scormId);
            if (!$scormData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Scorm not found.'
                ], 404);
            }

            $scormData->update([
                'feedback' => $request->feedback,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Feedback updated successfully.'
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

    // public function generateScormCertificatePdf($name, $scormName, $scorm, $date, $user_whatsapp, $logo, $favIcon)
    // {
    //     $certificateId = $this->getScormCertificateId($user_whatsapp, $scorm);
    //     if (!$certificateId) {
    //         $certificateId = $this->generateCertificateId();
    //         $this->storeScormCertificateId($user_whatsapp, $certificateId, $scorm);
    //     }

    //     $pdf = new Fpdi();
    //     $pdf->AddPage('L', 'A4');
    //     $pdf->setSourceFile(resource_path('templates/design.pdf'));
    //     $template = $pdf->importPage(1);
    //     $pdf->useTemplate($template);

    //     // Truncate name if too long
    //     if (strlen($name) > 15) {
    //         $name = mb_substr($name, 0, 12) . '...';
    //     }

    //     // Add user name
    //     $pdf->SetFont('Helvetica', '', 50);
    //     $pdf->SetTextColor(47, 40, 103);
    //     $pdf->SetXY(100, 115);
    //     $pdf->Cell(0, 10, ucwords($name), 0, 1, 'L');

    //     // Add training module
    //     $pdf->SetFont('Helvetica', '', 16);
    //     $pdf->SetTextColor(169, 169, 169);
    //     $pdf->SetXY(100, 135);
    //     $pdf->Cell(210, 10, "For completing $scormName", 0, 1, 'L');

    //     // Add date and certificate ID
    //     $pdf->SetFont('Helvetica', '', 10);
    //     $pdf->SetTextColor(120, 120, 120);
    //     $pdf->SetXY(240, 165);
    //     $pdf->Cell(50, 10, "Completion date: $date", 0, 0, 'R');

    //     $pdf->SetXY(240, 10);
    //     $pdf->Cell(50, 10, "Certificate ID: $certificateId", 0, 0, 'R');

    //     if ($logo || file_exists($logo)) {
    //         // 1. Top-left corner (e.g., branding)
    //         $pdf->Image($logo, 100, 12, 50); // X=15, Y=12, Width=40mm           
    //     }

    //     // 2. Bottom-center badge
    //     $pdf->Image($favIcon, 110, 163, 15, 15);

    //     return $pdf->Output('S');
    // }

    // private function getScormCertificateId($user_whatsapp, $scorm)
    // {
    //     // Check the database for an existing certificate ID for this user and training module
    //     $certificate = BlueCollarTrainingUser::where('scorm', $scorm)
    //         ->where('user_whatsapp', $user_whatsapp)
    //         ->first();

    //     return $certificate ? $certificate->certificate_id : null;
    // }

    // private function storeScormCertificateId($user_whatsapp, $certificateId, $scorm)
    // {
    //     $scormAssignedUser = BlueCollarTrainingUser::where('scorm', $scorm)
    //         ->where('user_whatsapp', $user_whatsapp)
    //         ->first();

    //     if ($scormAssignedUser) {
    //         // Update only the certificate_id (no need to touch campaign_id)
    //         $scormAssignedUser->update([
    //             'certificate_id' => $certificateId,
    //         ]);
    //     }
    // }

    public function fetchScoreBoard(Request $request)
    {
        try {
            $request->validate([
                'user_whatsapp' => 'required|integer|exists:blue_collar_employees,whatsapp',
            ]);

            $allAssignedTrainingMods = BlueCollarTrainingUser::where('user_whatsapp', $request->user_whatsapp)->where('training_started', 1)->get();

            $allAssignedScorms = BlueCollarScormAssignedUser::where('user_whatsapp', $request->user_whatsapp)->where('scorm_started', 1)->get();

            $allAssignedTrainings = [];
            foreach ($allAssignedTrainingMods as $trainingMod) {
                $allAssignedTrainings[] = [
                    'training_name' => $trainingMod->training_type == 'games' ? $trainingMod->trainingGame->name : $trainingMod->trainingData->name,
                    'score' => $trainingMod->personal_best,
                    'status' => $trainingMod->completed ? 'Completed' : 'Not Completed',
                    'training_due_date' => $trainingMod->training_due_date,
                    'completion_date' => $trainingMod->completion_date,
                    'training_type' => $trainingMod->training_type,
                ];
            }

            foreach ($allAssignedScorms as $scorm) {
                $allAssignedTrainings[] = [
                    'training_name' => $scorm->scormTrainingData->name,
                    'score' => $scorm->personal_best,
                    'status' => $scorm->completed ? 'Completed' : 'Not Completed',
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
                'user_whatsapp' => 'required|integer|exists:blue_collar_employees,whatsapp',
            ]);

            $leaderboardRank = $this->calculateLeaderboardRank($request->user_whatsapp);
            $currentUserRank = $leaderboardRank['current_user_rank'];
            $leaderboard = $leaderboardRank['leaderboard'];

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
                'user_whatsapp' => 'required|integer|exists:blue_collar_employees,whatsapp',
            ]);

            $trainingUsers = BlueCollarTrainingUser::where('user_whatsapp', $request->user_whatsapp)
                ->where('training_started', 1)
                ->where('grade', '!=', 'null')->get();

            $assignedTrainingModules = [];
            $assignedScormModules = [];

            foreach ($trainingUsers as $user) {
                $assignedTrainingModules[] = [
                    'training_name' => $user->training_type == 'games' ? $user->trainingGame->name : $user->trainingData->name,
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

            $scormUsers = BlueCollarScormAssignedUser::where('user_whatsapp', $request->user_whatsapp)
                ->where('scorm_started', 1)
                ->where('grade', '!=', 'null')->get();

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
                    'total_passed_trainings' => BlueCollarTrainingUser::where('user_whatsapp', $request->user_whatsapp)
                        ->where('completed', 1)->count() + BlueCollarScormAssignedUser::where('user_whatsapp', $request->user_whatsapp)
                        ->where('completed', 1)->count(),
                    'current_avg' => (
                        BlueCollarTrainingUser::where('user_whatsapp', $request->user_whatsapp)
                        ->where('training_started', 1)
                        ->sum('personal_best')
                        +
                        BlueCollarScormAssignedUser::where('user_whatsapp', $request->user_whatsapp)
                        ->where('scorm_started', 1)
                        ->sum('personal_best')
                    ) / max(1, (
                        BlueCollarTrainingUser::where('user_whatsapp', $request->user_whatsapp)
                        ->where('training_started', 1)
                        ->count()
                        +
                        BlueCollarScormAssignedUser::where('user_whatsapp', $request->user_whatsapp)
                        ->where('scorm_started', 1)
                        ->count()
                    )),
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
                'user_whatsapp' => 'required|integer|exists:blue_collar_employees,whatsapp',
            ]);

            $allBadgeIds = [];

            // Collect badge IDs from training
            $trainingWithBadges = BlueCollarTrainingUser::where('user_whatsapp', $request->user_whatsapp)
                ->whereNotNull('badge')
                ->get();

            foreach ($trainingWithBadges as $training) {
                $badgeIds = json_decode($training->badge, true) ?? [];
                $allBadgeIds = array_merge($allBadgeIds, $badgeIds);
            }

            // Collect badge IDs from SCORM
            $scormWithBadges = BlueCollarScormAssignedUser::where('user_whatsapp', $request->user_whatsapp)
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
                    'total_unlock_badges' => Badge::where('id', '!=', $uniqueBadgeIds)->count()
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
                'user_whatsapp' => 'required|integer|exists:blue_collar_employees,whatsapp',
            ]);

            $nonGameGoals = BlueCollarTrainingUser::with('trainingData')->where('user_whatsapp', $request->user_whatsapp)->where('training_type', '!=', 'games')->where('personal_best', '<', 70)->get();

            $gameGoals = BlueCollarTrainingUser::with('trainingGame')->where('user_whatsapp', $request->user_whatsapp)->where('training_type',  'games')->where('personal_best', '<', 70)->get();

            $trainingGoals = $nonGameGoals->concat($gameGoals);

            $scormGoals = BlueCollarScormAssignedUser::with('scormTrainingData')->where('user_whatsapp', $request->user_whatsapp)->where('personal_best', '<', 70)->get();

            $activeNonGameGoals =  BlueCollarTrainingUser::with('trainingData')->where('user_whatsapp', $request->user_whatsapp)->where('training_type', '!=', 'games')->where('personal_best', '<', 70)->where('training_started', 1)->get();

            $activeGameGoals =  BlueCollarTrainingUser::with('trainingData')->where('user_whatsapp', $request->user_whatsapp)->where('training_type',  'games')->where('personal_best', '<', 70)->where('training_started', 1)->get();

            $activeGoals = $activeNonGameGoals->concat($activeGameGoals);

            return response()->json([
                'success' => true,
                'message' => __('Training goals retrieved successfully'),
                'data' => [
                    'all_training_goals' => $trainingGoals ?? [],
                    'all_scorm_goals' => $scormGoals ?? [],
                    'active_goals' => $activeGoals ?? [],
                    'total_active_goals' => count($activeGoals),
                    'avg_progress_training' => round(BlueCollarTrainingUser::where('user_whatsapp', $request->user_whatsapp)
                        ->where('training_started', 1)
                        ->where('completed', 1)->avg('personal_best')),
                    'total_training_goals' => count($trainingGoals) + count($scormGoals),
                    'avg_in_progress_trainings' => round(BlueCollarTrainingUser::where('user_whatsapp', $request->user_whatsapp)
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
                'user_whatsapp' => 'required|integer|exists:blue_collar_employees,whatsapp',
            ]);

            $allBadgeIds = [];

            // Collect badge IDs from training
            $trainingWithBadges = BlueCollarTrainingUser::where('user_whatsapp', $request->user_whatsapp)
                ->whereNotNull('badge')
                ->get();

            foreach ($trainingWithBadges as $training) {
                $badgeIds = json_decode($training->badge, true) ?? [];
                $allBadgeIds = array_merge($allBadgeIds, $badgeIds);
            }

            // Collect badge IDs from SCORM
            $scormWithBadges = BlueCollarScormAssignedUser::where('user_whatsapp', $request->user_whatsapp)
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

            $certificates = BlueCollarTrainingUser::where('user_whatsapp', $request->user_whatsapp)
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

    public function fetchAllAssignedTrainings(Request $request)
    {
        try {
            $request->validate([
                'user_whatsapp' => 'required|integer|exists:blue_collar_employees,whatsapp',
            ]);

            $assignedTrainings = BlueCollarTrainingUser::where('user_whatsapp', $request->user_whatsapp)
                ->with('trainingData')
                ->get();

            foreach ($assignedTrainings as $training) {
                $allAssignments[] = [
                    'training_name' => $training->training_type == 'games' ? $training->trainingGame->name : $training->trainingData->name,
                    'type' => $training->training_type == 'games' ? 'games' : $training->trainingData->training_type,
                    'score' => $training->personal_best,
                    'assigned_date' => $training->assigned_date,
                    'grade' => $training->grade ?? 'N/A',
                ];
            }

            $assignedScorm = BlueCollarScormAssignedUser::where('user_whatsapp', $request->user_whatsapp)
                ->with('scormTrainingData')
                ->get();


            foreach ($assignedScorm as $scorm) {
                $allAssignments[] = [
                    'training_name' => $scorm->scormTrainingData->name,
                    'type' => 'Scorm',
                    'score' => $scorm->personal_best,
                    'assigned_date' => $scorm->assigned_date,
                    'grade' => $scorm->grade ?? 'N/A',
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

    public function startTrainingModule(Request $request)
    {
        try {
            $request->validate([
                'training_id' => 'required|integer',
            ]);

            $training = BlueCollarTrainingUser::find($request->training_id);

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

    public function startScorm(Request $request)
    {
        try {
            $request->validate([
                'scorm_id' => 'required|integer',
            ]);

            $scorm = BlueCollarScormAssignedUser::find($request->scorm_id);

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
