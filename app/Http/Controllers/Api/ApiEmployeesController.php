<?php

namespace App\Http\Controllers\Api;

use App\Models\Users;
use App\Models\Campaign;
use App\Models\UsersGroup;
use App\Models\CampaignLive;
use Illuminate\Http\Request;
use App\Models\AiCallCampaign;
use App\Models\AiCallCampLive;
use App\Models\AssignedPolicy;
use App\Models\DomainVerified;
use App\Models\OutlookAdToken;
use App\Models\WaLiveCampaign;
use App\Models\BlueCollarGroup;
use App\Models\DeletedEmployee;
use App\Models\QuishingLiveCamp;
use App\Services\EmployeeReport;
use App\Services\EmployeeService;
use App\Models\PolicyCampaignLive;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\LearnApi\ApiLearnController;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\InfoGraphicLiveCampaign;
use App\Services\CheckWhitelabelService;
use App\Services\NormalEmpLearnService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ApiEmployeesController extends Controller
{
    public function index()
    {
        try {
            $companyId = Auth::user()->company_id;
            $groups = UsersGroup::where('company_id', $companyId)->get();

            $totalEmps = Users::where('company_id', $companyId)->pluck('user_email')->unique()->count();
            $verifiedDomains = DomainVerified::where('verified', 1)->where('company_id', $companyId)->get();
            $notVerifiedDomains = DomainVerified::where('verified', 0)->where('company_id', $companyId)->get();

            $allDomains = DomainVerified::where('company_id', $companyId)->get();

            $hasOutlookAdToken = OutlookAdToken::where('company_id', $companyId)->exists();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_employees' => $totalEmps,
                    'total_emps_pp' => $this->getEmpPp(),
                    'groups' => $groups,
                    'verified_domains' => $verifiedDomains,
                    'verified_domains_pp' => $this->getVerifiedDomainsPp(),
                    'not_verified_domains' => $notVerifiedDomains,
                    'not_verified_domains_pp' => $this->getNotVerifiedDomainsPp(),
                    'all_domains' => $allDomains,
                    'has_outlook_ad_token' => $hasOutlookAdToken
                ],
                'message' => __('Employee data retrieved successfully')
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error:') . $e->getMessage()], 500);
        }
    }

    public function verifiedDomains()
    {
        try {
            $companyId = Auth::user()->company_id;
            $verifiedDomains = DomainVerified::where('verified', 1)->where('company_id', $companyId)->get();

            return response()->json([
                'success' => true,
                'data' => $verifiedDomains,
                'message' => __('Verified domains retrieved successfully')
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error:') . $e->getMessage()], 500);
        }
    }

    public function getGroups()
    {
        try {
            $companyId = Auth::user()->company_id;
            $groups = UsersGroup::where('company_id', $companyId)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $groups,
                'message' => __('User groups retrieved successfully')
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error:') . $e->getMessage()], 500);
        }
    }

    private function getEmpPp()
    {
        $companyId = Auth::user()->company_id;

        // Current 14 days
        $now = now();
        $startCurrent = $now->copy()->subDays(13)->startOfDay();
        $endCurrent = $now->endOfDay();

        $currentCount = Users::where('company_id', $companyId)
            ->whereBetween('created_at', [$startCurrent, $endCurrent])
            ->distinct('user_email')
            ->count('user_email');

        // Previous 14 days
        $startPrev = $now->copy()->subDays(27)->startOfDay();
        $endPrev = $now->copy()->subDays(14)->endOfDay();

        $prevCount = Users::where('company_id', $companyId)
            ->whereBetween('created_at', [$startPrev, $endPrev])
            ->distinct('user_email')
            ->count('user_email');

        // Calculate percent change
        if ($prevCount == 0 && $currentCount == 0) {
            $percent = 0;
        } elseif ($prevCount == 0) {
            $percent = 100;
        } else {
            $percent = (($currentCount - $prevCount) / $prevCount) * 100;
        }

        return round($percent, 2);
    }

    private function getVerifiedDomainsPp()
    {
        $companyId = Auth::user()->company_id;

        // Current 14 days
        $now = now();
        $startCurrent = $now->copy()->subDays(13)->startOfDay();
        $endCurrent = $now->endOfDay();

        $currentCount = DomainVerified::where('company_id', $companyId)
            ->where('verified', 1)
            ->whereBetween('created_at', [$startCurrent, $endCurrent])
            ->count();

        // Previous 14 days
        $startPrev = $now->copy()->subDays(27)->startOfDay();
        $endPrev = $now->copy()->subDays(14)->endOfDay();

        $prevCount = DomainVerified::where('company_id', $companyId)
            ->where('verified', 1)
            ->whereBetween('created_at', [$startPrev, $endPrev])
            ->count();

        // Calculate percent change
        if ($prevCount == 0 && $currentCount == 0) {
            $percent = 0;
        } elseif ($prevCount == 0) {
            $percent = 100;
        } else {
            $percent = (($currentCount - $prevCount) / $prevCount) * 100;
        }

        return round($percent, 2);
    }

    private function getNotVerifiedDomainsPp()
    {

        $companyId = Auth::user()->company_id;

        // Current 14 days
        $now = now();
        $startCurrent = $now->copy()->subDays(13)->startOfDay();
        $endCurrent = $now->endOfDay();

        $currentCount = DomainVerified::where('company_id', $companyId)
            ->where('verified', 0)
            ->whereBetween('created_at', [$startCurrent, $endCurrent])
            ->count();

        // Previous 14 days
        $startPrev = $now->copy()->subDays(27)->startOfDay();
        $endPrev = $now->copy()->subDays(14)->endOfDay();

        $prevCount = DomainVerified::where('company_id', $companyId)
            ->where('verified', 0)
            ->whereBetween('created_at', [$startPrev, $endPrev])
            ->count();

        // Calculate percent change
        if ($prevCount == 0 && $currentCount == 0) {
            $percent = 0;
        } elseif ($prevCount == 0) {
            $percent = 100;
        } else {
            $percent = (($currentCount - $prevCount) / $prevCount) * 100;
        }

        return round($percent, 2);
    }

    public function allEmployee()
    {
        try {
            $companyId = Auth::user()->company_id;

            $totalEmps = Users::where('company_id', $companyId)->pluck('user_email')->unique()->count();
            $verifiedDomains = DomainVerified::where('verified', 1)->where('company_id', $companyId)->get();
            $notVerifiedDomains = DomainVerified::where('verified', 0)->where('company_id', $companyId)->get();

            $allDomains = DomainVerified::where('company_id', $companyId)->get();
            $allEmployees = Users::where('company_id', $companyId)
                ->whereIn('id', function ($query) {
                    $query->selectRaw('MAX(id)')
                        ->from('users')
                        ->groupBy('user_email');
                })
                ->get();

            $allEmployees->map(function ($employee) {
                $empReport = new EmployeeReport($employee->user_email, $employee->company_id);
                $employee->risk_score = $empReport->calculateOverallRiskScore();
                return $employee;
            });

            $hasOutlookAdToken = OutlookAdToken::where('company_id', $companyId)->exists();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_employees' => $totalEmps,
                    'verified_domains' => $verifiedDomains,
                    'not_verified_domains' => $notVerifiedDomains,
                    'all_domains' => $allDomains,
                    'has_outlook_ad_token' => $hasOutlookAdToken,
                    'all_employees' => $allEmployees
                ],
                'message' => __('All employee data retrieved successfully')
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function employeeDetail(Request $request)
    {
        try {
            $base_encode_id = $request->route('base_encode_id');
            if (!$base_encode_id) {
                return response()->json(['success' => false, 'message' => __('ID is required')], 422);
            }
            $companyId = Auth::user()->company_id;
            $id = base64_decode($base_encode_id);
            $employee = Users::with(['campaigns', 'assignedTrainings', 'whatsappCamps', 'aiCalls'])->where('id', $id)->where('company_id', $companyId)->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => __('Employee not found')
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => $employee,
                'message' => __('Employee details retrieved successfully')
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }



    public function employeeDetailNew(Request $request)
    {
        try {
            $email = $request->route('email');
            if (!$email) {
                return response()->json(['success' => false, 'message' => __('Email is required')], 422);
            }
            $companyId = Auth::user()->company_id;

            $exist = Users::where('user_email', $email)
                ->where('company_id', $companyId)
                ->first();
            if (!$exist) {
                return response()->json(['success' => false, 'message' => __('Employee not found')], 404);
            }
            $empReport = new EmployeeReport($email, $companyId);
            $acceptedPolicies = $empReport->policiesAcceptedNames();

            $normalEmpLearnService = new NormalEmpLearnService();
            $leaderboardDetails = $normalEmpLearnService->calculateLeaderboardRank($email);

            $empBadgesData = $normalEmpLearnService->getAllEarnedBadges($email);

            $data = [
                'personal' => $exist,
                'campaigns' => $this->getCampaigns($email),
                'training_assigned' => $this->getTrainingAssigned($email),
                'security_score' => $this->getSecurityScore($email),
                'emails_viewed' => $this->getEmailViewed($email),
                'accepted_policies' => $acceptedPolicies,
                'leaderboard_details' => $leaderboardDetails,
                'employee_badges' => $empBadgesData,
            ];

            $aiAnalysis = $this->getAIAnalysis($data);

            return response()->json([
                'success' => true,
                'data' => $data,
                'ai_analysis' => $aiAnalysis,
                'message' => __('Employee details retrieved successfully')
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    private function getAIAnalysis($data)
    {
        try {
            $openaiApiKey = env('OPENAI_API_KEY');

            if (!$openaiApiKey) {
                // Fallback to manual analysis if API key not configured
                return $this->getFallbackAnalysis($data);
            }

            // Prepare data for AI analysis
            $analysisData = [
                'security_score' => $data['security_score']['security_score'] ?? null,
                'risk_score' => $data['security_score']['risk_score'] ?? null,
                'total_simulations' => $data['security_score']['total_simulations'] ?? null,
                'compromised_simulations' => $data['security_score']['compromised_simulations'] ?? null,
                'email_compromises' => $data['campaigns']['email_summary']['in_attack'] ?? 0,
                'quishing_compromises' => $data['campaigns']['quishing_summary']['in_attack'] ?? 0,
                'whatsapp_compromises' => $data['campaigns']['whatsapp_summary']['in_attack'] ?? 0,
                'ai_call_responses' => $data['campaigns']['ai_call_summary']['total_calls_responded'] ?? 0,
                'overdue_trainings' => $data['training_assigned']['overdue_trainings'] ?? 0,
                'employee_name' => $data['personal']['user_name'] ?? 'Employee',
            ];

            $prompt = "Analyze this employee's cybersecurity behavior and provide 6-7 key insights as a JSON array of strings. Data: " . json_encode($analysisData) .
                "\n\nAnalysis should cover: current security posture, vulnerabilities, training compliance, recommendations, and overall risk. " .
                "If security score > 85% with low compromises, include positive reinforcement. " .
                "Return only a JSON array of strings, maximum 7 items.";

            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $openaiApiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a cybersecurity expert analyzing employee security behavior. Respond ONLY with a valid JSON array of strings.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'max_tokens' => 300,   // smaller cap, since we only need ~7 sentences
                    'temperature' => 0.3   // lower for more consistent JSON output
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $content = $result['choices'][0]['message']['content'] ?? null;

                if ($content) {
                    // Try to extract JSON array safely
                    preg_match('/\[[\s\S]*\]/', $content, $matches);

                    if ($matches) {
                        $aiAnalysis = json_decode($matches[0], true);

                        if (is_array($aiAnalysis)) {
                            return array_slice($aiAnalysis, 0, 7);
                        }
                    }
                }
            }

            // Fallback if AI response is invalid

            return $this->getFallbackAnalysis($data);
        } catch (\Exception $e) {
            return $this->getFallbackAnalysis($data);
        }
    }



    private function getFallbackAnalysis($data)
    {
        $analysis = [];

        $totalSimulations = $data['security_score']['total_simulations'];
        $compromisedCount = $data['security_score']['compromised_simulations'];
        $securityScore = $data['security_score']['security_score'];
        $overdueTrainings = $data['training_assigned']['overdue_trainings'];

        if ($totalSimulations == 0) {
            $analysis[] = "Employee has not participated in any security simulations yet. Consider enrolling them in upcoming campaigns.";
        } elseif ($securityScore >= 85) {
            return ["Excellent security awareness demonstrated! This employee maintains strong cybersecurity practices and serves as a model for the organization."];
        } else {
            if ($securityScore < 50) {
                $analysis[] = "CRITICAL: High vulnerability detected with {$compromisedCount} compromises out of {$totalSimulations} simulations. Immediate intervention required.";
            }

            if ($data['campaigns']['email_summary']['in_attack'] > 0) {
                $analysis[] = "Email security training needed - employee fell for phishing attempts.";
            }

            if ($overdueTrainings > 0) {
                $analysis[] = "Training compliance issue: {$overdueTrainings} modules overdue.";
            }
        }

        return array_slice($analysis, 0, 7);
    }

    private function getEmailViewed($email)
    {
        return CampaignLive::where('user_email', $email)->where('mail_open', 1)->count() + QuishingLiveCamp::where('user_email', $email)->where('mail_open', '1')->count();
    }

    private function getCampaigns($email)
    {

        // Email Campaigns Summary
        $emailSummary = [
            'type' => 'email_summary',
            'total_campaigns' => CampaignLive::where('user_email', $email)->count(),
            'total_payload_clicked' => CampaignLive::where('user_email', $email)->where('payload_clicked', 1)->count(),
            'in_attack' => CampaignLive::where('user_email', $email)->where('emp_compromised', 1)->count(),
        ];



        // Quishing Campaigns Summary
        $quishingSummary = [
            'type' => 'quishing_summary',
            'total_campaigns' => QuishingLiveCamp::where('user_email', $email)->count(),
            'total_qr_scanned' => QuishingLiveCamp::where('user_email', $email)->where('qr_scanned', '1')->count(),
            'in_attack' => QuishingLiveCamp::where('user_email', $email)->where('compromised', '1')->count(),
        ];



        // WhatsApp Campaigns Summary
        $waSummary = [
            'type' => 'whatsapp_summary',
            'total_campaigns' => WaLiveCampaign::where('user_email', $email)->count(),
            'total_payload_clicked' => WaLiveCampaign::where('user_email', $email)->where('payload_clicked', 1)->count(),
            'in_attack' => WaLiveCampaign::where('user_email', $email)->where('compromised', 1)->count(),
        ];
        // AI Call Campaigns Summary
        $aiCallSummary = [
            'type' => 'ai_call_summary',
            'total_campaigns' => AiCallCampLive::where('user_email', $email)->count(),
            'total_calls_sent' => AiCallCampLive::where('user_email', $email)->where('call_send_response', '!=', null)->count(),
            'total_calls_responded' => AiCallCampLive::where('user_email', $email)->where('call_end_response', '!=', null)->count(),
            'in_attack' => AiCallCampLive::where('user_email', $email)->where('compromised', 1)->count()
        ];

        return [
            'email_summary' => $emailSummary,
            'quishing_summary' => $quishingSummary,
            'whatsapp_summary' => $waSummary,
            'ai_call_summary' => $aiCallSummary,
        ];
    }

    private function getTrainingAssigned($email)
    {
        // total static training assigned
        $staticTrainingAssigned = TrainingAssignedUser::where('user_email', $email)
            ->where('training_type', 'static_training')
            ->count();

        //total ai training assigned
        $aiTrainingAssigned = TrainingAssignedUser::where('user_email', $email)
            ->where('training_type', 'ai_training')
            ->count();

        // total gamified training assigned
        $gamifiedTrainingAssigned = TrainingAssignedUser::where('user_email', $email)
            ->where('training_type', 'gamified')
            ->count();

        //total games assigned
        $gamesAssigned = TrainingAssignedUser::where('user_email', $email)
            ->where('training_type', 'games')
            ->count();

        // trainings overdue
        $overdueTrainings = TrainingAssignedUser::where('user_email', $email)
            ->whereDate('training_due_date', '<', now())
            ->where('personal_best', 0)
            ->count();

        return [
            'static_training_assigned' => $staticTrainingAssigned,
            'ai_training_assigned' => $aiTrainingAssigned,
            'gamified_training_assigned' => $gamifiedTrainingAssigned,
            'games_assigned' => $gamesAssigned,
            'overdue_trainings' => $overdueTrainings
        ];
    }

    private function getSecurityScore($email)
    {
        $companyId = Auth::user()->company_id;

        // Email Campaigns
        // $emailTotal = CampaignLive::where('user_email', $email)->count();
        // $emailCompromised = CampaignLive::where('user_email', $email)->where('emp_compromised', 1)->count();
        // $emailPayloadClicked = CampaignLive::where('user_email', $email)->where('payload_clicked', 1)->count();
        // $emailRisky = CampaignLive::where('user_email', $email)
        //     ->where(function ($q) {
        //         $q->where('emp_compromised', 1)->orWhere('payload_clicked', 1);
        //     })->count();

        // // Quishing Campaigns
        // $quishingTotal = QuishingLiveCamp::where('user_email', $email)->count();
        // $quishingCompromised = QuishingLiveCamp::where('user_email', $email)->where('compromised', 1)->count();
        // $quishingPayloadClicked = QuishingLiveCamp::where('user_email', $email)->where('qr_scanned', '1')->count();
        // $quishingRisky = QuishingLiveCamp::where('user_email', $email)
        //     ->where(function ($q) {
        //         $q->where('compromised', 1)->orWhere('qr_scanned', '1');
        //     })->count();

        // // WhatsApp Campaigns
        // $waTotal = WaLiveCampaign::where('user_email', $email)->count();
        // $waCompromised = WaLiveCampaign::where('user_email', $email)->where('compromised', 1)->count();
        // $waPayloadClicked = WaLiveCampaign::where('user_email', $email)->where('payload_clicked', 1)->count();
        // $waRisky = WaLiveCampaign::where('user_email', $email)
        //     ->where(function ($q) {
        //         $q->where('compromised', 1)->orWhere('payload_clicked', 1);
        //     })->count();

        // // AI Call Campaigns
        // $aiTotal = AiCallCampLive::where('user_email', $email)->count();
        // $aiCompromised = AiCallCampLive::where('user_email', $email)->where('compromised', 1)->count();
        // $aiRisky = $aiCompromised; // Only compromised is risky for AI calls

        // $totalSimulations = $emailTotal + $quishingTotal + $waTotal + $aiTotal;
        // $compromisedSimulations = $emailCompromised + $quishingCompromised + $waCompromised + $aiCompromised;
        // $payloadClickedSimulations = $emailPayloadClicked + $quishingPayloadClicked + $waPayloadClicked;
        // $totalRiskyActions = $emailRisky + $quishingRisky + $waRisky + $aiRisky;

        // if ($totalSimulations > 0) {
        //     $safeSimulations = $totalSimulations - $totalRiskyActions;
        //     $percentage = round(($safeSimulations / $totalSimulations) * 100, 2);
        //     $riskScore = round(($totalRiskyActions / $totalSimulations) * 100, 2);
        // } else {
        //     $percentage = 100;
        //     $riskScore = 0;
        // }

        $employeeReport = new EmployeeReport($email, $companyId);

        return [
            'security_score' => $employeeReport->calculateSecurityScore(), // out of 100
            'risk_score' => $employeeReport->calculateOverallRiskScore(),
            'total_simulations' => $employeeReport->totalSimulations(),
            'compromised_simulations' => $employeeReport->compromised(),
            'payload_clicked' => $employeeReport->payloadClicked()
        ];
    }

    public function sendDomainVerifyOtp(Request $request)
    {
        try {
            $input = $request->all();
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json(['success' => false, 'message' => __('Invalid input detected')], 422);
                }
            }
            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);

            $verifyEmail = $request->verificationEmail;

            $domain = explode("@", $verifyEmail)[1];

            $notAllowedDomains = [
                'gmail.com',
                'yahoo.com',
                'outlook.com',
                'hotmail.com',
                'aol.com',
                'live.com',
                'yandex.com',
                'icloud.com',
                'protonmail.com'
            ];

            if (in_array($domain, $notAllowedDomains)) {
                return response()->json(['success' => false, 'message' => __('This email provider is not allowed')], 422);
            }

            $companyId = Auth::user()->company_id; // Assuming company_id is stored in the authenticated user
            $verifiedDomain = DomainVerified::where('domain', $domain)
                ->first();

            if (
                $verifiedDomain &&
                $verifiedDomain->verified == '1' &&
                $verifiedDomain->domain != 'simuphish.com'
            ) {
                return response()->json(['success' => false, 'message' => __('Domain already verified or by some other company')], 409);
            }

            if (
                $verifiedDomain &&
                $verifiedDomain->verified == '0'
            ) {
                $genCode = generateRandom(6);
                $verifiedDomain->temp_code = $genCode;
                $verifiedDomain->company_id = $companyId;
                $verifiedDomain->save();

                $this->domainVerificationMail($verifyEmail, $genCode);
            } else {
                $genCode = generateRandom(6);
                DomainVerified::create([
                    'domain' => $domain,
                    'temp_code' => $genCode,
                    'verified' => '0',
                    'company_id' => $companyId,
                ]);

                $this->domainVerificationMail($verifyEmail, $genCode);
            }

            log_action("Domain verification mail sent: {$verifyEmail}");
            return response()->json(['success' => true, 'message' => __('Verification email sent')], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    private function domainVerificationMail($email, $code)
    {
        $companyId = Auth::user()->company_id; // Assuming company_id is stored in the authenticated user
        $isWhitelabeled = new CheckWhitelabelService($companyId);
        if ($isWhitelabeled->isCompanyWhitelabeled()) {
            $isWhitelabeled->updateSmtpConfig();
        } else {
            $isWhitelabeled->clearSmtpConfig();
        }
        Mail::send('emails.domainVerification', ['code' => $code], function ($message) use ($email) {
            $message->to($email)->subject('Domain Verification');
        });
    }

    public function verifyOtp(Request $request)
    {
        //xss check start
        try {
            $input = $request->all();
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json(['success' => false, 'message' => __('Invalid input detected.')], 422);
                }
            }
            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);

            //xss check end

            $verificationCode = $request->input('emailOTP');
            $companyId = Auth::user()->company_id; // Assuming company_id is stored in the authenticated user

            $verifiedDomain = DomainVerified::where('temp_code', $verificationCode)
                ->where('company_id', $companyId)
                ->first();

            if ($verifiedDomain) {
                $verifiedDomain->verified = '1';
                $verifiedDomain->save();

                log_action("Domain verified successfully : {$verifiedDomain->domain}");

                return response()->json(['success' => true, 'message' => __('Domain verified successfully')], 200);
            } else {
                return response()->json(['success' => false, 'message' => __('Invalid Code')], 422);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function deleteDomain(Request $request)
    {
        try {
            $domain = $request->route('domain');
            if (!$domain) {
                return response()->json(['success' => false, 'message' => __('Domain is required')], 422);
            }

            $isDomainExists = DomainVerified::where('domain', $domain)->exists();
            if (!$isDomainExists) {
                return response()->json(['success' => false, 'message' => __('Domain not found')], 404);
            }

            DB::transaction(function () use ($domain) {
                // Delete users with the domain
                // Users::where('user_email', 'LIKE', '%' . $domain)->delete();

                // Delete the domain
                DomainVerified::where('domain', $domain)->delete();
            });

            log_action("Domain {$domain} deleted from platform");

            return response()->json(['success' => true, 'message' => __('Domain and associated users deleted successfully')], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function createNewGroup(Request $request)
    {
        try {
            $request->validate([
                'usrGroupName' => 'required'
            ]);
            $input = $request->all();
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json(['success' => false, 'message' => __('Invalid input detected')], 422);
                }
            }
            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);

            $grpName = $request->input('usrGroupName');
            $companyId = Auth::user()->company_id;

            // Check for existing groups with the same base name
            $existingGroups = UsersGroup::where('company_id', $companyId)
                ->where('group_name', 'LIKE', "{$grpName}%")
                ->pluck('group_name')
                ->toArray();

            if (in_array($grpName, $existingGroups)) {
                // Find the next available suffix
                $suffix = 1;
                do {
                    $newGrpName = "{$grpName}-({$suffix})";
                    $suffix++;
                } while (in_array($newGrpName, $existingGroups));
                $grpName = $newGrpName;
            }

            $grpId = generateRandom(6);

            UsersGroup::create([
                'group_id' => $grpId,
                'group_name' => $grpName,
                'users' => null,
                'company_id' => $companyId,
            ]);

            log_action("New employee group {$grpName} created");

            return response()->json(['success' => true, 'message' => __('New Employee Division created successfully')], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function updateGroup(Request $request)
    {
        try {
            $request->validate([
                'group_id' => 'required',
                'group_name' => 'required'
            ]);
            $input = $request->all();
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json(['success' => false, 'message' => __('Invalid input detected')], 422);
                }
            }
            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);

            $groupId = $request->input('group_id');
            $groupName = $request->input('group_name');
            $companyId = Auth::user()->company_id;

            $group = UsersGroup::where('group_id', $groupId)
                ->where('company_id', $companyId)
                ->first();

            if (!$group) {
                return response()->json(['success' => false, 'message' => __('Group Not found')], 404);
            }

            AiCallCampaign::where('users_group', $groupId)
                ->where('company_id', $companyId)
                ->update(['users_grp_name' => $groupName]);

            $group->group_name = $groupName;
            $group->save();

            log_action("Employee group {$groupName} updated");

            return response()->json(['success' => true, 'message' => __('Employee Group updated successfully')], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function viewUsers(Request $request)
    {
        try {
            $groupId = $request->route('groupId');
            if (!$groupId) {
                return response()->json(['success' => false, 'message' => __('Group ID is required')], 422);
            }
            $companyId = Auth::user()->company_id;
            $group = UsersGroup::where('group_id', $groupId)
                ->where('company_id', $companyId)
                ->first();

            if (!$group) {
                return response()->json(['success' => false, 'message' => __('Group Not found')], 404);
            }

            if ($group->users == null) {
                return response()->json(['success' => true, 'data' => [], 'message' => __('No Employees Found')]);
            }

            $userIdsArray = json_decode($group->users, true);

            $users = Users::whereIn('id', $userIdsArray)->get();

            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => __('Employees retrieved successfully')
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function viewUniqueEmails()
    {
        try {
            $companyId = Auth::user()->company_id;
            $users = Users::where('company_id', $companyId)
                ->whereIn('id', function ($query) {
                    $query->selectRaw('MAX(id)')
                        ->from('users')
                        ->groupBy('user_email');
                })
                ->get();
            if (!$users->isEmpty()) {
                return response()->json(['success' => true, 'data' => $users, 'message' => __('Employees retrieved successfully')], 200);
            } else {
                return response()->json(['success' => false, 'message' => __('No Employees Found')], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function addEmpFromAllEmp(Request $request)
    {
        try {
            foreach ($request->user_ids as $id) {
                $user = Users::find($id);
                if ($user) {
                    $employee = new EmployeeService(Auth::user()->company_id);
                    // Check if the user is already in the group
                    $emailExists = $employee->emailExistsInGroup($request->groupId, $user->user_email);
                    if ($emailExists) {
                        return response()->json(['success' => false, 'message' => __('This email(s) already exists in this group')], 409);
                    }

                    $addedInGroup = $employee->addEmployeeInGroup($request->groupId, $user->id);
                    if ($addedInGroup['status'] == 0) {
                        return response()->json(['success' => false, 'message' => $addedInGroup['msg']], 409);
                    }
                }
            }
            $UsersGroup = UsersGroup::where('group_id', $request->groupId)->first();
            log_action("Employee(s) added to the group : {$UsersGroup->group_name}");
            return response()->json(['success' => true, 'message' => __('Employee(s) successfully added to the group')], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function deleteUser(Request $request)
    {
        if (!$request->route('user_id')) {
            return response()->json(['success' => false, 'message' => __('User ID is required')], 422);
        }

        $user_id = base64_decode($request->route('user_id'));
        $group_id = $request->input('group_id'); // Get group_id from request

        if (!$group_id) {
            return response()->json(['success' => false, 'message' => __('Group ID is required')], 422);
        }

        $isUserExists = Users::where('id', $user_id)->where('company_id', Auth::user()->company_id)->first();

        if (!$isUserExists) {
            return response()->json(['success' => false, 'message' => __('User not found')], 404);
        }

        $user_name = $isUserExists->user_name;
        $employee = new EmployeeService(Auth::user()->company_id);

        try {
            $result = $employee->removeEmployeeFromGroup($group_id, $user_id);

            if ($result['status'] == 1) {
                log_action("Employee {$user_name} removed from group");
                return response()->json(['success' => true, 'message' => __('Employee removed from group successfully')], 200);
            } else {
                return response()->json(['success' => false, 'message' => $result['msg']], 422);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function deleteUserByEmail(Request $request)
    {
        try {
            $request->validate([
                'user_email' => 'required'
            ]);
            $user_email = $request->input('user_email');

            $users = Users::where('user_email', $user_email)
                ->where('company_id', Auth::user()->company_id)
                ->get();

            if ($users->isEmpty()) {
                return response()->json(['success' => false, 'message' => __('Employee not found')], 404);
            }
            $employee = new EmployeeService(Auth::user()->company_id);
            foreach ($users as $user) {
                try {
                    $employee->deleteEmployeeById($user->id);
                } catch (\Exception $e) {
                    return response()->json(['success' => false, 'message' => __('Failed to delete employee')]);
                }
            }

            //delete assigned training and policy
            $employee->deleteAssignedTrainingAndPolicy($user_email);

            $emailExists = DeletedEmployee::where('email', $user_email)->where('company_id', Auth::user()->company_id)->exists();
            if (!$emailExists) {
                DeletedEmployee::create([
                    'email' => $user_email,
                    'company_id' => Auth::user()->company_id,
                ]);
            }

            log_action("Employee deleted : {$user_email}");
            return response()->json(['success' => true, 'message' => __('Employee deleted successfully')], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function addUser(Request $request)
    {
        try {
            //xss check start

            $input = $request->all();
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json(['success' => false, 'message' => __('Invalid input detected.')], 422);
                }
            }
            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);

            //xss check end

            $validator = Validator::make($request->all(), [
                'groupId' => 'required',
                'usrName' => 'required|string|max:255',
                'usrEmail' => 'required|email|unique:users,user_email|max:255',
                'usrCompany' => 'nullable|string|max:255',
                'usrJobTitle' => 'nullable|string|max:255',
                'usrWhatsapp' => 'nullable|digits_between:11,15',
            ]);

            $request->merge([
                'usrWhatsapp' => preg_replace('/\D/', '', $request->usrWhatsapp)
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }

            $employee = new EmployeeService(Auth::user()->company_id);

            $addedEmployee = $employee->addEmployee(
                $request->usrName,
                $request->usrEmail,
                !empty($request->usrCompany) ? $request->usrCompany : null,
                !empty($request->usrJobTitle) ? $request->usrJobTitle : null,
                !empty($request->usrWhatsapp) ? $request->usrWhatsapp : null
            );
            if ($addedEmployee['status'] == 1) {
                $addedInGroup = $employee->addEmployeeInGroup($request->groupId, $addedEmployee['user_id']);

                if ($addedInGroup['status'] == 0) {
                    return response()->json(['success' => false, 'message' => $addedInGroup['msg']]);
                }
                log_action("Employee Added");
                return response()->json(['success' => true, 'message' => __('Employee Added Successfully')], 201);
            } else {
                return response()->json(['success' => false, 'message' => $addedEmployee['msg']]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function addPlanUser(Request $request)
    {
        try {
            // XSS check start
            $input = $request->all();
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json(['success' => false, 'message' => __('Invalid input detected.')], 422);
                }
            }
            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);
            // XSS check end

            $validator = Validator::make($request->all(), [
                'usrName' => 'required|string|max:255',
                'usrEmail' => 'required|email|max:255',
                'usrCompany' => 'nullable|string|max:255',
                'usrJobTitle' => 'nullable|string|max:255',
                'usrWhatsapp' => 'nullable|digits_between:11,15',
            ]);

            $request->merge([
                'usrWhatsapp' => preg_replace('/\D/', '', $request->usrWhatsapp)
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }

            //check if this email already exists in table
            $user = Users::where('user_email', $request->usrEmail)->where('company_id', Auth::user()->company_id)->exists();
            if ($user) {
                return response()->json(['success' => false, 'message' => __('This email already exists')], 422);
            }

            $employee = new EmployeeService(Auth::user()->company_id);

            $addedEmployee = $employee->addEmployee(
                $request->usrName,
                $request->usrEmail,
                !empty($request->usrCompany) ? $request->usrCompany : null,
                !empty($request->usrJobTitle) ? $request->usrJobTitle : null,
                !empty($request->usrWhatsapp) ? $request->usrWhatsapp : null
            );
            if ($addedEmployee['status'] == 1) {
                log_action("Employee Added : { $request->usrName}");
                return response()->json(['success' => true, 'message' => __('Employee Added Successfully')], 201);
            } else {
                return response()->json(['success' => false, 'message' => $addedEmployee['msg']], 422);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function importCsv(Request $request)
    {
        try {
            $grpId = $request->input('groupId');
            $file = $request->file('usrCsv');
            $companyId = Auth::user()->company_id;

            // Validate that the selected file is a CSV file
            $validator = Validator::make($request->all(), [
                'usrCsv' => 'required|file|mimes:csv,txt',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }

            // Path to store the uploaded file
            $path = $file->storeAs('uploads', $file->getClientOriginalName());

            // Read data from CSV file
            if (($handle = fopen(storage_path('app/' . $path), "r")) !== FALSE) {
                // Flag to track if it's the first row
                $firstRow = true;
                $employee = new EmployeeService(Auth::user()->company_id);

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    // Skip the first row
                    if ($firstRow) {
                        $firstRow = false;
                        continue;
                    }

                    $name = $data[0];
                    $email = $data[1];
                    $company = !empty($data[2]) ? preg_replace('/[^\w\s]/u', '', $data[2]) : null;
                    $job_title = !empty($data[3]) ? preg_replace('/[^\w\s]/u', '', $data[3]) : null;
                    $whatsapp = !empty($data[4]) ? preg_replace('/\D/', '', $data[4]) : null;

                    if (!$name || !$email) {
                        continue;
                    }

                    if (!empty($grpId)) {
                        // Check if the user is already in the group
                        $emailExists = $employee->emailExistsInGroup($grpId, $email);
                        if ($emailExists) {
                            continue;
                        }

                        // Check if email already exists in users table for this company
                        $existingUser = Users::where('user_email', $email)
                            ->where('company_id', $companyId)
                            ->first();

                        if ($existingUser) {
                            // User exists, just add to group
                            $employee->addEmployeeInGroup($grpId, $existingUser->id);
                        } else {
                            // User doesn't exist, create new user and add to group
                            $addedEmployee = $employee->addEmployee(
                                $name,
                                $email,
                                $company,
                                $job_title,
                                $whatsapp
                            );

                            if ($addedEmployee['status'] == 0) {
                                continue;
                            }
                            if ($addedEmployee['status'] == 1) {
                                $employee->addEmployeeInGroup($grpId, $addedEmployee['user_id']);
                            }
                        }
                    } else {
                        // check if user exists before adding
                        $userExists = Users::where('user_email', $email)
                            ->where('company_id', $companyId)
                            ->exists();

                        if ($userExists) {
                            continue;
                        }

                        $addedEmployee = $employee->addEmployee(
                            $name,
                            $email,
                            $company,
                            $job_title,
                            $whatsapp
                        );

                        if ($addedEmployee['status'] == 0) {
                            continue;
                        }
                    }
                }
                fclose($handle);

                log_action("Employees added by csv file");
                return response()->json(['success' => true, 'message' => __('CSV file imported successfully!')], 200);
            } else {
                log_action("Unable to open csv file");
                return response()->json(['success' => false, 'message' => __('Invalid file type. Please upload a CSV file.')], 422);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function deleteGroup(Request $request)
    {
        try {
            $grpId = $request->route('groupId');
            if (!$grpId) {
                return response()->json(['success' => false, 'message' => __('Group ID is required')], 422);
            }
            $employee = new EmployeeService(Auth::user()->company_id);
            $deleted = $employee->deleteGroup($grpId);
            if ($deleted['status'] == 1) {

                log_action("Employee Group deleted");
                return response()->json(['success' => true, 'message' => $deleted['msg']], 200);
            } else {
                return response()->json(['success' => false, 'message' => $deleted['msg']], 422);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function checkAdConfig()
    {
        try {
            $companyId = Auth::user()->company_id;

            $ldap_config = DB::table('ldap_ad_config')
                ->where('company_id', $companyId)
                ->first();

            if ($ldap_config) {
                return response()->json([
                    "success" => true,
                    "message" => __('config exists'),
                    "data" => $ldap_config
                ], 200);
            } else {
                return response()->json([
                    "success" => false,
                    "message" => __('config not exists')
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function updateLdapConfig(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;

            $request->validate([
                'ldap_host' => 'required|min:5|max:50|regex:/^[^<>]*$/',
                'ldap_dn' => 'required|min:5|max:50|regex:/^[^<>]*$/',
                'ldap_admin' => 'required|min:5|max:50|regex:/^[^<>]*$/',
                'ldap_pass' => 'required|min:5|max:50|regex:/^[^<>]*$/',
            ]);

            DB::table('ldap_ad_config')
                ->where('company_id', $companyId)
                ->update([
                    "ldap_host" => $request->ldap_host,
                    "ldap_dn" => $request->ldap_dn,
                    "admin_username" => $request->ldap_admin,
                    "admin_password" => $request->ldap_pass,
                    "updated_at" => now()
                ]);

            log_action("LDAP config updated");
            return response()->json([
                "success" => true,
                "message" => __('LDAP Config Updated')
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function addLdapConfig(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;

            $validator = Validator::make($request->all(), [
                'host' => 'required|min:5|max:50',
                'dn' => 'required|min:5|max:50',
                'user' => 'required|min:5|max:50',
                'pass' => 'required|min:5|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }

            DB::table('ldap_ad_config')
                ->insert([
                    "ldap_host" => $request->host,
                    "ldap_dn" => $request->dn,
                    "admin_username" => $request->user,
                    "admin_password" => $request->pass,
                    "updated_at" => now(),
                    "created_at" => now(),
                    "company_id" => $companyId
                ]);

            log_action("LDAP config saved");

            return response()->json([
                'success' => true,
                'message' => __("LDAP Config Saved")
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function syncLdap()
    {
        try {
            $companyId = Auth::user()->company_id;

            // Retrieve LDAP/AD configuration from the database
            $ldapConfig = DB::table('ldap_ad_config')->where('company_id', $companyId)->first();

            if (!$ldapConfig) {
                return response()->json([
                    'success' => false,
                    'message' => __('LDAP configuration not found in the database.'),
                ], 404);
            }

            // Extract LDAP configuration
            $ldapHost = $ldapConfig->ldap_host;
            $ldapDn = $ldapConfig->ldap_dn;
            $adminUsername = $ldapConfig->admin_username;
            $adminPassword = $ldapConfig->admin_password;

            // Initialize LDAP connection
            $ldapConn = ldap_connect($ldapHost);

            if (!$ldapConn) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to connect to LDAP server.'),
                ], 422);
            }

            ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);

            // Bind to the LDAP server with admin credentials
            $ldapBind = @ldap_bind($ldapConn, "CN=$adminUsername,$ldapDn", $adminPassword);

            if (!$ldapBind) {
                ldap_unbind($ldapConn);
                return response()->json([
                    'success' => false,
                    'message' => __('LDAP bind failed. Check admin credentials.'),
                ], 422);
            }

            // Search for all users in the AD
            $searchFilter = "(&(objectClass=user)(objectCategory=person)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))";
            $attributes = ["samaccountname", "givenName", "sn", "mail", "displayName"];
            $result = @ldap_search($ldapConn, $ldapDn, $searchFilter, $attributes);

            if (!$result) {
                ldap_unbind($ldapConn);
                return response()->json([
                    'success' => false,
                    'message' => __('LDAP search failed.'),
                ], 422);
            }

            // Get entries from the LDAP result
            $entries = ldap_get_entries($ldapConn, $result);

            if ($entries['count'] === 0) {
                ldap_unbind($ldapConn);
                return response()->json([
                    'success' => false,
                    'message' => __('No active users found in the LDAP directory.'),
                ], 422);
            }


            // Close the LDAP connection
            ldap_unbind($ldapConn);

            log_action("LDAP sync completed: " . count($entries) . " new users synced");

            return response()->json([
                'success' => true,
                'message' => __('LDAP sync completed successfully.'),
                'data' => $entries,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function updateEmployee(Request $request)
    {
        $email = $request->route('email');
        if (!$email) {
            return response()->json(['success' => false, 'message' => __('Email is required')], 422);
        }
        try {
            $users = Users::where('user_email', $email)->where('company_id', Auth::user()->company_id)->get();
            if ($users->isEmpty()) {
                return response()->json(['success' => false, 'message' => __('Employee not found')], 404);
            }

            $request->validate([
                'usrName' => 'required|string|max:255',
                'usrCompany' => 'nullable|string|max:255',
                'usrJobTitle' => 'nullable|string|max:255',
                'usrWhatsapp' => 'nullable|digits_between:11,15',
            ]);

            foreach ($users as $user) {
                $user->user_name = $request->input('usrName');
                $user->user_company = !empty($request->input('usrCompany')) ? $request->input('usrCompany') : null;
                $user->user_job_title = !empty($request->input('usrJobTitle')) ? $request->input('usrJobTitle') : null;
                $user->whatsapp = !empty($request->input('usrWhatsapp')) ? preg_replace('/\D/', '', $request->input('usrWhatsapp')) : null;

                // Save the updated user
                $user->save();
            }
            //update in ai call camp live
            $this->updateInCampaigns($email, $request->input('usrName'));

            log_action("Employee updated : {$user->user_name}");

            return response()->json(['success' => true, 'message' => __('Employee updated successfully')], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    private function updateInCampaigns($email, $name)
    {
        $companyId = Auth::user()->company_id;
        //ai call campaign live update
        AiCallCampLive::where('user_email', $email)
            ->where('company_id', $companyId)
            ->update([
                'user_name' => $name,
            ]);
        //assigned policies update
        AssignedPolicy::where('user_email', $email)
            ->where('company_id', $companyId)
            ->update([
                'user_name' => $name,
            ]);
        //email
        CampaignLive::where('user_email', $email)
            ->where('company_id', $companyId)
            ->update([
                'user_name' => $name,
            ]);
        //policy campaign live
        PolicyCampaignLive::where('user_email', $email)
            ->where('company_id', $companyId)
            ->update([
                'user_name' => $name,
            ]);
        //quishing campaign live
        QuishingLiveCamp::where('user_email', $email)
            ->where('company_id', $companyId)
            ->update([
                'user_name' => $name,
            ]);
        //training Assigned
        TrainingAssignedUser::where('user_email', $email)
            ->where('company_id', $companyId)
            ->update([
                'user_name' => $name,
            ]);

        //whatsapp campaign live
        WaLiveCampaign::where('user_email', $email)
            ->where('company_id', $companyId)
            ->update([
                'user_name' => $name,
            ]);

        //infographics campaign live
        InfoGraphicLiveCampaign::where('user_email', $email)
            ->where('company_id', $companyId)
            ->update([
                'user_name' => $name,
            ]);
    }
}
