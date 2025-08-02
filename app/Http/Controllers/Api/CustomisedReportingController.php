<?php

namespace App\Http\Controllers\Api;

use App\Models\Users;
use Illuminate\Http\Request;
use App\Models\CustomisedReporting;
use App\Http\Controllers\Controller;
use App\Models\AiCallCampLive;
use App\Models\AssignedPolicy;
use App\Models\CampaignLive;
use App\Models\QuishingLiveCamp;
use App\Models\TprmCampaignLive;
use App\Models\TrainingAssignedUser;
use App\Models\WaLiveCampaign;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Claims\Custom;
use Illuminate\Validation\ValidationException;

class CustomisedReportingController extends Controller
{
    public function index(Request $request)
    {
        // Fetch customised reports based on the company ID
        $companyId = Auth::user()->company_id;
        $reports = CustomisedReporting::where('company_id', $companyId)->get();

        return response()->json([
            'success' => true,
            'message' => __('Customised reports fetched successfully'),
            'data' => $reports,
        ]);
    }

    public function reportingById($id)
    {
        $id = base64_decode($id);
        $companyId = Auth::user()->company_id;
        $report = CustomisedReporting::where('id', $id)
            ->where('company_id', $companyId)
            ->first();
        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => __('Report not found')
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => __('Report fetched successfully'),
            'data' => $report
        ]);
    }

    public function addReport(Request $request)
    {
        try {
            $request->validate([
                'report_name' => 'required|string|max:255',
                'report_description' => 'required|string'
            ]);

            CustomisedReporting::create([
                'report_name' => $request->report_name,
                'report_description' => $request->report_description,
                'company_id' => Auth::user()->company_id,
            ]);
            return response()->json([
                'success' => true,
                'message' => __('Widget added successfully')
            ]);
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

    public function addWidgets(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|string',
                'widgets' => 'required|array'
            ]);

            $id = base64_decode($request->id);

            CustomisedReporting::where('id', $id)
                ->where('company_id', Auth::user()->company_id)
                ->update(['widgets' => json_encode($request->widgets)]);
            return response()->json([
                'success' => true,
                'message' => __('Widget added successfully')
            ]);
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

    public function cardData(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $type = $request->query('type');
            $cardData = [];
            if ($type == 'employees') {
                $cardData['title'] = __('Employees');
                $cardData['period'] = __('Last 30 Days');
                $cardData['value'] = Users::where('company_id', $companyId)
                    ->distinct('user_email')
                    ->count();
                $totalEmployees = Users::where('company_id', $companyId)
                    ->distinct('user_email')
                    ->count();

                // Example: fraction = percentage of employees added in last 30 days
                $employeesLast30Days = Users::where('company_id', $companyId)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->distinct('user_email')
                    ->count();

                $cardData['fraction'] = $totalEmployees > 0 ? round(($employeesLast30Days / $totalEmployees) * 100, 2) : 0;
                $cardData['pp'] = $employeesLast30Days;
                $cardData['icon'] = 'LucideUsers';
                $cardData['iconColor'] = 'text-blue-500';
            }
            if ($type == 'assigned_trainings') {
                $cardData['title'] = __('Assigned Trainings');
                $cardData['period'] = __('Total');
                $cardData['value'] = TrainingAssignedUser::where('company_id', $companyId)->count();
                $cardData['icon'] = 'LucideBookOpen';
                $cardData['iconColor'] = 'text-green-500';
            }
            if ($type == 'assigned_policies') {
                $cardData['title'] = __('Assigned Policies');
                $cardData['period'] = __('Total');
                $cardData['value'] = AssignedPolicy::where('company_id', $companyId)->count();
                $cardData['icon'] = 'LucideFileText';
                $cardData['iconColor'] = 'text-yellow-500';
            }
            if ($type == 'compromised_employees') {
                $compromisedCount = CampaignLive::where('company_id', $companyId)
                    ->where('emp_compromised', 1)
                    ->count();
                $compromisedCount += QuishingLiveCamp::where('company_id', $companyId)
                    ->where('compromised', '1')
                    ->count();
                $compromisedCount += WaLiveCampaign::where('company_id', $companyId)
                    ->where('compromised', 1)
                    ->count();
                $compromisedCount += AiCallCampLive::where('company_id', $companyId)
                    ->where('compromised', 1)
                    ->count();

                $cardData['title'] = __('Compromised Employees');
                $cardData['period'] = __('Total');
                $cardData['value'] = $compromisedCount;
                $cardData['icon'] = 'LucideAlertTriangle';
                $cardData['iconColor'] = 'text-red-500';
            }

            return response()->json([
                'success' => true,
                'message' => __('Card data retrieved successfully'),
                'data' => $cardData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function lineData(Request $request)
    {
        try {
            $type = $request->query('type');
            $companyId = Auth::user()->company_id;

            // Get last 6 months including current month
            $months = [];
            for ($i = 5; $i >= 0; $i--) {
                $start = now()->subMonths($i)->startOfMonth()->format('Y-m-d');
                $end = now()->subMonths($i)->endOfMonth()->format('Y-m-d');
                $monthLabel = now()->subMonths($i)->format('M Y');
                $months[] = [
                    'month' => $monthLabel,
                    'start' => $start,
                    'end' => $end
                ];
            }

            $phishing_events_overtime = [];

            foreach ($months as $m) {
                $clickRate = 0;
                $reportRate = 0;
                $ignoreRate = 0;

                if ($type === 'email') {
                    $clickRate = CampaignLive::where('company_id', $companyId)
                        ->whereBetween('created_at', [$m['start'], $m['end']])
                        ->where('emp_compromised', 1)
                        ->count();

                    $reportRate = CampaignLive::where('company_id', $companyId)
                        ->whereBetween('created_at', [$m['start'], $m['end']])
                        ->where('email_reported', 1)
                        ->count();

                    $ignoreRate = CampaignLive::where('company_id', $companyId)
                        ->whereBetween('created_at', [$m['start'], $m['end']])
                        ->where('payload_clicked', 0)
                        ->count();
                } elseif ($type === 'quishing') {
                    $clickRate = QuishingLiveCamp::where('company_id', $companyId)
                        ->whereBetween('created_at', [$m['start'], $m['end']])
                        ->where('compromised', '1')
                        ->count();

                    $reportRate = QuishingLiveCamp::where('company_id', $companyId)
                        ->whereBetween('created_at', [$m['start'], $m['end']])
                        ->where('email_reported', '1')
                        ->count();

                    $ignoreRate = QuishingLiveCamp::where('company_id', $companyId)
                        ->whereBetween('created_at', [$m['start'], $m['end']])
                        ->where('qr_scanned', '0')
                        ->count();
                } elseif ($type === 'tprm') {
                    // Fetch TPRM campaign data
                    $clickRate = TprmCampaignLive::where('company_id', $companyId)
                        ->whereBetween('created_at', [$m['start'], $m['end']])
                        ->where('emp_compromised', 1)
                        ->count();

                    $reportRate = TprmCampaignLive::where('company_id', $companyId)
                        ->whereBetween('created_at', [$m['start'], $m['end']])
                        ->where('email_reported', 1)
                        ->count();

                    $ignoreRate = TprmCampaignLive::where('company_id', $companyId)
                        ->whereBetween('created_at', [$m['start'], $m['end']])
                        ->where('payload_clicked', 0)
                        ->count();
                }

                $phishing_events_overtime[] = [
                    'month' => $m['month'],
                    'clicked' => $clickRate,
                    'targetClicked' => 5,
                    'reported' => $reportRate,
                    'targetReported' => 40,
                    'ignored' => $ignoreRate,
                    'targetIgnored' => 55
                ];
            }

            return response()->json([
                'success' => true,
                'message' => __('Data retrieved successfully'),
                'data' => [
                    'phishing_events_overtime' => $phishing_events_overtime,
                    'reportFormData' => [
                        'simulationsPeriod' => 6
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function barData(Request $request)
    {
        //phishing event overtime
        return $this->lineData($request);
    }
    public function areaData(Request $request)
    {
        //phishing event overtime
        return $this->lineData($request);
    }

    public function tableData(Request $request)
    {
        try {
            $type = $request->query('type');
            $companyId = Auth::user()->company_id;

            if ($type === 'employees') {
                $users = Users::where('company_id', $companyId)->distinct('user_email')->get();
                $data = [];

                foreach ($users as $user) {
                    // Campaigns ran (sum from all sources)
                    $campaignsRan =
                        CampaignLive::where('company_id', $companyId)->where('user_email', $user->user_email)->count() +
                        QuishingLiveCamp::where('company_id', $companyId)->where('user_email', $user->user_email)->count() +
                        WaLiveCampaign::where('company_id', $companyId)->where('user_email', $user->user_email)->count() +
                        AiCallCampLive::where('company_id', $companyId)->where('employee_email', $user->user_email)->count();

                    // Compromised count (sum from all sources)
                    $compromisedCount =
                        CampaignLive::where('company_id', $companyId)->where('user_email', $user->user_email)->where('emp_compromised', 1)->count() +
                        QuishingLiveCamp::where('company_id', $companyId)->where('user_email', $user->user_email)->where('compromised', '1')->count() +
                        WaLiveCampaign::where('company_id', $companyId)->where('user_email', $user->user_email)->where('compromised', 1)->count() +
                        AiCallCampLive::where('company_id', $companyId)->where('employee_email', $user->user_email)->where('compromised', 1)->count();

                    $compromisedRate = $campaignsRan > 0 ? round(($compromisedCount / $campaignsRan) * 100, 2) : 0;

                    // Ignore count (sum from all sources)
                    $ignoredCount =
                        CampaignLive::where('company_id', $companyId)->where('user_email', $user->user_email)->where('payload_clicked', 0)->count() +
                        QuishingLiveCamp::where('company_id', $companyId)->where('user_email', $user->user_email)->where('qr_scanned', '0')->count() +
                        WaLiveCampaign::where('company_id', $companyId)->where('user_email', $user->user_email)->where('payload_clicked', 0)->count() +
                        AiCallCampLive::where('company_id', $companyId)->where('employee_email', $user->user_email)->where('compromised', 0)->count();

                    $ignoreRate = $campaignsRan > 0 ? round(($ignoredCount / $campaignsRan) * 100, 2) : 0;

                    // Risk score (example: compromised rate + ignore rate, capped at 100)
                    $riskScore = min($compromisedRate + $ignoreRate, 100);

                    // Trainings assigned
                    $trainingsAssigned = TrainingAssignedUser::where('company_id', $companyId)
                        ->where('user_email', $user->user_email)
                        ->count();

                    $data[] = [
                        'name' => $user->user_name,
                        'email' => $user->user_email,
                        'campaigns_ran' => $campaignsRan,
                        'compromised_rate' => $compromisedRate,
                        'risk_score' => $riskScore,
                        'ignore_rate' => $ignoreRate,
                        'trainings_assigned' => $trainingsAssigned,
                    ];
                }

                return response()->json([
                    'success' => true,
                    'message' => __('Employee table data retrieved successfully'),
                    'data' => $data
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }
}
