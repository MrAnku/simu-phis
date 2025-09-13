<?php

namespace App\Http\Controllers\LearnApi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Badge;
use App\Models\Users;
use App\Models\AssignedPolicy;
use App\Models\TrainingModule;
use Illuminate\Support\Carbon;
use App\Models\ScormAssignedUser;
use App\Mail\TrainingCompleteMail;
use Illuminate\Support\Facades\DB;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use App\Services\CheckWhitelabelService;
use App\Mail\LearnerSessionRegenerateMail;
use App\Services\NormalEmpLearnService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ApiLearnController extends Controller
{
    protected $normalEmpLearnService;

    public function __construct()
    {
        $this->normalEmpLearnService = new NormalEmpLearnService();
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

            $employeeType = 'normal';
            $user = Users::where('user_email', $userEmail)->first();

            if (!$user) {
                TrainingAssignedUser::where('user_email', $userEmail)->delete();
                ScormAssignedUser::where('user_email', $userEmail)->delete();
                AssignedPolicy::where('user_email', $userEmail)->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'You are no longer an employee on this platform.'
                ], 404);
            }

            $userName = $user->user_name;

            return response()->json([
                'success' => true,
                'data' => [
                    'email' => $userEmail,
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

    public function createNewToken(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email']);

            $hasTraining = TrainingAssignedUser::where('user_email', $request->email)->first();
            $hasScormAssigned = ScormAssignedUser::where('user_email', $request->email)->first();
            $hasPolicy = AssignedPolicy::where('user_email', $request->email)->first();

            if (!$hasTraining && !$hasPolicy && !$hasScormAssigned) {
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
            if ($hasScormAssigned) {
                $companyId = $hasScormAssigned->company_id;
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

            $learning_dashboard_link = $learn_domain . '/training-dashboard/' . $token;

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
                'company_dark_logo' => $companyDarkLogo,
                'company_id' => $companyId
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

    public function getDashboardMetrics(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,user_email',
            ]);

            $user = Users::where('user_email', $request->email)->first();

            // Calculate Risk score
            $riskData = $this->normalEmpLearnService->calculateRiskScore($user);
            $riskScore = $riskData['riskScore'];
            $riskLevel = $riskData['riskLevel'];

            // Calculate current rank
            $leaderboardRank = $this->normalEmpLearnService->calculateLeaderboardRank($request->email);
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

            $allTrainings = TrainingAssignedUser::with('trainingData')
                ->where('user_email', $request->email)
                ->where('training_type', '!=', 'games')->get();

            $completedTrainings = TrainingAssignedUser::with('trainingData')
                ->where('user_email', $request->email)
                ->where('completed', 1)->where('training_type', '!=', 'games')->get();

            $inProgressTrainings = TrainingAssignedUser::with('trainingData')
                ->where('user_email', $request->email)
                ->where('training_started', 1)
                ->where('completed', 0)->where('training_type', '!=', 'games')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'email' => $request->email,
                    'all_trainings' => $allTrainings,
                    'completed_trainings' => $completedTrainings,
                    'in_progress_trainings' => $inProgressTrainings,
                    'total_trainings' => TrainingAssignedUser::with('trainingData')
                        ->where('user_email', $request->email)->count(),
                    'total_trainings' => $allTrainings->count(),
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

    public function updateTrainingScore(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'trainingScore' => 'required|integer',
                'encoded_id' => 'required',
            ]);

            $row_id = base64_decode($request->encoded_id);

            $rowData = TrainingAssignedUser::with('trainingData')->find($row_id);

            $user = $rowData->user_email;

            if ($request->trainingScore == 0 && $rowData->personal_best == 0) {
                $rowData->grade = 'F';
                $rowData->save();
            }
            $normalEmpLearnService = new NormalEmpLearnService();

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
                        $user,
                        null,
                        'TRAINING_COMPLETED',
                        "{$user} completed training : '{$rowData->trainingData->name}'.",
                        'normal'
                    );

                    $totalCompletedTrainings = TrainingAssignedUser::where('user_email', $rowData->user_email)
                        ->where('completed', 1)->count();

                    $badge = getMatchingBadge('courses_completed', $totalCompletedTrainings);
                    // This helper function accepts a criteria type and value, and returns the first matching badge

                    if ($badge) {
                        assignBadge($rowData, $badge);
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
                        'logo' => $companyLogo,
                        'company_id' => $rowData->company_id
                    ];

                    $pdfContent = $normalEmpLearnService->generateCertificatePdf($rowData, $companyLogo, $favIcon);

                    $normalEmpLearnService->saveCertificatePdf($pdfContent, $rowData);

                    $rowData->save();

                    // Audit log
                    audit_log(
                        $rowData->company_id,
                        $rowData->user_email,
                        null,
                        'CERTIFICATE_AWARDED',
                        "Certificate for {$rowData->trainingData->name} has been awarded to {$rowData->user_email}",
                        'normal'
                    );

                    Mail::to($user)->send(new TrainingCompleteMail($mailData, $pdfContent));
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

            $trainingData = TrainingAssignedUser::find($trainingId);

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

    public function updateScormTrainingScore(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'scormTrainingScore' => 'required|integer',
                'encoded_id' => 'required',
            ]);

            $row_id = base64_decode($request->encoded_id);

            $rowData = ScormAssignedUser::with('scormTrainingData')->find($row_id);
            $user = $rowData->user_email;

            if ($request->scormTrainingScore == 0 && $rowData->personal_best == 0) {
                $rowData->grade = 'F';
                $rowData->save();
            }

            if ($rowData && $request->scormTrainingScore > $rowData->personal_best) {
                // Update the column if the current value is greater
                $rowData->personal_best = $request->scormTrainingScore;

                $normalEmpLearnService = new NormalEmpLearnService();

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
                        $rowData->user_email,
                        null,
                        'TRAINING_COMPLETED',
                        "{$rowData->user_email} completed training : '{$rowData->scormTrainingData->name}'.",
                        'normal'
                    );

                    $totalCompletedTrainings = ScormAssignedUser::where('user_email', $rowData->user_email)
                        ->where('completed', 1)->count();

                    // This helper function accepts a criteria type and value, and returns the first matching badge
                    $badge = getMatchingBadge('courses_completed', $totalCompletedTrainings);

                    if ($badge) {
                        assignBadge($rowData, $badge);
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
                        'training_name' => $rowData->scormTrainingData->name,
                        'training_score' => $request->scormTrainingScore,
                        'company_name' => $companyName,
                        'logo' => $companyLogo,
                        'company_id' => $rowData->company_id
                    ];

                    $pdfContent = $normalEmpLearnService->generateScormCertificatePdf($rowData, $companyLogo, $favIcon);

                    $normalEmpLearnService->saveCertificatePdf($pdfContent, $rowData);

                    $rowData->save();

                    // Audit log
                    audit_log(
                        $rowData->company_id,
                        $rowData->user_email,
                        null,
                        'CERTIFICATE_AWARDED',
                        "Certificate for {$rowData->scormTrainingData->name} has been awarded to {$rowData->user_email}",
                        'normal'
                    );

                    Mail::to($user)->send(new TrainingCompleteMail($mailData, $pdfContent));
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

            $scormData = ScormAssignedUser::find($scormId);

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

            $allTrainings = ScormAssignedUser::with('scormTrainingData')
                ->where('user_email', $request->email)->get();

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
                    'all_trainings' => $allTrainings,
                    'completed_trainings' => $completedTrainings,
                    'in_progress_trainings' => $inProgressTrainings,
                    'total_trainings' => ScormAssignedUser::with('scormTrainingData')
                        ->where('user_email', $email)->count(),
                    'total_trainings' => $allTrainings->count(),
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

            $allAssignedTrainings = $this->normalEmpLearnService->getAllProgressTrainings($request->email);

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

            $leaderboardRank = $this->normalEmpLearnService->calculateLeaderboardRank($request->email);
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
                'email' => 'required|email|exists:users,user_email',
            ]);

            $trainingUsers = TrainingAssignedUser::where('user_email', $request->email)
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

            $avgTrainingGrade = $this->normalEmpLearnService->getGrade($avgTrainingScore);

            $scormUsers = ScormAssignedUser::where('user_email', $request->email)
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

            $avgScormGrade = $this->normalEmpLearnService->getGrade($avgScormScore);

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
                    'total_trainings' => TrainingAssignedUser::where('user_email', $request->email)->count() + ScormAssignedUser::where('user_email', $request->email)->count(),
                    'total_passed_trainings' => TrainingAssignedUser::where('user_email', $request->email)
                        ->where('completed', 1)->count() + ScormAssignedUser::where('user_email', $request->email)
                        ->where('completed', 1)->count(),
                    'current_avg' => (
                        TrainingAssignedUser::where('user_email', $request->email)
                        ->where('training_started', 1)
                        ->sum('personal_best')
                        +
                        ScormAssignedUser::where('user_email', $request->email)
                        ->where('scorm_started', 1)
                        ->sum('personal_best')
                    ) / max(1, (
                        TrainingAssignedUser::where('user_email', $request->email)
                        ->where('training_started', 1)
                        ->count()
                        +
                        ScormAssignedUser::where('user_email', $request->email)
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
                'email' => 'required|email|exists:users,user_email',
            ]);

            $nonGameGoals = TrainingAssignedUser::with('trainingData')->where('user_email', $request->email)->where('training_type', '!=', 'games')->where('personal_best', '<', 70)->get();

            $gameGoals = TrainingAssignedUser::with('trainingGame')->where('user_email', $request->email)->where('training_type',  'games')->where('personal_best', '<', 70)->get();

            $trainingGoals = $nonGameGoals->concat($gameGoals);

            $scormGoals = ScormAssignedUser::with('scormTrainingData')->where('user_email', $request->email)->where('personal_best', '<', 70)->get();

            $activeNonGameGoals =  TrainingAssignedUser::with('trainingData')->where('user_email', $request->email)->where('training_type', '!=', 'games')->where('personal_best', '<', 70)->where('training_started', 1)->get();

            $activeGameGoals =  TrainingAssignedUser::with('trainingData')->where('user_email', $request->email)->where('training_type',  'games')->where('personal_best', '<', 70)->where('training_started', 1)->get();

            $activeGoals = $activeNonGameGoals->concat($activeGameGoals);

            return response()->json([
                'success' => true,
                'message' => __('Training goals retrieved successfully'),
                'data' => [
                    'all_training_goals' => $trainingGoals ?? [],
                    'all_scorm_goals' => $scormGoals ?? [],
                    'active_goals' => $activeGoals ?? [],
                    'total_active_goals' => count($activeGoals),
                    'avg_progress_training' => round(TrainingAssignedUser::where('user_email', $request->email)
                        ->where('training_started', 1)
                        ->where('completed', 1)->avg('personal_best')),
                    'total_training_goals' => count($trainingGoals) + count($scormGoals),
                    'avg_in_progress_trainings' => round(TrainingAssignedUser::where('user_email', $request->email)
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

    public function fetchAllAssignedTrainings(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,user_email',
            ]);

            $assignedTrainings = TrainingAssignedUser::where('user_email', $request->email)
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

            $assignedScorm = ScormAssignedUser::where('user_email', $request->email)
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

    public function startScorm(Request $request)
    {
        try {
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

    public function changeTrainingLang(Request $request)
    {
        try {
            $training_id = $request->query('training_id');
            $training_lang = $request->query('training_lang');

            // Decode the ID
            $id = base64_decode($training_id);

            // Validate the ID
            if ($id === false) {
                return response()->json(['success' => false, 'message' => __('Invalid training module ID.')], 422);
            }

            // Fetch the training data
            $trainingData = TrainingModule::find($id);

            // Check if the training module exists
            if (!$trainingData) {
                return response()->json(['success' => false, 'message' => __('Training Module Not Found')], 422);
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

                return response()->json(['success' => true, 'message' => __('Converted Json retreived successfully'), 'data' => $trainingData], 200);

                // return response()->json(['status' => 1, 'jsonData' => $trainingData]);
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

                        return response()->json(['success' => true, 'message' => __('Converted Json Quiz retreived successfully'), 'data' => $trainingData->json_quiz], 200);


                        // return response()->json(['status' => 1, 'jsonData' => $trainingData->json_quiz]);
                    }
                }

                return response()->json(['success' => true, 'message' => __('Converted Json Quiz retreived successfully'), 'data' => $trainingData->json_quiz], 200);


                // return response()->json(['status' => 1, 'jsonData' => $trainingData->json_quiz]);
            }
        } catch (\Exception $e) {
            Log::error('loadTraining failed', [
                'error' => $e->getMessage(),
                'training_id' => $training_id,
                'lang' => $training_lang
            ]);
            return response()->json(['success' => false, 'message' => __('An error occurred while loading the training module.')], 422);

            // return response()->json(['status' => 0, 'msg' => 'An error occurred while loading the training module.']);
        }
    }

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

                return response()->json(['success' => false, 'message' => 'Translation service failed. Status: ' . $response->status(), 'data' => $errorDetails],  422);


                // return response()->json([
                //     'status' => 0,
                //     'msg' => 'Translation service failed. Status: ' . $response->status(),
                //     'debug' => $errorDetails
                // ]);
            }

            $responseData = $response->json();

            // Debug full response
            Log::info("Full API Response", ['response' => $responseData]);

            if (!isset($responseData['choices'][0]['message']['content'])) {
                Log::error("Invalid API response structure", ['response' => $responseData]);
                return response()->json(['success' => false, 'message' => 'Invalid response structure from translation service', 'data' => $responseData],  422);

                // return response()->json([
                //     'status' => 0,
                //     'msg' => 'Invalid response structure from translation service',
                //     'debug' => $responseData
                // ]);
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
                        return response()->json(['success' => false, 'message' => 'Invalid JSON returned: ' . json_last_error_msg(), 'data' => [
                            'original_content' => $translatedContent,
                            'fixed_content' => $fixedContent
                        ]],  422);


                        // return response()->json([
                        //     'status' => 0,
                        //     'msg' => 'Invalid JSON returned: ' . json_last_error_msg(),
                        //     'debug' => [
                        //         'original_content' => $translatedContent,
                        //         'fixed_content' => $fixedContent
                        //     ]
                        // ]);
                    }
                } else {
                    return response()->json(['success' => false, 'message' => 'Invalid JSON returned: ' . json_last_error_msg(), 'data' => ['content' => $translatedContent]],  422);

                    // return response()->json([
                    //     'status' => 0,
                    //     'msg' => 'Invalid JSON returned: ' . json_last_error_msg(),
                    //     'debug' => ['content' => $translatedContent]
                    // ]);
                }
            }

            // Validate that we actually got translations
            if ($isAmharic && $this->validateAmharicTranslation($json, $translatedData)) {
                Log::info("Amharic translation validation passed");
            } elseif ($isAmharic) {
                Log::warning("Amharic translation may not contain proper Amharic text");
            }

            log_action("JSON data successfully translated to {$langName}", 'learner', 'learner');
            return response()->json(['success' => true, 'message' => 'Translated Json retreived successfully', 'data' => $translatedData],  200);

            // return response()->json([
            //     'status' => 1,
            //     'jsonData' => $translatedData,
            // ]);
        } catch (\Exception $e) {
            Log::error("Translation exception", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'lang' => $lang,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            log_action("Exception during JSON translation: " . $e->getMessage(), 'learner', 'learner');

            return response()->json(['success' => false, 'message' => 'Translation failed: ' . $e->getMessage(), 'data' => [
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]],  422);

            // return response()->json([
            //     'status' => 0,
            //     'msg' => 'Translation failed: ' . $e->getMessage(),
            //     'debug' => [
            //         'exception_class' => get_class($e),
            //         'file' => $e->getFile(),
            //         'line' => $e->getLine()
            //     ]
            // ]);
        }
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

    public function fetchAssignedGames(Request $request)
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

            $allTrainings = TrainingAssignedUser::with('trainingGame')
                ->where('user_email', $request->email)
                ->where('training_type', 'games')->get();

            $completedTrainings = TrainingAssignedUser::with('trainingGame')
                ->where('user_email', $request->email)
                ->where('completed', 1)->where('training_type', 'games')->get();

            $inProgressTrainings = TrainingAssignedUser::with('trainingGame')
                ->where('user_email', $request->email)
                ->where('training_started', 1)
                ->where('completed', 0)->where('training_type', 'games')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'email' => $request->email,
                    'all_trainings' => $allTrainings,
                    'completed_trainings' => $completedTrainings,
                    'in_progress_trainings' => $inProgressTrainings,
                    'total_trainings' => TrainingAssignedUser::with('trainingGame')
                        ->where('user_email', $request->email)->count(),
                    'total_trainings' => $allTrainings->count(),
                    'total_completed_trainings' => $completedTrainings->count(),
                    'total_in_progress_trainings' => $inProgressTrainings->count(),
                    'avg_in_progress_trainings' => round(TrainingAssignedUser::with('trainingGame')
                        ->where('user_email', $request->email)
                        ->where('training_started', 1)
                        ->where('completed', 0)->where('training_type', 'games')->avg('personal_best')),
                ],
                'message' => 'Games fetched successfully for ' . $request->email
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

    public function updateGameScore(Request $request)
    {
        try {
            $assignedUserId = base64_decode($request->assignedUserId);
            $assignedUser = TrainingAssignedUser::where('id', $assignedUserId)->first();
            if ($assignedUser->personal_best < $request->score) {
                $assignedUser->personal_best = $request->score;
            }
            $assignedUser->game_time = $request->timeConsumed;

            // Assign Grade based on score
            if ($request->score >= 90) {
                $assignedUser->grade = 'A+';
            } elseif ($request->score >= 80) {
                $assignedUser->grade = 'A';
            } elseif ($request->score >= 70) {
                $assignedUser->grade = 'B';
            } elseif ($request->score >= 60) {
                $assignedUser->grade = 'C';
            } else {
                $assignedUser->grade = 'D';
            }

            $badge = getMatchingBadge('score', $request->score);
            // This helper function accepts a criteria type and value, and returns the first matching badge

            if ($badge) {
                // Decode existing badges (or empty array if null)
                $existingBadges = json_decode($assignedUser->badge, true) ?? [];

                // Avoid duplicates
                if (!in_array($badge, $existingBadges)) {
                    $existingBadges[] = $badge; // Add new badge
                }

                // Save back to the model
                $assignedUser->badge = json_encode($existingBadges);
            }

            if ($assignedUser->trainingGame->passing_score <= $request->score) {
                $assignedUser->completed = 1;
                $assignedUser->completion_date = now()->format('Y-m-d');

                $totalCompletedTrainings = TrainingAssignedUser::where('user_email', $assignedUser->user_email)
                    ->where('completed', 1)->count();

                $badge = getMatchingBadge('courses_completed', $totalCompletedTrainings);
                // This helper function accepts a criteria type and value, and returns the first matching badge

                if ($badge) {
                    // Decode existing badges (or empty array if null)
                    $existingBadges = json_decode($assignedUser->badge, true) ?? [];

                    // Avoid duplicates
                    if (!in_array($badge, $existingBadges)) {
                        $existingBadges[] = $badge; // Add new badge
                    }

                    // Save back to the model
                    $assignedUser->badge = json_encode($existingBadges);
                }
            }
            $assignedUser->save();
            return response()->json([
                'success' => true,
                'message' => 'Game Score updated successfully'
            ], 200);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }


    public function generateAiTraining(Request $request)
    {
        try {
            $topic = $request->topic;
            $prompt = "
        Create a JSON array of quiz questions about $topic for phishing awareness training. 
        The JSON should include the following fields for each question:
        
        1. qtype (e.g., \"multipleChoice\" or \"trueFalse\")
        2. question (the text of the question)
        3. option1, option2, option3, option4 (for multiple-choice questions)
        4. correctOption (e.g., \"option2\")
        5. ansDesc (a detailed explanation of the correct answer)
        
        Ensure the questions are clear, concise, and relevant to the topic of $topic. Include at least one true/false question.
        ";

            $response = Http::withOptions(['verify' => false])->withHeaders([
                'Authorization' => 'Bearer ' . env("OPENAI_API_KEY"),
            ])->post('https://api.openai.com/v1/completions', [
                'model' => 'gpt-3.5-turbo-instruct',
                'prompt' => $prompt,
                'max_tokens' => 1500,
                'temperature' => 0.7,
            ]);

            if ($response->failed()) {

                log_action("Failed to generate AI Training on topic {$topic}", 'learner', 'learner');

                return response()->json([
                    'success' => false,
                    'message' => $response->body()
                ], 422);
            }

            $generatedText = $response->json('choices.0.text');

            // Attempt to decode JSON from the response
            $quiz = json_decode($generatedText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {

                log_action("Failed to decode JSON from AI Training on topic {$topic}", 'learner', 'learner');

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to decode JSON from AI response'
                ], 422);
            }

            log_action("AI Training generated on topic: {$topic}", 'learner', 'learner');

            return response()->json([
                'success' => true,
                'message' => 'Quiz is generated successfully',
                'data' => [
                    'quiz' => $quiz,
                ]
            ], 200);
        } catch (\Exception $e) {

            log_action("Failed to generate AI Training on topic {$topic}", 'learner', 'learner');

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function translateAiTraining(Request $request)
    {

        if ($request->lang !== 'en') {

            // $translatedArray = translateArrayValues($request->quiz, $request->lang);
            // $translatedQuizJson = json_encode($translatedArray, JSON_UNESCAPED_UNICODE);

            //translation using ai
            $quiz = json_encode($request->quiz, JSON_UNESCAPED_UNICODE);
            $quiz = translateQuizUsingAi($quiz, $request->lang);
            // $translatedQuizJson = json_encode($quiz, JSON_UNESCAPED_UNICODE);
            return response()->json([
                'success' => true,
                'message' => 'Quiz is translated successfully',
                'data' => [
                    'quiz' => $quiz,
                ]
            ], 200);
        }
        return response()->json([
            'success' => false,
            'message' => 'Unable to translate in english',
        ], 422);
    }

    public function fetchLanguages()
    {
        $languages = getLanguages();

        return response()->json([
            'success' => true,
            'message' => __('Languages fetched successfully.'),
            'data' => [
                "languages" => $languages
            ],
        ], 200);
    }
}
