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
use App\Models\UsersGroup;
use App\Models\WaLiveCampaign;
use App\Services\CustomisedReport\WidgetsService;
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

    public function updateReport(Request $request, $id)
    {
        try {
            $request->validate([
                'report_name' => 'required|string|max:255',
                'report_description' => 'required|string'
            ]);
            $id = base64_decode($id);

            CustomisedReporting::where('id', $id)
                ->where('company_id', Auth::user()->company_id)
                ->update([
                    'report_name' => $request->report_name,
                    'report_description' => $request->report_description
                ]);

            return response()->json([
                'success' => true,
                'message' => __('Report updated successfully')
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

    public function deleteReport($id)
    {
        try {
            $id = base64_decode($id);
            CustomisedReporting::where('id', $id)
                ->where('company_id', Auth::user()->company_id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => __('Report deleted successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
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

            $widget = new WidgetsService($companyId);

            return response()->json([
                'success' => true,
                'message' => __('Card data retrieved successfully'),
                'data' => $widget->card($type)
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
            $type = $request->query('type', 'interaction');
            $months = $request->query('months', 6);
            $companyId = Auth::user()->company_id;

            $widget = new WidgetsService($companyId);

            return response()->json([
                'success' => true,
                'message' => __('Line data retrieved successfully'),
                'data' => $widget->line($type, $months)
            ]);



            // $title = '';
            // $description = '';

            // // Get last 6 months including current month
            // $months = [];
            // for ($i = 5; $i >= 0; $i--) {
            //     $start = now()->subMonths($i)->startOfMonth()->format('Y-m-d');
            //     $end = now()->subMonths($i)->endOfMonth()->format('Y-m-d');
            //     $monthLabel = now()->subMonths($i)->format('M Y');
            //     $months[] = [
            //         'month' => $monthLabel,
            //         'start' => $start,
            //         'end' => $end
            //     ];
            // }

            // $phishing_events_overtime = [];

            // foreach ($months as $m) {
            //     $clickRate = 0;
            //     $reportRate = 0;
            //     $ignoreRate = 0;

            //     if ($type === 'email') {
            //         $clickRate = CampaignLive::where('company_id', $companyId)
            //             ->whereBetween('created_at', [$m['start'], $m['end']])
            //             ->where('emp_compromised', 1)
            //             ->count();

            //         $reportRate = CampaignLive::where('company_id', $companyId)
            //             ->whereBetween('created_at', [$m['start'], $m['end']])
            //             ->where('email_reported', 1)
            //             ->count();

            //         $ignoreRate = CampaignLive::where('company_id', $companyId)
            //             ->whereBetween('created_at', [$m['start'], $m['end']])
            //             ->where('payload_clicked', 0)
            //             ->count();

            //         $title = 'Email Phishing Campaigns';
            //         $description = __('Phishing events over time for email campaigns');
            //     } elseif ($type === 'quishing') {
            //         $clickRate = QuishingLiveCamp::where('company_id', $companyId)
            //             ->whereBetween('created_at', [$m['start'], $m['end']])
            //             ->where('compromised', '1')
            //             ->count();

            //         $reportRate = QuishingLiveCamp::where('company_id', $companyId)
            //             ->whereBetween('created_at', [$m['start'], $m['end']])
            //             ->where('email_reported', '1')
            //             ->count();

            //         $ignoreRate = QuishingLiveCamp::where('company_id', $companyId)
            //             ->whereBetween('created_at', [$m['start'], $m['end']])
            //             ->where('qr_scanned', '0')
            //             ->count();

            //         $title = 'Quishing Campaigns';
            //         $description = __('Phishing events over time for quishing campaigns');
            //     } elseif ($type === 'tprm') {
            //         // Fetch TPRM campaign data
            //         $clickRate = TprmCampaignLive::where('company_id', $companyId)
            //             ->whereBetween('created_at', [$m['start'], $m['end']])
            //             ->where('emp_compromised', 1)
            //             ->count();

            //         $reportRate = TprmCampaignLive::where('company_id', $companyId)
            //             ->whereBetween('created_at', [$m['start'], $m['end']])
            //             ->where('email_reported', 1)
            //             ->count();

            //         $ignoreRate = TprmCampaignLive::where('company_id', $companyId)
            //             ->whereBetween('created_at', [$m['start'], $m['end']])
            //             ->where('payload_clicked', 0)
            //             ->count();

            //         $title = 'TPRM Campaigns';
            //         $description = __('Phishing events over time for TPRM campaigns');
            //     }

            //     $phishing_events_overtime[] = [
            //         'month' => $m['month'],
            //         'clicked' => $clickRate,
            //         'targetClicked' => 5,
            //         'reported' => $reportRate,
            //         'targetReported' => 40,
            //         'ignored' => $ignoreRate,
            //         'targetIgnored' => 55
            //     ];
            // }


            // $series = [
            //     [
            //         'key' => 'clickRate',
            //         'label' => 'Click Rate',
            //         'color' => '#ef4444',
            //         'type' => $chartType
            //     ],
            //     [
            //         'key' => 'targetClickRate',
            //         'label' => 'Target Click Rate',
            //         'color' => '#f472b4',
            //         'type' => $chartType
            //     ],
            //     [
            //         'key' => 'reportRate',
            //         'label' => 'Report Rate',
            //         'color' => '#3b82f6',
            //         'type' => $chartType
            //     ],
            //     [
            //         'key' => 'targetReportRate',
            //         'label' => 'Target Report Rate',
            //         'color' => '#93c5fd',
            //         'type' => $chartType
            //     ],
            //     [
            //         'key' => 'ignoreRate',
            //         'label' => 'Ignore Rate',
            //         'color' => '#f97316',
            //         'type' => $chartType
            //     ],
            //     [
            //         'key' => 'targetIgnoreRate',
            //         'label' => 'Target Ignore Rate',
            //         'color' => '#fdba74',
            //         'type' => $chartType
            //     ],
            // ];

            // return response()->json([
            //     'success' => true,
            //     'message' => __('Data retrieved successfully'),
            //     'data' => [
            //         'title' => $title,
            //         'description' => $description,
            //         'data' => $phishing_events_overtime,
            //         'series' => $series,
            //         'reportFormData' => [
            //             'simulationsPeriod' => 6
            //         ]
            //     ]
            // ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function radialbarData(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $usersGroupId = $request->query('usersGroup'); // Get usersGroup from query parameter

            // Fetch only the specified usersGroup
            $division = UsersGroup::where('company_id', $companyId)
                ->where('group_id', $usersGroupId)
                ->whereNotNull('users')
                ->first();

            $data = [];
            if ($division) {
                $userIds = json_decode($division->users, true);
                $users = Users::where('company_id', $companyId)
                    ->whereIn('id', $userIds)
                    ->get();

                $totalUsers = $users->count();

                $riskScores = [];
                $exposureScores = [];
                $mitigationScores = [];

                foreach ($users as $user) {
                    $campaignsRan =
                        CampaignLive::where('company_id', $companyId)->where('user_email', $user->user_email)->count() +
                        QuishingLiveCamp::where('company_id', $companyId)->where('user_email', $user->user_email)->count() +
                        WaLiveCampaign::where('company_id', $companyId)->where('user_email', $user->user_email)->count() +
                        AiCallCampLive::where('company_id', $companyId)->where('employee_email', $user->user_email)->count();

                    $compromisedCount =
                        CampaignLive::where('company_id', $companyId)->where('user_email', $user->user_email)->where('emp_compromised', 1)->count() +
                        QuishingLiveCamp::where('company_id', $companyId)->where('user_email', $user->user_email)->where('compromised', '1')->count() +
                        WaLiveCampaign::where('company_id', $companyId)->where('user_email', $user->user_email)->where('compromised', 1)->count() +
                        AiCallCampLive::where('company_id', $companyId)->where('employee_email', $user->user_email)->where('compromised', 1)->count();

                    $ignoredCount =
                        CampaignLive::where('company_id', $companyId)->where('user_email', $user->user_email)->where('payload_clicked', 0)->count() +
                        QuishingLiveCamp::where('company_id', $companyId)->where('user_email', $user->user_email)->where('qr_scanned', '0')->count() +
                        WaLiveCampaign::where('company_id', $companyId)->where('user_email', $user->user_email)->where('payload_clicked', 0)->count() +
                        AiCallCampLive::where('company_id', $companyId)->where('employee_email', $user->user_email)->where('compromised', 0)->count();

                    // Security measurements:
                    // riskScore = compromisedRate (higher means more risk)
                    // exposureScore = (compromisedCount + ignoredCount) / campaignsRan * 100 (higher means more exposure)
                    // mitigationScore = 100 - exposureScore (higher means better mitigation)

                    $compromisedRate = $campaignsRan > 0 ? round(($compromisedCount / $campaignsRan) * 100, 2) : 0;
                    $exposureScore = $campaignsRan > 0 ? round((($compromisedCount + $ignoredCount) / $campaignsRan) * 100, 2) : 0;
                    $mitigationScore = 100 - $exposureScore;

                    $riskScores[] = $compromisedRate;
                    $exposureScores[] = $exposureScore;
                    $mitigationScores[] = $mitigationScore;
                }

                $data[] = [
                    'division' => $division->group_name ?: 'Unknown',
                    'risk_score' => $totalUsers > 0 ? round(array_sum($riskScores) / $totalUsers, 2) : 0,
                    'exposure_score' => $totalUsers > 0 ? round(array_sum($exposureScores) / $totalUsers, 2) : 0,
                    'mitigation_score' => $totalUsers > 0 ? round(array_sum($mitigationScores) / $totalUsers, 2) : 0,
                ];
            }

            $series = [
                [
                    'key' => 'risk_score',
                    'label' => 'Risk Score',
                    'color' => '#ef4444',
                    'maxValue' => 100
                ],
                [
                    'key' => 'exposure_score',
                    'label' => 'Exposure Score',
                    'color' => '#f59e0b',
                    'maxValue' => 100
                ],
                [
                    'key' => 'mitigation_score',
                    'label' => 'Mitigation Score',
                    'color' => '#10b981',
                    'maxValue' => 100
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'title' => 'Risk and Security Analysis',
                    'type' => 'radialbar',
                    'data' => $data,
                    'series' => $series,
                    'xAxisKey' => 'division',
                    'maxValue' => 100
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function bubbleData(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $usersGroups = UsersGroup::where('company_id', $companyId)
                ->whereNotNull('users')
                ->get();

            $data = [];
            foreach ($usersGroups as $group) {
                $userIds = json_decode($group->users, true);
                $users = Users::where('company_id', $companyId)
                    ->whereIn('id', $userIds)
                    ->get();

                $totalUsers = $users->count();

                $phishingAttacks = 0;
                $compromisedCount = 0;
                $securityScore = 0;

                foreach ($users as $user) {
                    $campaignsRan =
                        CampaignLive::where('company_id', $companyId)->where('user_email', $user->user_email)->count() +
                        QuishingLiveCamp::where('company_id', $companyId)->where('user_email', $user->user_email)->count() +
                        WaLiveCampaign::where('company_id', $companyId)->where('user_email', $user->user_email)->count() +
                        AiCallCampLive::where('company_id', $companyId)->where('employee_email', $user->user_email)->count();

                    $phishingAttacks += $campaignsRan;

                    $compromisedCount +=
                        CampaignLive::where('company_id', $companyId)->where('user_email', $user->user_email)->where('emp_compromised', 1)->count() +
                        QuishingLiveCamp::where('company_id', $companyId)->where('user_email', $user->user_email)->where('compromised', '1')->count() +
                        WaLiveCampaign::where('company_id', $companyId)->where('user_email', $user->user_email)->where('compromised', 1)->count() +
                        AiCallCampLive::where('company_id', $companyId)->where('employee_email', $user->user_email)->where('compromised', 1)->count();

                    $ignoredCount =
                        CampaignLive::where('company_id', $companyId)->where('user_email', $user->user_email)->where('payload_clicked', 0)->count() +
                        QuishingLiveCamp::where('company_id', $companyId)->where('user_email', $user->user_email)->where('qr_scanned', '0')->count() +
                        WaLiveCampaign::where('company_id', $companyId)->where('user_email', $user->user_email)->where('payload_clicked', 0)->count() +
                        AiCallCampLive::where('company_id', $companyId)->where('employee_email', $user->user_email)->where('compromised', 0)->count();

                    $securityScore += $campaignsRan > 0 ? 100 - round((($compromisedCount + $ignoredCount) / $campaignsRan) * 100, 2) : 100;
                }

                $data[] = [
                    'usersGroup' => $group->group_name ?: 'Unknown',
                    'phishingAttacks' => $phishingAttacks,
                    'compromised' => $compromisedCount,
                    'securityScore' => $totalUsers > 0 ? round($securityScore / $totalUsers, 2) : 100,
                    'employees' => $totalUsers
                ];
            }

            $series = [
                [
                    'key' => 'phishingAttacks',
                    'label' => 'Phishing Attacks',
                    'color' => '#3b82f6',
                    'sizeKey' => 'employees'
                ],
                [
                    'key' => 'compromised',
                    'label' => 'Compromised',
                    'color' => '#ef4444',
                    'sizeKey' => 'employees'
                ],
                [
                    'key' => 'securityScore',
                    'label' => 'Security Score',
                    'color' => '#10b981',
                    'sizeKey' => 'employees'
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'title' => 'Division Security Performance',
                    'subtitle' => __('Phishing & Security Analysis'),
                    'type' => 'bubble',
                    'data' => $data,
                    'series' => $series,
                    'xAxisKey' => 'usersGroup',
                    'yAxisKey' => 'phishingAttacks',
                    'sizeKey' => 'employees',
                    'description' => __('Bubble size represents number of employees in the division.'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function mixedData(Request $request)
    {
        //phishing event overtime
        return $this->lineData($request, 'mixed');
    }

    public function barData(Request $request)
    {
        //phishing event overtime
        return $this->lineData($request, 'bar');
    }
    public function areaData(Request $request)
    {
        //phishing event overtime
        return $this->lineData($request, 'area');
    }

    public function tableData(Request $request)
    {
        try {
            $type = $request->query('type');
            $companyId = Auth::user()->company_id;
            $title = '';
            $description = '';
            $columns = [];

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

                    $title = 'Employees';
                    $description = __('Employee table data with campaigns, compromised rates, and risk scores');
                }

                $columns = [
                    ['key' => 'name', 'label' => 'NAME', 'sortable' => true],
                    ['key' => 'email', 'label' => 'EMAIL', 'sortable' => true],
                    ['key' => 'campaigns_ran', 'label' => 'CAMPAIGNS RAN', 'sortable' => true],
                    ['key' => 'compromised_rate', 'label' => 'COMPROMISED RATE', 'sortable' => true],
                    ['key' => 'risk_score', 'label' => 'RISK SCORE', 'sortable' => true],
                    ['key' => 'ignore_rate', 'label' => 'IGNORE RATE', 'sortable' => true],
                    ['key' => 'trainings_assigned', 'label' => 'TRAININGS ASSIGNED', 'sortable' => true],
                ];


                return response()->json([
                    'success' => true,
                    'message' => __('Employee table data retrieved successfully'),
                    'data' => [
                        'title' => $title,
                        'description' => $description,
                        'columns' => $columns,
                        'data' => $data
                    ]
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
