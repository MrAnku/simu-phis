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
                'message' => __('Data retrieved successfully'),
                'data' => $widget->line($type, $months)
            ]);


        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function tableData(Request $request)
    {
          try {
            $type = $request->query('type', 'employees');
            $months = $request->query('months', 2);
            $companyId = Auth::user()->company_id;

            $widget = new WidgetsService($companyId);

            return response()->json([
                'success' => true,
                'message' => __('Table data retrieved successfully'),
                'data' => $widget->table($type, $months)
            ]);


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
        try {
            $type = $request->query('type', 'interaction');
            $months = $request->query('months', 6);
            $companyId = Auth::user()->company_id;

            $widget = new WidgetsService($companyId);

            return response()->json([
                'success' => true,
                'message' => __('Data retrieved successfully'),
                'data' => $widget->line($type, $months)
            ]);


        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }
    public function areaData(Request $request)
    {
        //phishing event overtime
        return $this->lineData($request, 'area');
    }

    
}
