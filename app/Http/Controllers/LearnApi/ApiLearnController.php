<?php

namespace App\Http\Controllers\LearnApi;

use App\Models\Badge;
use App\Models\Users;

use Illuminate\Http\Request;
use App\Models\AssignedPolicy;
use App\Models\TrainingModule;
use Illuminate\Support\Carbon;
use App\Services\CompanyReport;
use App\Services\EmployeeReport;
use App\Models\ComicAssignedUser;
use App\Models\ScormAssignedUser;
use App\Mail\TrainingCompleteMail;
use App\Models\TranslatedTraining;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\TrainingAssignedUser;
use App\Services\TranslationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Services\NormalEmpLearnService;
use Illuminate\Support\Facades\Session;
use App\Services\CheckWhitelabelService;
use App\Services\TrainingAssignedService;
use App\Mail\LearnerSessionRegenerateMail;
use App\Models\PhishSetting;
use App\Models\CompanySettings;
use App\Models\TrainingSetting;
use App\Models\UserTour;
use Illuminate\Container\Attributes\Auth;
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

            $branding = new CheckWhitelabelService($companyId);
            $learn_domain = $branding->learningPortalDomain();
            $companyName = $branding->companyName();
            $companyDarkLogo = $branding->companyDarkLogo();

            if ($branding->isCompanyWhitelabeled()) {
                $branding->updateSmtpConfig();
            } else {
                $branding->clearSmtpConfig();
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

            $employeeReport = new EmployeeReport($request->email, $user->company_id);

            // check if risk information is enabled from company or not
            $riskInfoEnabled = PhishSetting::where('company_id', $user->company_id)->value('risk_information');

            if ($riskInfoEnabled) {
                // Calculate Risk score
                $riskScore = $employeeReport->calculateOverallRiskScore();
                $riskLevel = $this->normalEmpLearnService->calculateRiskLevel($riskScore);
            }

            // Calculate current rank
            $leaderboardRank = $this->normalEmpLearnService->calculateLeaderboardRank($request->email);
            $currentUserRank = $leaderboardRank['current_user_rank'];

            // Get tour_prompt status
            $tourPromptSettings = CompanySettings::where('company_id', $user->company_id)->first();
            $tourPrompt = $tourPromptSettings  ? (int)$tourPromptSettings->tour_prompt : 0;

            // fetch help redirect link from company settings
            $helpRedirectTo = TrainingSetting::where('company_id', $user->company_id)->value('help_redirect_to');
            if (!$helpRedirectTo) {
                $helpRedirectTo = "https://help.simuphish.com";
            }
            $tourPromptSettings = CompanySettings::where('company_id', $user->company_id)->first();
            $tourPrompt =  $tourPromptSettings ? (int)$tourPromptSettings->tour_prompt : 0;

            // check if help redirect destination is set or not
            $helpRedirectTo = TrainingSetting::where('company_id', $user->company_id)->value('help_redirect_to');

            if (!$helpRedirectTo) {
                $helpRedirectTo = 'https://help.simuphish.com';
            }


            return response()->json([
                'success' => true,
                'data' => [
                    'riskScore' => $riskScore ?? null,
                    'riskLevel' => $riskLevel ?? null,
                    'currentUserRank' => $currentUserRank,
                    'tour_prompt' => $tourPrompt,
                    'helpRedirectTo' => $helpRedirectTo,
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

            // Use service to get training data
            $trainingData = $this->normalEmpLearnService->getNormalEmpTrainings($request->email);

            return response()->json([
                'success' => true,
                'data' => $trainingData,
                'message' =>  __('Courses fetched successfully for :email', ['email' => $request->email])
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
                'survey_response' => 'nullable|array',

            ]);

            $row_id = base64_decode($request->encoded_id);

            $rowData = TrainingAssignedUser::with('trainingData')->find($row_id);

            $user = $rowData->user_email;

            if ($request->trainingScore == 0 && $rowData->personal_best == 0) {
                $rowData->grade = 'F';
                $rowData->save();
            }
            $normalEmpLearnService = new NormalEmpLearnService();

            // If user fails then assign alternative training if exists
            $passingScore = (int)$rowData->trainingData->passing_score;


            // Save survey response 

             if ($request->has('survey_response')) {
                $rowData->survey_response = $request->survey_response;
                $rowData->save();
            }
           

            if ($request->trainingScore < $passingScore && $rowData->alt_training != 1) {

                $alterTrainingId = $rowData->trainingData->alternative_training;

                if ($alterTrainingId !== null) {
                    $alterTraining = TrainingModule::find($alterTrainingId);
                    if (!$alterTraining) {
                        return response()->json(['success' => false, 'message' => 'No Alternative Training Found'], 404);
                    }

                    $isAlterTrainingAssigned = TrainingAssignedUser::where('user_email', $rowData->user_email)
                        ->where('training', $alterTrainingId)
                        ->first();

                    if (!$isAlterTrainingAssigned) {
                        $campData = [
                            'campaign_id' => $rowData->campaign_id,
                            'user_id' => $rowData->user_id,
                            'user_name' => $rowData->user_name,
                            'user_email' => $rowData->user_email,
                            'training' => $alterTrainingId,
                            'training_lang' => $rowData->training_lang,
                            'training_type' => 'ai_training',
                            'assigned_date' => $rowData->assigned_date,
                            'training_due_date' => $rowData->training_due_date,
                            'company_id' => $rowData->company_id,
                            'alt_training' => 1
                        ];

                        $trainingAssignedService = new TrainingAssignedService();

                        $trainingAssigned = $trainingAssignedService->assignNewTraining($campData);

                        if ($trainingAssigned['status'] == 1) {
                            $rowData->alt_training = 1;
                            $rowData->save();

                            $module = TrainingModule::find($alterTrainingId);
                            // Audit log
                            audit_log(
                                $rowData->company_id,
                                $rowData->user_email,
                                null,
                                'ALTER_TRAINING_ASSIGNED',
                                "{$module->name} has been assigned to {$rowData->user_email}",
                                'normal'
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
                    sendNotification("Badge '{$badgeDetails->name}' has been awarded to {$rowData->user_email}", $rowData->company_id);
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
                        $user,
                        null,
                        'TRAINING_COMPLETED',
                        "{$user} completed training : '{$rowData->trainingData->name}'.",
                        'normal'
                    );

                    // Notify admin
                    sendNotification("{$user} has completed the ‘{$rowData->trainingData->name}’ training with a score of {$request->trainingScore}%", $rowData->company_id);

                    $totalCompletedTrainings = TrainingAssignedUser::where('user_email', $rowData->user_email)
                        ->where('completed', 1)->count();

                    $badge = getMatchingBadge('courses_completed', $totalCompletedTrainings);
                    // This helper function accepts a criteria type and value, and returns the first matching badge

                    if ($badge) {
                        assignBadge($rowData, $badge);

                        // Notify admin when badge assigned
                        $badgeDetails = Badge::find($badge);
                        sendNotification("Badge '{$badgeDetails->name}' has been awarded to {$rowData->user_email}", $rowData->company_id);
                    }
                    $rowData->save();

                    // Send email
                    $branding = new CheckWhitelabelService($rowData->company_id);
                    $companyName = $branding->companyName();
                    $companyLogo = $branding->companyDarkLogo();
                    $favIcon = $branding->companyFavicon();
                    if ($branding->isCompanyWhitelabeled()) {

                        $branding->updateSmtpConfig();
                    } else {
                        $branding->clearSmtpConfig();
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

                    // Notify admin when certificate assigned
                    sendNotification("Certificate for {$rowData->trainingData->name} has been awarded to {$rowData->user_email}", $rowData->company_id);

                    Mail::to($user)->send(new TrainingCompleteMail($mailData, $pdfContent));
                }

                // when user fails then notify admin
                if ($request->trainingScore < $passingScore) {
                    sendNotification("{$rowData->user_email} failed the '{$rowData->trainingData->name}' training with a score of {$request->trainingScore}.", $rowData->company_id);
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

            $trainingData = TrainingAssignedUser::find($trainingId);

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
                assignGrade($rowData, $request->scormTrainingScore);

                $badge = getMatchingBadge('score', $request->scormTrainingScore);
                // This helper function accepts a criteria type and value, and returns the first matching badge

                if ($badge) {
                    assignBadge($rowData, $badge);

                    // Notify admin when badge assigned
                    $badgeDetails = Badge::find($badge);
                    sendNotification("Badge '{$badgeDetails->name}' has been awarded to {$rowData->user_email}", $rowData->company_id);
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
                        $rowData->user_email,
                        null,
                        'TRAINING_COMPLETED',
                        "{$rowData->user_email} completed training : '{$rowData->scormTrainingData->name}'.",
                        'normal'
                    );

                    // Notify admin
                    sendNotification("{$user} has completed the ‘{$rowData->scormTrainingData->name}’ training with a score of {$request->scormTrainingScore}%", $rowData->company_id);

                    $totalCompletedTrainings = ScormAssignedUser::where('user_email', $rowData->user_email)
                        ->where('completed', 1)->count();

                    // This helper function accepts a criteria type and value, and returns the first matching badge
                    $badge = getMatchingBadge('courses_completed', $totalCompletedTrainings);

                    if ($badge) {
                        assignBadge($rowData, $badge);

                        // Notify admin when badge assigned
                        $badgeDetails  = Badge::find($badge);
                        sendNotification("Badge '{$badgeDetails->name}' has been awarded to {$rowData->user_email} for completing {$totalCompletedTrainings} SCORM courses", $rowData->company_id);
                    }
                    $rowData->save();

                    // Send email
                    $branding = new CheckWhitelabelService($rowData->company_id);
                    $companyName = $branding->companyName();
                    $companyLogo = $branding->companyDarkLogo();
                    $favIcon = $branding->companyFavicon();
                    if ($branding->isCompanyWhitelabeled()) {

                        $branding->updateSmtpConfig();
                    } else {
                        $branding->clearSmtpConfig();
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


                    // Notify admin when certificate assigned
                    sendNotification("Certificate for {$rowData->scormTrainingData->name} has been awarded to {$rowData->user_email}", $rowData->company_id);

                    Mail::to($user)->send(new TrainingCompleteMail($mailData, $pdfContent));
                }

                // when user fails then notify admin
                if ($request->scormTrainingScore < $passingScore) {
                    sendNotification("{$rowData->user_email} failed the '{$rowData->scormTrainingData->name}' training with a score of {$request->scormTrainingScore}.", $rowData->company_id);
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

            $scormData = ScormAssignedUser::find($scormId);

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

            $employeeReport = new EmployeeReport($request->email, $user->company_id);

            return response()->json([
                'success' => true,
                'message' => __('Training grades retrieved successfully'),
                'data' => [
                    'assigned_training_modules' => $assignedTrainingModules ?? [],
                    'total_assigned_training_mod' => $employeeReport->assignedTrainings(),
                    'avg_training_score' => count($assignedTrainingModules) > 0 ? round(array_sum(array_column($assignedTrainingModules, 'score')) / count($assignedTrainingModules)) : 0,
                    'avg_training_grade' => $avgTrainingGrade,
                    'assigned_scorm_modules' => $assignedScormModules ?? [],
                    'avg_scorm_score' => count($assignedScormModules) > 0 ? round(array_sum(array_column($assignedScormModules, 'score')) / count($assignedScormModules)) : 0,
                    'avg_scorm_grade' => $avgScormGrade,
                    'total_trainings' => $employeeReport->assignedTrainings(),
                    'total_passed_trainings' => $employeeReport->trainingCompleted(),
                    'current_avg' => round((
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
                    ))),
                    'security_score' => $employeeReport->calculateSecurityScore(),
                    'outstanding_trainings' => $employeeReport->outstandingTrainings(),
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

            $allEarnedBadges = $this->normalEmpLearnService->getAllEarnedBadges($request->email);
            $badges = $allEarnedBadges['badges'];
            $totalUnlockBadges = $allEarnedBadges['total_unlock_badges'];

            return response()->json([
                'success' => true,
                'message' => __('Badges retrieved successfully'),
                'data' => [
                    'badges' => $badges,
                    'total_badges' => count($badges),
                    'total_unlock_badges' => $totalUnlockBadges
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

            $activeNonGameGoals =  TrainingAssignedUser::with('trainingData')->where('user_email', $request->email)->where('training_type', '!=', 'games')->where('completed', 0)->where('training_started', 1)->get();

            $activeGameGoals =  TrainingAssignedUser::with('trainingData')->where('user_email', $request->email)->where('training_type',  'games')->where('completed', 0)->where('training_started', 1)->get();

            $activeGoals = $activeNonGameGoals->concat($activeGameGoals);

            $user = Users::where('user_email', $request->email)->first();

            $employeeReport = new EmployeeReport($request->email, $user->company_id);

            return response()->json([
                'success' => true,
                'message' => __('Training goals retrieved successfully'),
                'data' => [
                    'all_training_goals' => $trainingGoals ?? [],
                    'all_scorm_goals' => $scormGoals ?? [],
                    'active_goals' => $activeGoals ?? [],
                    'total_active_goals' => count($activeGoals),
                    'total_training_goals' => count($trainingGoals) + count($scormGoals),
                    'avg_in_progress_trainings' => round((TrainingAssignedUser::where('user_email', $request->email)
                        ->where('training_started', 1)
                        ->where('completed', 0)->avg('personal_best') +
                        ScormAssignedUser::where('user_email', $request->email)
                        ->where('scorm_started', 1)
                        ->where('completed', 0)->avg('personal_best')) / 2),
                    'completed_trainings' => $employeeReport->trainingCompleted() ?? 0
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
                ->with('trainingData')
                ->get()
                ->map(function ($item) {
                    return [
                        'training_name' => $item->trainingData->name ?? null,
                        'certificate_path' => $item->certificate_path
                    ];
                });

            $scormCertificates = ScormAssignedUser::where('user_email', $request->email)
                ->where('certificate_path', '!=', null)
                ->with('scormTrainingData')
                ->get()
                ->map(function ($item) {
                    return [
                        'training_name' => $item->scormTrainingData->name ?? null,
                        'certificate_path' => $item->certificate_path
                    ];
                });
            return response()->json([
                'success' => true,
                'message' => __('Training achivements retrieved successfully'),
                'data' => [
                    'badges' => $badges,
                    'certificates' => [
                        'certificates' => $certificates,
                        'scorm_certificates' => $scormCertificates
                    ],
                    'completion_rate' => $this->normalEmpLearnService->calculateCompletionRate($request->email),
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

            //check this training is translated before

            $translatedBefore = TranslatedTraining::where('training_id', $id)
                ->where('language', $training_lang)
                ->first();
            if ($translatedBefore) {
                $trainingData->json_quiz = json_decode($translatedBefore->json_quiz, true);
            } else {
                $translator = new TranslationService();
                $trainingData = $translator->translateTraining($trainingData, $training_lang);
                TranslatedTraining::create([
                    'training_id' => $id,
                    'language' => $training_lang,
                    'json_quiz' => json_encode($trainingData->json_quiz, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            }

            return response()->json(['success' => true, 'message' => __('Training module language changed successfully'), 'data' => $trainingData], 200);
        } catch (\Exception $e) {
            Log::error('loadTraining failed', [
                'error' => $e->getMessage(),
                'training_id' => $training_id,
                'lang' => $training_lang
            ]);
            return response()->json(['success' => false, 'message' => __('An error occurred while loading the training module.')], 422);
        }
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

                return response()->json([
                    'success' => false,
                    'message' => $response->body()
                ], 422);
            }

            $generatedText = $response->json('choices.0.text');

            // Attempt to decode JSON from the response
            $quiz = json_decode($generatedText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to decode JSON from AI response'
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Quiz is generated successfully',
                'data' => [
                    'quiz' => $quiz,
                ]
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function translateAiTraining(Request $request)
    {

        if ($request->lang !== 'en') {

            $translator = new TranslationService();
            $quiz = $translator->translateOnlyQuiz($request->quiz, $request->lang);
        } else {
            $quiz = $request->quiz;
        }
        return response()->json([
            'success' => true,
            'message' => 'Quiz is translated successfully',
            'data' => [
                'quiz' => $quiz,
            ]
        ], 200);
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

    public function fetchAssignedComics(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $assignedComics = ComicAssignedUser::with('comicData')
                ->where('user_email', $request->email)
                ->get();

            return response()->json([
                'success' => true,
                'message' => __('Assigned comics retrieved successfully'),
                'data' => $assignedComics
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function fetchPhishTestResults(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,user_email',
            ]);
            $user = Users::where('user_email', $request->email)->first();

            // check whether phishing test results is enabled from company or not
            $phishResultsVisible = PhishSetting::where('company_id', $user->company_id)->value('phish_results_visible');

            if (!$phishResultsVisible) {
                return response()->json([
                    'success' => false,
                    'message' => __('Phishing test results are disable for employees.')
                ], 403);
            }

            $empReport = new EmployeeReport($request->email, $user->company_id);

            $phishTestResults = [
                'total_simulations' => $empReport->totalSimulations(),
                'payload_clicked' => $empReport->payloadClicked(),
                'compromised' => $empReport->compromised(),
                'compromise_rate' => $empReport->compromiseRate(),
                'total_reported' => $empReport->emailReported(),
                'assigned_trainings' => $empReport->assignedTrainings(),
                'total_ignored' => $empReport->totalIgnored(),
                'ignore_rate' => $empReport->ignoreRate(),
                'click_rate' => $empReport->clickRate(),
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



    public function tourComplete(Request $request)
{
    try {
        $request->validate([
            'company_id' => 'required',
            'user_email' => 'required|email',
        ]);

        // get user tour record
        $tour = UserTour::where('company_id', (string) $request->company_id)
            ->where('user_email', $request->user_email)
            ->first();

        return response()->json([
            'success' => true,
            'message' => __('Tour status fetched successfully'),
            'data' => [
                'company_id'     => (string) $request->company_id,
                'user_email'     => $request->user_email,
                'tour_completed' => $tour ? (bool) $tour->tour_completed : false
            ]
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => __('Something went wrong'),
            'error'   => $e->getMessage()
        ], 500);
    }
}


    public function fetchSurveyQuestions(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,user_email',
            ]);

            $user = Users::where('user_email', $request->email)->first();

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
}
