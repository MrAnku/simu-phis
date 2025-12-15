<?php

namespace App\Http\Controllers\LearnApi;

use App\Http\Controllers\Controller;
use App\Models\AiCallCampLive;
use App\Models\Badge;
use App\Models\BlueCollarEmployee;
use App\Models\BlueCollarScormAssignedUser;
use App\Models\BlueCollarTrainingUser;
use App\Models\PhishSetting;
use App\Models\CompanySettings;
use App\Models\TrainingModule;
use App\Models\TrainingSetting;
use App\Models\Users;
use App\Models\UserTour;
use App\Models\WaLiveCampaign;
use App\Services\BlueCollarEmpLearnService;
use App\Services\BlueCollarWhatsappService;
use App\Services\CheckWhitelabelService;
use App\Services\TrainingAssignedService;
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
                    'message' => __('Token is required!')
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

            $blueCollarService = new BlueCollarEmpLearnService();

            // check if risk information is enabled from company or not
            $riskInfoEnabled = PhishSetting::where('company_id', $user->company_id)->value('risk_information');

            if ($riskInfoEnabled) {
                // Calculate Risk score
                $riskData = $blueCollarService->calculateRiskScore($user);
                $riskScore = $riskData['riskScore'];
                $riskLevel = $riskData['riskLevel'];
            }

            // Calculate current rank
            $leaderboardRank = $this->calculateLeaderboardRank($request->user_whatsapp);
            $currentUserRank = $leaderboardRank['current_user_rank'];

            // tour_prompt status
            $tourPromptSettings = CompanySettings::where('company_id', $user->company_id)->first();
            $tourPrompt =  $tourPromptSettings ? (int) $tourPromptSettings->tour_prompt : 0;

            // tour completed 
            $tourCompleted = UserTour::where('company_id', $user->company_id)->where('user_whatsapp', $request->user_whatsapp)->value('tour_completed');
            $tourCompleted = $tourCompleted ? 1 : 0;

            // check if help redirect destination is set or not
            $helpRedirectTo = TrainingSetting::where('company_id', $user->company_id)->value('help_redirect_to');
            if (!$helpRedirectTo) {
                $helpRedirectTo = "https://help.simuphish.com";
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'riskScore' => $riskScore ?? null,
                    'riskLevel' => $riskLevel ?? null,
                    'currentUserRank' => $currentUserRank,
                    'tour_prompt' => $tourPrompt,
                    'tour_completed' => $tourCompleted,
                    'help_redirect_to' => $helpRedirectTo
                ],
                'message' => __('Fetched dashboard metrics successfully')
            ], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
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
                    'message' => __('No blue collar employee found with this WhatsApp number.')
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
                'message' => __('Courses fetched successfully for blue collar employee')
            ], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
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


            // If user fails then assign alternative training if exists
            $passingScore = (int)$rowData->trainingData->passing_score;

            if ($request->trainingScore < $passingScore && $rowData->alt_training != 1) {

                $alterTrainingId = $rowData->trainingData->alternative_training;

                if ($alterTrainingId !== null) {
                    $alterTraining = TrainingModule::find($alterTrainingId);
                    if (!$alterTraining) {
                        return response()->json(['success' => false, 'message' => 'No Alternative Training Found'], 404);
                    }

                    $isAlterTrainingAssigned = BlueCollarTrainingUser::where('user_whatsapp', $rowData->user_whatsapp)
                        ->where('training', $alterTrainingId)
                        ->first();

                    if (!$isAlterTrainingAssigned) {
                        $campData = [
                            'campaign_id' => $rowData->campaign_id,
                            'user_id' => $rowData->user_id,
                            'user_name' => $rowData->user_name,
                            'user_whatsapp' => $rowData->user_whatsapp,
                            'training' => $alterTrainingId,
                            'training_lang' => $rowData->training_lang,
                            'training_type' => 'ai_training',
                            'assigned_date' => $rowData->assigned_date,
                            'training_due_date' => $rowData->training_due_date,
                            'company_id' => $rowData->company_id,
                            'alt_training' => 1
                        ];

                        $trainingAssignedService = new TrainingAssignedService();

                        $trainingAssigned = $trainingAssignedService->assignNewBlueCollarTraining($campData);

                        if ($trainingAssigned['status'] == 1) {
                            $rowData->alt_training = 1;
                            $rowData->save();

                            $module = TrainingModule::find($alterTrainingId);
                            // Audit log
                            audit_log(
                                $rowData->company_id,
                                null,
                                $rowData->user_whatsapp,
                                'ALTER_TRAINING_ASSIGNED',
                                "{$rowData->user_whatsapp} has been assigned an alternative training : '{$module->name}'.",
                                'bluecollar'
                            );
                        }
                    }
                }
            }

            if ($rowData && $request->trainingScore > $rowData->personal_best) {
                // Update the column if the current value is greater
                $rowData->personal_best = $request->trainingScore;

                // Assign Grade based on score
                assignGrade($rowData, $request->trainingScore);

                $badge = getMatchingBadge('score', $request->trainingScore);
                // This helper function accepts a criteria type and value, and returns the first matching badge

                if ($badge) {
                    assignBadge($rowData, $badge);

                    // Notify admin when badge assigned
                    $badgeDetails = Badge::find($badge);
                    sendNotification("Badge '{$badgeDetails->name}' has been awarded to {$rowData->user_whatsapp}", $rowData->company_id);
                }

                $rowData->save();

                setCompanyTimezone($rowData->company_id);

                log_action("{$user} scored {$request->trainingScore}% in training", 'company', $rowData->company_id);

                if ($request->trainingScore >= $passingScore) {
                    $rowData->completed = 1;
                    $rowData->completion_date = now()->format('Y-m-d');

                    // Audit log
                    audit_log(
                        $rowData->company_id,
                        null,
                        $rowData->user_whatsapp,
                        'TRAINING_COMPLETED',
                        "{$rowData->user_whatsapp} completed training : '{$rowData->trainingData->name}'.",
                        'bluecollar'
                    );

                    // Notify admin
                    sendNotification("{$user} has completed the ‘{$rowData->trainingData->name}’ training with a score of {$request->trainingScore}%", $rowData->company_id);

                    $totalCompletedTrainings = BlueCollarTrainingUser::where('user_whatsapp', $rowData->user_whatsapp)
                        ->where('completed', 1)->count();

                    // This helper function accepts a criteria type and value, and returns the first matching badge
                    $badge = getMatchingBadge('courses_completed', $totalCompletedTrainings);

                    if ($badge) {
                        assignBadge($rowData, $badge);
                        // Notify admin when badge assigned
                        $badgeDetails = Badge::find($badge);
                        sendNotification("Badge '{$badgeDetails->name}' has been awarded to {$rowData->user_whatsapp}", $rowData->company_id);
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

                    $branding = new CheckWhitelabelService($rowData->company_id);
                    $companyLogo = $branding->companyDarkLogo();
                    $favIcon = $branding->companyFavicon();


                    $pdfContent = $blueCollarEmpLearnService->generateCertificatePdf($rowData, $companyLogo, $favIcon);

                    $blueCollarEmpLearnService->saveCertificatePdf($pdfContent, $rowData);

                    $rowData->save();

                    // Audit log
                    audit_log(
                        $rowData->company_id,
                        null,
                        $rowData->user_whatsapp,
                        'CERTIFICATE_AWARDED',
                        "Certificate for {$rowData->trainingData->name} has been awarded to {$rowData->user_whatsapp}",
                        'bluecollar'
                    );


                    // Notify admin when certificate assigned
                    sendNotification("Certificate for {$rowData->trainingData->name} has been awarded to {$rowData->user_whatsapp}", $rowData->company_id);

                    if ($whatsapp_response->successful()) {
                        return response()->json(['success' => true, 'message' => __('Score updated')], 200);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => __('Failed to send WhatsApp message')
                        ], 422);
                    }
                }

                // when user fails then notify admin
                if ($request->trainingScore < $passingScore) {
                    sendNotification("{$rowData->user_whatsapp} failed the '{$rowData->trainingData->name}' training with a score of {$request->trainingScore}.", $rowData->company_id);
                }
            }
            return response()->json(['success' => true, 'message' => __('Score updated')], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
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
                    'message' => __('Training not found.')
                ], 404);
            }

            $trainingData->update([
                'feedback' => $request->feedback,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Feedback updated successfully.')
            ], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
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
                    'message' => __('No SCORM trainings found for this WhatsApp number.')
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

                    // Notify admin when badge assigned
                    $badgeDetails = Badge::find($badge);
                    sendNotification("Badge '{$badgeDetails->name}' has been awarded to {$rowData->user_whatsapp}", $rowData->company_id);
                }

                $rowData->save();

                setCompanyTimezone($rowData->company_id);

                log_action("{$user} scored {$request->scormTrainingScore}% in training", 'company', $rowData->company_id);

                $passingScore = (int)$rowData->scormTrainingData->passing_score;

                if ($request->scormTrainingScore >= $passingScore) {
                    $rowData->completed = 1;
                    $rowData->completion_date = now()->format('Y-m-d');

                    // Audit log
                    audit_log(
                        $rowData->company_id,
                        null,
                        $rowData->user_whatsapp,
                        'TRAINING_COMPLETED',
                        "{$rowData->user_whatsapp} completed training : '{$rowData->scormTrainingData->name}'.",
                        'bluecollar'
                    );

                    // Notify admin
                    sendNotification("{$user} has completed the ‘{$rowData->scormTrainingData->name}’ training with a score of {$request->scormTrainingScore}%", $rowData->company_id);

                    $totalCompletedTrainings = BlueCollarScormAssignedUser::where('user_whatsapp', $rowData->user_whatsapp)
                        ->where('completed', 1)->count();

                    // This helper function accepts a criteria type and value, and returns the first matching badge
                    $badge = getMatchingBadge('courses_completed', $totalCompletedTrainings);

                    if ($badge) {
                        assignBadge($rowData, $badge);

                        // Notify admin when badge assigned
                        $badgeDetails = Badge::find($badge);
                        sendNotification("Badge '{$badgeDetails->name}' has been awarded to {$rowData->user_whatsapp}", $rowData->company_id);
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

                    $branding = new CheckWhitelabelService($rowData->company_id);
                    $companyLogo = $branding->companyDarkLogo();
                    $favIcon = $branding->companyFavicon();


                    $pdfContent = $blueCollarEmpLearnService->generateScormCertificatePdf($rowData, $companyLogo, $favIcon);

                    $blueCollarEmpLearnService->saveCertificatePdf($pdfContent, $rowData);

                    $rowData->save();

                    // Audit log
                    audit_log(
                        $rowData->company_id,
                        null,
                        $rowData->user_whatsapp,
                        'CERTIFICATE_AWARDED',
                        "Certificate for {$rowData->scormTrainingData->name} has been awarded to {$rowData->user_whatsapp}",
                        'bluecollar'
                    );


                    // Notify admin when certificate assigned
                    sendNotification("Certificate for {$rowData->scormTrainingData->name} has been awarded to {$rowData->user_whatsapp}", $rowData->company_id);

                    if ($whatsapp_response->successful()) {
                        return response()->json(['success' => true, 'message' => __('Score updated')], 200);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => __('Failed to send WhatsApp message')
                        ], 422);
                    }

                    log_action("{$user} scored {$request->scormTrainingScore}% in training", 'company', $rowData->company_id);
                }

                // when user fails then notify admin
                if ($request->scormTrainingScore < $passingScore) {
                    sendNotification("{$rowData->user_whatsapp} failed the '{$rowData->scormTrainingData->name}' training with a score of {$request->scormTrainingScore}.", $rowData->company_id);
                }
            }
            return response()->json(['success' => true, 'message' => __('Score updated')], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
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
                    'message' => __('Scorm not found.')
                ], 404);
            }

            $scormData->update([
                'feedback' => $request->feedback,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Feedback updated successfully.')
            ], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
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

            $allAssignedTrainingMods = BlueCollarTrainingUser::where('user_whatsapp', $request->user_whatsapp)->get();

            $allAssignedScorms = BlueCollarScormAssignedUser::where('user_whatsapp', $request->user_whatsapp)->get();

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
            $blueCollarService = new BlueCollarEmpLearnService();

            return response()->json([
                'success' => true,
                'message' => __('Training grades retrieved successfully'),
                'data' => [
                    'assigned_training_modules' => $assignedTrainingModules ?? [],
                    'total_assigned_training_mod' => count($assignedTrainingModules) + count($assignedScormModules),
                    'avg_training_score' => count($assignedTrainingModules) > 0 ? round(array_sum(array_column($assignedTrainingModules, 'score')) / count($assignedTrainingModules)) : 0,
                    'avg_training_grade' => $avgTrainingGrade,
                    'assigned_scorm_modules' => $assignedScormModules ?? [],
                    'avg_scorm_score' => count($assignedScormModules) > 0 ? round(array_sum(array_column($assignedScormModules, 'score')) / count($assignedScormModules)) : 0,
                    'avg_scorm_grade' => $avgScormGrade,
                    'total_trainings' => count($assignedTrainingModules) + count($assignedScormModules),
                    'total_passed_trainings' => BlueCollarTrainingUser::where('user_whatsapp', $request->user_whatsapp)
                        ->where('completed', 1)->count() + BlueCollarScormAssignedUser::where('user_whatsapp', $request->user_whatsapp)
                        ->where('completed', 1)->count(),
                    'current_avg' => round((
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
                    ))),
                    'security_score' => $blueCollarService->calculateSecurityScore($request->user_whatsapp),
                    'outstanding_trainings' => $blueCollarService->outstandingTrainings($request->user_whatsapp),
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

            $activeNonGameGoals =  BlueCollarTrainingUser::with('trainingData')->where('user_whatsapp', $request->user_whatsapp)->where('training_type', '!=', 'games')->where('completed', 0)->where('training_started', 1)->get();

            $activeGameGoals =  BlueCollarTrainingUser::with('trainingData')->where('user_whatsapp', $request->user_whatsapp)->where('training_type',  'games')->where('completed', 0)->where('training_started', 1)->get();

            $activeGoals = $activeNonGameGoals->concat($activeGameGoals);

            $blueCollarEmpService = new BlueCollarEmpLearnService();

            return response()->json([
                'success' => true,
                'message' => __('Training goals retrieved successfully'),
                'data' => [
                    'all_training_goals' => $trainingGoals ?? [],
                    'all_scorm_goals' => $scormGoals ?? [],
                    'active_goals' => $activeGoals ?? [],
                    'total_active_goals' => count($activeGoals),
                    'total_training_goals' => count($trainingGoals) + count($scormGoals),
                    'avg_in_progress_trainings' => round(BlueCollarTrainingUser::where('user_whatsapp', $request->user_whatsapp)
                        ->where('training_started', 1)
                        ->where('completed', 0)->avg('personal_best') + BlueCollarScormAssignedUser::where('user_whatsapp', $request->user_whatsapp)
                        ->where('training_started', 1)
                        ->where('completed', 0)->avg('personal_best') / 2),
                    'completed_trainings' => $blueCollarEmpService->trainingCompleted($request->user_whatsapp) ?? 0
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
                ->with('trainingData') // eager load training module
                ->get()
                ->map(function ($item) {
                    return [
                        'training_name' => $item->trainingData->name ?? null,
                        'certificate_path' => $item->certificate_path,
                    ];
                });

            $scormCertificates = BlueCollarScormAssignedUser::where('user_whatsapp', $request->user_whatsapp)
                ->where('certificate_path', '!=', null)
                ->with('scormTrainingData')
                ->get()
                ->map(function ($item) {
                    return [
                        'training_name' => $item->scormTrainingData->name ?? null,
                        'certificate_path' => $item->certificate_path,
                    ];
                });

            $blueCollarEmpService = new BlueCollarEmpLearnService();

            return response()->json([
                'success' => true,
                'message' => __('Training achivements retrieved successfully'),
                'data' => [
                    'badges' => $badges,
                    'certificates' => [
                        'certificates' => $certificates,
                        'scorm_certificates' => $scormCertificates
                    ],
                    'completion_rate' => $blueCollarEmpService->trainingCompletionRate($request->user_whatsapp),
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

    public function fetchAssignedGames(Request $request)
    {
        try {
            $request->validate([
                'whatsapp' => 'required',
            ]);

            $user = BlueCollarEmployee::where('whatsapp', $request->whatsapp)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => __('No user found with this number.')
                ], 404);
            }

            $allTrainings = BlueCollarTrainingUser::with('trainingGame')
                ->where('user_whatsapp', $request->whatsapp)
                ->where('training_type', 'games')->get();

            $completedTrainings = BlueCollarTrainingUser::with('trainingGame')
                ->where('user_whatsapp', $request->whatsapp)
                ->where('completed', 1)->where('training_type', 'games')->get();

            $inProgressTrainings = BlueCollarTrainingUser::with('trainingGame')
                ->where('user_whatsapp', $request->whatsapp)
                ->where('training_started', 1)
                ->where('completed', 0)->where('training_type', 'games')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'email' => $request->email,
                    'all_trainings' => $allTrainings,
                    'completed_trainings' => $completedTrainings,
                    'in_progress_trainings' => $inProgressTrainings,
                    'total_trainings' => BlueCollarTrainingUser::with('trainingGame')
                        ->where('user_whatsapp', $request->whatsapp)->count(),
                    'total_trainings' => $allTrainings->count(),
                    'total_completed_trainings' => $completedTrainings->count(),
                    'total_in_progress_trainings' => $inProgressTrainings->count(),
                    'avg_in_progress_trainings' => round(BlueCollarTrainingUser::with('trainingGame')
                        ->where('user_whatsapp', $request->whatsapp)
                        ->where('training_started', 1)
                        ->where('completed', 0)->where('training_type', 'games')->avg('personal_best')),
                ],
                'message' => __('Games fetched successfully for ') . $request->whatsapp
            ], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
            ], 500);
        }
    }

    public function fetchPhishTestResults(Request $request)
    {
        try {
            $request->validate([
                'user_whatsapp' => 'required',
            ]);

            $user = BlueCollarEmployee::where('whatsapp', $request->user_whatsapp)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => __('No user found with this number.')
                ], 404);
            }

            // check whether phishing test results is enabled from company or not
            $phishResultsVisible = PhishSetting::where('company_id', $user->company_id)->value('phish_results_visible');

            if (!$phishResultsVisible) {
                return response()->json([
                    'success' => false,
                    'message' => __('Phishing test results are disable for employees.')
                ], 403);
            }

            $blueCollarEmpService = new BlueCollarEmpLearnService();

            $phishTestResults = [
                'total_simulations' => $blueCollarEmpService->totalSimulations($user),
                'payload_clicked' => $blueCollarEmpService->payloadClicked($user),
                'compromised' => $blueCollarEmpService->compromised($user),
                'compromise_rate' => $blueCollarEmpService->compromiseRate($user),
                'total_reported' => $blueCollarEmpService->callReported($user->whatsapp),
                'assigned_trainings' => $blueCollarEmpService->assignedTrainings($user),
                'total_ignored' => $blueCollarEmpService->totalIgnored($user),
                'ignore_rate' => $blueCollarEmpService->ignoreRate($user),
                'click_rate' => $blueCollarEmpService->clickRate($user),
            ];

            return response()->json([
                'success' => true,
                'message' => __('Phishing test results retrieved successfully'),
                'data' => $phishTestResults
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }
    public function fetchSurveyQuestions(Request $request)
    {
        try {
            $request->validate([
                'user_whatsapp' => 'required|integer|exists:blue_collar_employees,whatsapp',
            ]);

            $user = BlueCollarEmployee::where('whatsapp', $request->user_whatsapp)->first();

            $training_settings = TrainingSetting::where('company_id', $user->company_id)->first();

            if (!$training_settings->content_survey) {
                return response()->json([
                    'success' => false,
                    'message' => __('Content survey is disabled from company.'),
                ], 404);
            }

            $surveyQuestions = $training_settings->survey_questions;

            return response()->json([
                'success' => true,
                'message' => __('Survey questions retrieved successfully.'),
                'data' => $surveyQuestions,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }



    public function tourComplete(Request $request)
    {
        try {

            $request->validate([
                'user_whatsapp' => 'required|string'
            ]);

            $user = Users::where('user_whatsapp', $request->user_whatsapp)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => __('User with this WhatsApp number does not exist')
                ], 404);
            }

            $tour = UserTour::where('company_id', (string) $user->company_id)
                ->where('user_whatsapp', $user->user_whatsapp)
                ->first();

            return response()->json([
                'success' => true,
                'message' => __('Tour status fetched successfully'),
                'tour_completed' => $tour ? (bool) $tour->tour_completed : false
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }


    public function saveTrainingSurveyResponse(Request $request)
    {
        try {
            $request->validate([
                'encoded_id' => 'required',
                'survey_response' => 'required|array',
                'type' => 'required|in:training,scorm'
            ]);

            $rowId = base64_decode($request->encoded_id);

            if ($request->type === 'training') {
                $rowData = BlueCollarTrainingUser::find($rowId);
                $trainingName = $rowData ? $rowData->trainingData->name : null;
            } else {
                $rowData = BlueCollarScormAssignedUser::find($rowId);
                $trainingName = $rowData ? $rowData->scormTrainingData->name : null;
            }

            if (!$rowData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Training record not found'
                ], 404);
            }

            $rowData->survey_response = $request->survey_response;
            $rowData->save();

            audit_log(
                $rowData->company_id,
                null,
                $rowData->user_whatsapp,
                'SURVEY_RESPONSE_SAVED',
                "Survey response saved for training '{$trainingName}'",
                'bluecollar'
            );

            return response()->json([
                'success' => true,
                'message' => __('Survey response saved successfully')
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage(),
            ], 500);
        }
    }
}
