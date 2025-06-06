<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Users;
use App\Models\Campaign;
use App\Models\UsersGroup;
use App\Models\CampaignLive;
use Illuminate\Http\Request;
use App\Models\BreachedEmail;
use App\Models\CampaignReport;
use App\Models\EmailCampActivity;
use App\Models\TpmrVerifiedDomain;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\Auth;

class ApiDashboardController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $data = $this->getTotalAssets();

        $recentSixCampaigns = Campaign::where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->take(6)
            ->get();

        $campaignsWithReport = CampaignReport::where('company_id', $companyId)->take(4)->get();

        $totalEmpCompromised = CampaignReport::where('company_id', $companyId)
            ->sum('emp_compromised');

        $package = $this->getPackage();
        $usageCounts = $this->osBrowserUsage();

        $breachedEmails = BreachedEmail::with('userData')->where('company_id', $companyId)->take(5)->get();

        // return $breachedEmails['userData'];

        $activeAIVishing = DB::table('ai_call_reqs')->where('company_id', Auth::user()->company_id)
            ->where('status', true)->first();

        $activeTprm = TpmrVerifiedDomain::where('company_id', Auth::user()->company_id)
            ->where('verified', true)->first();

        // return view('dashboard', compact('data', 'recentSixCampaigns', 'campaignsWithReport', 'totalEmpCompromised', 'package', 'breachedEmails', 'usageCounts', 'activeAIVishing', 'activeTprm'));
        return response()->json([
            'status' => 'success',
            'data' => [
                'totalAssets' => $data,
                'recentSixCampaigns' => $recentSixCampaigns,
                'campaignsWithReport' => $campaignsWithReport,
                'totalEmpCompromised' => $totalEmpCompromised,
                'package' => $package,
                'breachedEmails' => $breachedEmails,
                'usageCounts' => $usageCounts
            ]
        ], 200);
    }

    public function getPieData()
    {
        $pieData = DB::table('campaign_reports')
            ->selectRaw('SUM(emails_delivered) AS total_emails_delivered, 
                                     SUM(emails_viewed) AS total_emails_viewed, 
                                     SUM(training_assigned) AS total_training_assigned, 
                                     SUM(training_completed) AS total_training_completed')
            ->where('company_id', Auth::user()->company_id)
            ->first();

        return response()->json($pieData);
    }

    public function getLineChartData()
    {
        $lastSixMonthsData = [];
        $lastSixMonthsWhatsAppData = [];
        $lastSixMonthsQuishingData = [];
        $lastSixMonthsAiVishingData = [];
        $currentDate = now();

        // all_campaigns: uses launch_time
        for ($i = 0; $i < 6; $i++) {
            $monthDate = $currentDate->copy()->subMonthsNoOverflow($i);
            $monthName = $monthDate->format('F');
            $year = $monthDate->format('Y');

            $noOfCampaigns = DB::table('all_campaigns')
                ->whereRaw(
                    'MONTH(STR_TO_DATE(launch_time, "%m/%d/%Y %h:%i %p")) = ? AND YEAR(STR_TO_DATE(launch_time, "%m/%d/%Y %h:%i %p")) = ?',
                    [$monthDate->format('m'), $year]
                )
                ->where('company_id', Auth::user()->company_id)
                ->count();

            $lastSixMonthsData[] = [
                'month' => __($monthName),
                'no_of_camps' => $noOfCampaigns,
            ];
        }

        // wa_live_campaigns: uses created_at
        for ($i = 0; $i < 6; $i++) {
            $WhatsAppmonthDate = $currentDate->copy()->subMonthsNoOverflow($i);
            $WhatsAppmonthName = $WhatsAppmonthDate->format('F');
            $year = $WhatsAppmonthDate->format('Y');

            $noOfWhatsAppCampaigns = DB::table('wa_live_campaigns')
                ->whereMonth('created_at', $WhatsAppmonthDate->format('m'))
                ->whereYear('created_at', $year)
                ->where('company_id', Auth::user()->company_id)
                ->count();

            $lastSixMonthsWhatsAppData[] = [
                'month' => __($WhatsAppmonthName),
                'no_of_camps' => $noOfWhatsAppCampaigns,
            ];
        }

        // quishing_live_camps: use the correct date column, e.g. created_at
        for ($i = 0; $i < 6; $i++) {
            $monthQuishingDate = $currentDate->copy()->subMonthsNoOverflow($i);
            $monthNameQuishing = $monthQuishingDate->format('F');
            $year = $monthQuishingDate->format('Y');

            // Change 'created_at' to your actual date column if different
            $noOfQuishingCampaigns = DB::table('quishing_live_camps')
                ->whereMonth('created_at', $monthQuishingDate->format('m'))
                ->whereYear('created_at', $year)
                ->where('company_id', Auth::user()->company_id)
                ->count();

            $lastSixMonthsQuishingData[] = [
                'month' => __($monthNameQuishing),
                'no_of_camps' => $noOfQuishingCampaigns,
            ];
        }
        for ($i = 0; $i < 6; $i++) {
            $monthAiVishingDate = $currentDate->copy()->subMonthsNoOverflow($i);
            $monthNameAiVishing = $monthAiVishingDate->format('F');
            $year = $monthAiVishingDate->format('Y');

            // Change 'created_at' to your actual date column if different
            $noOfAiVishingCampaigns = DB::table('ai_call_camp_live')
                ->whereMonth('created_at', $monthAiVishingDate->format('m'))
                ->whereYear('created_at', $year)
                ->where('company_id', Auth::user()->company_id)
                ->count();

            $lastSixMonthsAiVishingData[] = [
                'month' => __($monthNameAiVishing),
                'no_of_camps' => $noOfAiVishingCampaigns,
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Line chart data fetched successfully',
            'data' => array_reverse($lastSixMonthsData),
            'WhatsAppData' => array_reverse($lastSixMonthsWhatsAppData),
            'QuishinhData' => array_reverse($lastSixMonthsQuishingData),
            'AiVishingData' => array_reverse($lastSixMonthsAiVishingData),
        ]);
    }

    public function whatsappReport()
    {
        try {
            // Get the authenticated user's company ID
            $companyId = Auth::user()->company_id;

            // Get current date
            $now = Carbon::now();
            $months = [];

            // Generate an array for the previous four months with formatted month and year
            for ($i = 0; $i < 4; $i++) {
                $date = $now->copy()->subMonths($i);
                $monthFormatted = $date->format('M y');
                $months[$monthFormatted] = [
                    'link_clicked' => 0,
                    'emp_compromised' => 0
                ];
            }

            // Fetch data from the database
            $data = DB::table('whatsapp_camp_users')
                ->select(
                    DB::raw('SUM(link_clicked) as link_clicked'),
                    DB::raw('SUM(emp_compromised) as emp_compromised'),
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month')
                )
                ->where('created_at', '>=', $now->copy()->subMonths(4)->startOfMonth())
                ->where('company_id', $companyId)
                ->groupBy('month')
                ->orderBy('month', 'asc')
                ->get();

            // Merge query results into the months array
            foreach ($data as $item) {
                $date = Carbon::createFromFormat('Y-m', $item->month);
                $monthFormatted = $date->format('M y');
                $months[$monthFormatted] = [
                    'link_clicked' => (int) $item->link_clicked,
                    'emp_compromised' => (int) $item->emp_compromised
                ];
            }

            // Prepare the final response structure
            $chartData = [
                'months' => array_keys($months),
                'link_clicked' => array_values(array_column($months, 'link_clicked')),
                'emp_compromised' => array_values(array_column($months, 'emp_compromised'))
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'WhatsApp report retrieved successfully',
                'data' => $chartData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve WhatsApp report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPayloadClickData()
    {
        $companyId = Auth::user()->company_id;

        // Fetch total records for the company
        $totalRecords = DB::table('campaign_live')
            ->where('company_id', $companyId)
            ->count();

        // Fetch records where payload is clicked
        $payloadClickRecords = DB::table('campaign_live')
            ->where('company_id', $companyId)
            ->where('payload_clicked', '>', 0)
            ->count();

        // Calculate the percentage
        $percentage = ($totalRecords > 0) ? ($payloadClickRecords / $totalRecords) * 100 : 0;

        // Prepare API response
        return response()->json([
            'percentage' => round($percentage, 2),
            'payload_clicks' => $payloadClickRecords
        ]);
    }

    public function getEmailReportedData()
    {
        $companyId = Auth::user()->company_id;

        // Fetch the total number of records for the company
        $totalRecords = DB::table('campaign_live')->where('company_id', $companyId)->count();

        // Fetch the number of records where email is reported
        $emailReportedRecords = DB::table('campaign_live')
            ->where('email_reported', '>', 0)
            ->where('company_id', $companyId)
            ->count();

        // Calculate the percentage
        $percentage = ($totalRecords > 0) ? ($emailReportedRecords / $totalRecords) * 100 : 0;

        // Return JSON response
        return response()->json([
            'percentage' => round($percentage, 2), // Round to 2 decimal places
            'email_reported' => $emailReportedRecords
        ]);
    }

    public function getPackage()
    {
        $companyId = Auth::user()->company_id;
        $employees = (int) Auth::user()->usedemployees;
        $allotedEmployees = (int) Auth::user()->employees;

        // Prevent division by zero
        $usedPercent = ($allotedEmployees > 0) ? ($employees / $allotedEmployees) * 100 : 0;

        // Prepare response data
        $package = [
            "alloted_emp" => $allotedEmployees,
            "total_emp" => $employees,
            "used_percent" => round($usedPercent, 2) // Round to 2 decimal places
        ];

        // Return JSON response
        return response()->json($package);
    }

    public function getLineChartData2()
    {
        try {
            // Get the current date and the date 10 days ago
            $endDate = Carbon::now();
            $startDate = $endDate->copy()->subDays(10);

            // Initialize an empty array to hold the results
            $data = [];

            // Loop through each day from start date to end date
            for ($date = $startDate; $date <= $endDate; $date->addDay()) {
                // Format the date
                $formattedDate = $date->format('Y-m-d');

                // Fetch data from all_campaigns by converting launch_time to date
                $allCampaignsCount = DB::table('all_campaigns')
                    ->whereDate(DB::raw("STR_TO_DATE(launch_time, '%m/%d/%Y %h:%i %p')"), $date->format('Y-m-d'))
                    ->where('company_id', Auth::user()->company_id)
                    ->count();

                // Fetch data from whatsapp_campaigns where created_at matches the current date
                $whatsappCampaignsCount = DB::table('whatsapp_campaigns')
                    ->whereDate('created_at', $date->format('Y-m-d'))
                    ->where('company_id', Auth::user()->company_id)
                    ->count();

                // Add the results to the data array
                $data[] = [
                    'date' => $date->format('d M'), // e.g., "12 Mar"
                    'all_campaigns' => $allCampaignsCount,
                    'whatsapp_campaigns' => $whatsappCampaignsCount,
                ];
            }

            // Return response as JSON
            return response()->json([
                'success' => true,
                'message' => 'Line chart data retrieved successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getTotalAssets()
    {
        $companyId = Auth::user()->company_id;

        // Get active campaigns
        $activeCampaignsCount = DB::table('all_campaigns')
            ->where('company_id', $companyId)
            ->where('status', 'running')
            ->count();

        // Get phishing emails
        $phishingEmailsCount = DB::table('phishing_emails')
            ->where(function ($query) use ($companyId) {
                $query->where('company_id', 'default')
                    ->orWhere('company_id', $companyId);
            })
            ->count();

        // Get phishing websites
        $phishingWebsitesCount = DB::table('phishing_websites')
            ->where(function ($query) use ($companyId) {
                $query->where('company_id', 'default')
                    ->orWhere('company_id', $companyId);
            })
            ->count();

        // Get training modules
        $trainingModulesCount = DB::table('training_modules')
            ->where(function ($query) use ($companyId) {
                $query->where('company_id', 'default')
                    ->orWhere('company_id', $companyId);
            })
            ->count();

        // Get sender profiles
        $senderprofileCount = DB::table('senderprofile')
            ->where(function ($query) use ($companyId) {
                $query->where('company_id', 'default')
                    ->orWhere('company_id', $companyId);
            })
            ->count();


        // Get WhatsApp Simulations
        $waSimuCount = DB::table('whatsapp_campaigns')
            ->where('company_id', $companyId)
            ->count();

        // Get Phishing Simulations
        $phishSimuCount = DB::table('all_campaigns')
            ->where('company_id', $companyId)
            ->count();


        // Get upgrade Request
        $upgrade_req = DB::table('upgrade_req')
            ->where('company_id', $companyId)
            ->where('status', 0)
            ->latest()
            ->first();

        // Prepare data to pass to view
        return [
            'active_campaigns' => $activeCampaignsCount,
            'phishing_emails' => $phishingEmailsCount,
            'phishing_websites' => $phishingWebsitesCount,
            'training_modules' => $trainingModulesCount,
            'senderprofile' => $senderprofileCount,
            'waSimuCount' => $waSimuCount,
            'phishSimuCount' => $phishSimuCount,
            'getLinechart2' => $this->getLineChartData2(),
            'upgrade_req' => $upgrade_req,
        ];
    }

    public function osBrowserUsage()
    {
        $companyId = Auth::user()->company_id;

        //os usage counts
        $macUsers = EmailCampActivity::whereNotNull('client_details')
            ->whereJsonContains('client_details->platform', 'OS X')
            ->where('company_id', $companyId)
            ->count();

        $windowsUsers = EmailCampActivity::whereNotNull('client_details')
            ->whereJsonContains('client_details->platform', 'Windows')
            ->where('company_id', $companyId)
            ->count();

        $androidUsers = EmailCampActivity::whereNotNull('client_details')
            ->whereJsonContains('client_details->platform', 'Android')
            ->where('company_id', $companyId)
            ->count();

        //browser usage counts
        $chromeUsers = EmailCampActivity::whereNotNull('client_details')
            ->whereJsonContains('client_details->browser', 'Chrome')
            ->where('company_id', $companyId)
            ->count();

        $firefoxUsers = EmailCampActivity::whereNotNull('client_details')
            ->whereJsonContains('client_details->browser', 'Firefox')
            ->where('company_id', $companyId)
            ->count();

        $edgeUsers = EmailCampActivity::whereNotNull('client_details')
            ->whereJsonContains('client_details->browser', 'Edge')
            ->where('company_id', $companyId)
            ->count();




        $usage = [
            'os' => [
                'windows' => $windowsUsers,
                'mac' => $macUsers,
                'android' => $androidUsers,
            ],
            'browser' => [
                'chrome' => $chromeUsers,
                'firefox' => $firefoxUsers,
                'edge' => $edgeUsers,
            ]
        ];

        return $usage;
    }

    // cards apis

    public function emailSimulationReport(Request $request)
    {
        $companyId = Auth::user()->company_id;

        if ($request->query('users_group') && $request->query('months')) {

            $group = $request->query('users_group');
            $months = $request->query('months');
            $months = (int)$months;

            $usersArray = UsersGroup::where('group_id', $group)
                ->where('company_id', $companyId)->first()->users;
            $usersArray = json_decode($usersArray, true);
            if (!$usersArray) {
                return response()->json([
                    'success' => false,
                    'message' => 'No users found for the specified group',
                ], 404);
            }

            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            $total = CampaignLive::where('company_id', $companyId)
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $clicked = CampaignLive::where('company_id', $companyId)
                ->where('payload_clicked', 1)
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            $reportRate = CampaignLive::where('company_id', $companyId)
                ->where('email_reported', 1)
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            $ignoreRate = CampaignLive::where('company_id', $companyId)
                ->where('payload_clicked', 0)
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $repeatClickers = CampaignLive::where('company_id', $companyId)
                ->where('payload_clicked', 1)
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('user_email')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('user_email')
                ->count();

            $remediationRate = $total > 0 ? round(($reportRate / $total) * 100, 2) : 0;
            return response()->json([
                'success' => true,
                'message' => 'Email simulation report retrieved successfully',
                'data' => [
                    "cards" => [
                        'total' => $total,
                        'clicked' => $clicked,
                        'reported' => $reportRate,
                        'ignored' => $ignoreRate,
                        'repeat_clickers' => $repeatClickers,
                        'remediation_rate_percent' => $remediationRate,
                        'pp_difference' => $this->ppDifference(),
                    ],
                    "phishing_events_overtime" => $this->eventsOverTime($usersArray, $months),
                    "grouped_simulation_statistics" => $this->groupedSimulationStatistics($group, $months),
                    "employee_simulation_events" => $this->empSimulationEvents($usersArray, $months),
                    "timing_statistics" => $this->timingStatistics($usersArray, $months),
                    "clicks_in_week_days" => $this->clicksInWeekDays($usersArray, $months),
                    "emotional_statistics" => $this->emotionalStatistics($group, $months),
                ]
            ], 200);
        } else {
            $total = CampaignLive::where('company_id', $companyId)->count();
            $clicked = CampaignLive::where('company_id', $companyId)
                ->where('payload_clicked', 1)
                ->count();
            $reportRate = CampaignLive::where('company_id', $companyId)
                ->where('email_reported', 1)
                ->count();
            $ignoreRate = CampaignLive::where('company_id', $companyId)
                ->where('payload_clicked', 0)
                ->count();

            $repeatClickers = CampaignLive::where('company_id', $companyId)
                ->where('payload_clicked', 1)
                ->groupBy('user_email')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('user_email')
                ->count();

            $remediationRate = $total > 0 ? round(($reportRate / $total) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'message' => 'Email simulation report retrieved successfully',
                'data' => [
                    "cards" => [
                        'total' => $total,
                        'clicked' => $clicked,
                        'reported' => $reportRate,
                        'ignored' => $ignoreRate,
                        'repeat_clickers' => $repeatClickers,
                        'remediation_rate_percent' => $remediationRate,
                        'pp_difference' => $this->ppDifference(),
                    ],
                    "phishing_events_overtime" => $this->eventsOverTime(),
                    "grouped_simulation_statistics" => $this->groupedSimulationStatistics(),
                    "employee_simulation_events" => $this->empSimulationEvents(),
                    "timing_statistics" => $this->timingStatistics(),
                    "clicks_in_week_days" => $this->clicksInWeekDays(),
                    "emotional_statistics" => $this->emotionalStatistics(),
                ]
            ], 200);
        }
    }

    private function ppDifference()
    {
        $companyId = Auth::user()->company_id;
        $now = Carbon::now();
        $currentStart = $now->copy()->subDays(7);  // Last 7 days
        $previousStart = $now->copy()->subDays(14); // Previous 7 days
        $previousEnd = $now->copy()->subDays(7);

        // Current period
        $totalCurrent = CampaignLive::where('company_id', $companyId)
            ->whereBetween('created_at', [$currentStart, $now])
            ->count();

        $clickedCurrent = CampaignLive::where('company_id', $companyId)
            ->where('payload_clicked', 1)
            ->whereBetween('created_at', [$currentStart, $now])
            ->count();

        $clickRateCurrent = $totalCurrent > 0 ? ($clickedCurrent / $totalCurrent) * 100 : 0;

        // Previous period
        $totalPrevious = CampaignLive::where('company_id', $companyId)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        $clickedPrevious = CampaignLive::where('company_id', $companyId)
            ->where('payload_clicked', 1)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        $clickRatePrevious = $totalPrevious > 0 ? ($clickedPrevious / $totalPrevious) * 100 : 0;

        // Percentage Point Difference
        $ppDifference = $clickRateCurrent - $clickRatePrevious;

        // Format result
        $ppFormatted = number_format($ppDifference, 2) . ' pp';
        return $ppFormatted;
    }
    private function eventsOverTime($usersArray = null, $months = null)
    {
        $companyId = Auth::user()->company_id;
        $now = Carbon::now();
        $chartData = [];

        if ($usersArray && $months) {


            for ($i = 0; $i < (int)$months; $i++) {
                $monthDate = $now->copy()->subMonthsNoOverflow($i);
                $monthStart = $monthDate->copy()->startOfMonth();
                $monthEnd = $monthDate->copy()->endOfMonth();

                $total = CampaignLive::where('company_id', $companyId)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('user_id', $usersArray)
                    ->count();

                $clicked = CampaignLive::where('company_id', $companyId)
                    ->where('payload_clicked', 1)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('user_id', $usersArray)
                    ->count();

                $reported = CampaignLive::where('company_id', $companyId)
                    ->where('email_reported', 1)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('user_id', $usersArray)
                    ->count();

                $ignored = CampaignLive::where('company_id', $companyId)
                    ->where('payload_clicked', 0)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('user_id', $usersArray)
                    ->count();

                $clickRate = $total > 0 ? round(($clicked / $total) * 100, 2) : 0;
                $reportRate = $total > 0 ? round(($reported / $total) * 100, 2) : 0;
                $ignoreRate = $total > 0 ? round(($ignored / $total) * 100, 2) : 0;

                // Example target rates, adjust as needed
                $targetClickRate = 5;
                $targetReportRate = 40;
                $targetIgnoreRate = 40;

                $chartData[] = [
                    'month' => $monthDate->format('F Y'),
                    'clickRate' => $clickRate,
                    'targetClickRate' => $targetClickRate,
                    'reportRate' => $reportRate,
                    'targetReportRate' => $targetReportRate,
                    'ignoreRate' => $ignoreRate,
                    'targetIgnoreRate' => $targetIgnoreRate,
                ];
            }
        } else {
            for ($i = 0; $i < 5; $i++) {
                $monthDate = $now->copy()->subMonthsNoOverflow($i);
                $monthStart = $monthDate->copy()->startOfMonth();
                $monthEnd = $monthDate->copy()->endOfMonth();

                $total = CampaignLive::where('company_id', $companyId)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count();

                $clicked = CampaignLive::where('company_id', $companyId)
                    ->where('payload_clicked', 1)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count();

                $reported = CampaignLive::where('company_id', $companyId)
                    ->where('email_reported', 1)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count();

                $ignored = CampaignLive::where('company_id', $companyId)
                    ->where('payload_clicked', 0)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count();

                $clickRate = $total > 0 ? round(($clicked / $total) * 100, 2) : 0;
                $reportRate = $total > 0 ? round(($reported / $total) * 100, 2) : 0;
                $ignoreRate = $total > 0 ? round(($ignored / $total) * 100, 2) : 0;

                // Example target rates, adjust as needed
                $targetClickRate = 5;
                $targetReportRate = 40;
                $targetIgnoreRate = 40;

                $chartData[] = [
                    'month' => $monthDate->format('F Y'),
                    'clickRate' => $clickRate,
                    'targetClickRate' => $targetClickRate,
                    'reportRate' => $reportRate,
                    'targetReportRate' => $targetReportRate,
                    'ignoreRate' => $ignoreRate,
                    'targetIgnoreRate' => $targetIgnoreRate,
                ];
            }
        }



        return array_reverse($chartData);
    }

    private function groupedSimulationStatistics($group = null, $months = null)
    {
        $companyId = Auth::user()->company_id;

        if ($group && $months) {
            // Fetch all campaigns for the company
            $groups = UsersGroup::with('emailCampaigns.campLive')
            ->where('company_id', $companyId)
            ->where('group_id', $group)
            ->get();
            if ($groups->isEmpty()) {
                return [];
            }
            return $groups->map(function ($group) {
                $total = $group->emailCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->count();
                });
                $totalSent = $group->emailCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('sent', 1)->count();
                });

                $clicked = $group->emailCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('payload_clicked', 1)->count();
                });

                $reported = $group->emailCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('email_reported', 1)->count();
                });

                $ignored = $group->emailCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('payload_clicked', 0)->count();
                });
                $compromised = $group->emailCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('emp_compromised', 1)->count();
                });

                return [
                    'group_name' => $group->group_name,
                    'total_sent' => $totalSent,
                    'total_clicked' => $clicked,
                    'click_rate' => $total > 0 ? round(($clicked / $total) * 100, 2) : 0,
                    'reported' => $reported,
                    'reported_rate' => $total > 0 ? round(($reported / $total) * 100, 2) : 0,
                    'ignored' => $ignored,
                    'ignored_rate' => $total > 0 ? round(($ignored / $total) * 100, 2) : 0,
                    'compromised' => $compromised,
                    'compromised_rate' => $total > 0 ? round(($compromised / $total) * 100, 2) : 0,
                ];
            });
        } else {
            // Fetch all campaigns for the company
            $groups = UsersGroup::with('emailCampaigns.campLive')->where('company_id', $companyId)->get();
            if ($groups->isEmpty()) {
                return [];
            }
            return $groups->map(function ($group) {
                $total = $group->emailCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->count();
                });
                $totalSent = $group->emailCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('sent', 1)->count();
                });

                $clicked = $group->emailCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('payload_clicked', 1)->count();
                });

                $reported = $group->emailCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('email_reported', 1)->count();
                });

                $ignored = $group->emailCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('payload_clicked', 0)->count();
                });
                $compromised = $group->emailCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('emp_compromised', 1)->count();
                });

                return [
                    'group_name' => $group->group_name,
                    'total_sent' => $totalSent,
                    'total_clicked' => $clicked,
                    'click_rate' => $total > 0 ? round(($clicked / $total) * 100, 2) : 0,
                    'reported' => $reported,
                    'reported_rate' => $total > 0 ? round(($reported / $total) * 100, 2) : 0,
                    'ignored' => $ignored,
                    'ignored_rate' => $total > 0 ? round(($ignored / $total) * 100, 2) : 0,
                    'compromised' => $compromised,
                    'compromised_rate' => $total > 0 ? round(($compromised / $total) * 100, 2) : 0,
                ];
            });
        }
    }

    private function timingStatistics($usersArray = null, $months = null)
    {
        $companyId = Auth::user()->company_id;

        if ($usersArray && $months) {

            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            $campaignLive = [
                'avg_time_to_click_in_hours' => round(
                    CampaignLive::where('company_id', $companyId)
                        ->whereIn('user_id', $usersArray)
                        ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_seconds')
                        ->value('avg_seconds') / 3600,
                    2
                ),
                'percent_within_10_min' => round(
                    (
                        CampaignLive::where('company_id', $companyId)
                        ->whereIn('user_id', $usersArray)
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) <= 10')
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) > 1')
                        ->count()
                        /
                        max(
                            CampaignLive::where('company_id', $companyId)
                                ->count(),
                            1
                        )
                    ) * 100,
                    2
                ),
                'clicked_within_1_hour' => round(
                    (
                        CampaignLive::where('company_id', $companyId)
                        ->whereIn('user_id', $usersArray)
                        ->where('payload_clicked', 1)
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) <= 60')
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) > 1')
                        ->count()
                        /
                        max(
                            CampaignLive::where('company_id', $companyId)

                                ->count(),
                            1
                        )
                    ) * 100,
                    2
                ),
                'clicked_within_1_day' => round(
                    (
                        CampaignLive::where('company_id', $companyId)
                        ->whereIn('user_id', $usersArray)
                        ->where('payload_clicked', 1)
                        ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) <= 24')
                        ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) > 1')
                        ->count()
                        /
                        max(
                            CampaignLive::where('company_id', $companyId)

                                ->count(),
                            1
                        )
                    ) * 100,
                    2
                ),
            ];
            return $campaignLive;
        } else {
            $campaignLive = [
                'avg_time_to_click_in_hours' => round(
                    CampaignLive::where('company_id', $companyId)
                        ->whereNotNull('created_at')
                        ->whereNotNull('updated_at')
                        ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_seconds')
                        ->value('avg_seconds') / 3600,
                    2
                ),
                'percent_within_10_min' => round(
                    (
                        CampaignLive::where('company_id', $companyId)
                        ->whereNotNull('created_at')
                        ->whereNotNull('updated_at')
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) <= 10')
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) > 1')
                        ->count()
                        /
                        max(
                            CampaignLive::where('company_id', $companyId)
                                ->whereNotNull('created_at')
                                ->whereNotNull('updated_at')
                                ->count(),
                            1
                        )
                    ) * 100,
                    2
                ),
                'clicked_within_1_hour' => round(
                    (
                        CampaignLive::where('company_id', $companyId)
                        ->whereNotNull('created_at')
                        ->whereNotNull('updated_at')
                        ->where('payload_clicked', 1)
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) <= 60')
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) > 1')
                        ->count()
                        /
                        max(
                            CampaignLive::where('company_id', $companyId)
                                ->whereNotNull('created_at')
                                ->whereNotNull('updated_at')
                                ->count(),
                            1
                        )
                    ) * 100,
                    2
                ),
                'clicked_within_1_day' => round(
                    (
                        CampaignLive::where('company_id', $companyId)
                        ->whereNotNull('created_at')
                        ->whereNotNull('updated_at')
                        ->where('payload_clicked', 1)
                        ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) <= 24')
                        ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) > 1')
                        ->count()
                        /
                        max(
                            CampaignLive::where('company_id', $companyId)
                                ->whereNotNull('created_at')
                                ->whereNotNull('updated_at')
                                ->count(),
                            1
                        )
                    ) * 100,
                    2
                ),
            ];
            return $campaignLive;
        }
    }

    private function clicksInWeekDays($usersArray = null, $months = null)
    {
        $companyId = Auth::user()->company_id;

        if ($usersArray && $months) {

            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();


            $weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $total = CampaignLive::where('company_id', $companyId)
                ->where('payload_clicked', 1)
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $clicksByDay = CampaignLive::where('company_id', $companyId)
                ->where('payload_clicked', 1)
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('DAYOFWEEK(created_at) as day, COUNT(*) as count')
                ->groupBy('day')
                ->pluck('count', 'day')
                ->toArray();

            $result = [];
            foreach ($weekDays as $i => $dayName) {
                // DAYOFWEEK returns 1 (Sunday) to 7 (Saturday)
                $dayIndex = $i + 1;
                $count = isset($clicksByDay[$dayIndex]) ? $clicksByDay[$dayIndex] : 0;
                $percent = $total > 0 ? round(($count / $total) * 100, 2) : 0;
                $result[] = [
                    'day' => $dayName,
                    'percentage' => $percent
                ];
            }

            return $result;
        } else {
            $weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $total = CampaignLive::where('company_id', $companyId)
                ->where('payload_clicked', 1)
                ->count();

            $clicksByDay = CampaignLive::where('company_id', $companyId)
                ->where('payload_clicked', 1)
                ->selectRaw('DAYOFWEEK(created_at) as day, COUNT(*) as count')
                ->groupBy('day')
                ->pluck('count', 'day')
                ->toArray();

            $result = [];
            foreach ($weekDays as $i => $dayName) {
                // DAYOFWEEK returns 1 (Sunday) to 7 (Saturday)
                $dayIndex = $i + 1;
                $count = isset($clicksByDay[$dayIndex]) ? $clicksByDay[$dayIndex] : 0;
                $percent = $total > 0 ? round(($count / $total) * 100, 2) : 0;
                $result[] = [
                    'day' => $dayName,
                    'percentage' => $percent
                ];
            }

            return $result;
        }
    }
    private function empSimulationEvents($usersArray = null, $months = null)
    {
        $companyId = Auth::user()->company_id;

        if ($usersArray && $months) {
            $uniqueUsers = Users::where('company_id', $companyId)
                ->whereIn('id', $usersArray)
                ->get();
            if ($uniqueUsers->isEmpty()) {
                return [];
            }

            $campaignStats = [];
            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            foreach ($uniqueUsers as $user) {
                $userEmail = $user->user_email;

                $total = CampaignLive::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $totalSent = CampaignLive::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('sent', 1)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $clicked = CampaignLive::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('payload_clicked', 1)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $reported = CampaignLive::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('email_reported', 1)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $ignored = CampaignLive::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('payload_clicked', 0)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $compromised = CampaignLive::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('emp_compromised', 1)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $campaignStats[] = [
                    'user_email' => $userEmail,
                    'total_sent' => $totalSent,
                    'total_clicked' => $clicked,
                    'click_rate' => $total > 0 ? round(($clicked / $total) * 100, 2) : 0,
                    'reported' => $reported,
                    'reported_rate' => $total > 0 ? round(($reported / $total) * 100, 2) : 0,
                    'ignored' => $ignored,
                    'ignored_rate' => $total > 0 ? round(($ignored / $total) * 100, 2) : 0,
                    'compromised' => $compromised,
                    'compromised_rate' => $total > 0 ? round(($compromised / $total) * 100, 2) : 0,
                ];
            }

            return $campaignStats;
        } else {
            $uniqueUsers = Users::where('company_id', $companyId)
                ->select('user_email')
                ->distinct()
                ->get();

            $campaignStats = [];

            foreach ($uniqueUsers as $user) {
                $userEmail = $user->user_email;

                $total = CampaignLive::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->count();

                $totalSent = CampaignLive::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('sent', 1)
                    ->count();

                $clicked = CampaignLive::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('payload_clicked', 1)
                    ->count();

                $reported = CampaignLive::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('email_reported', 1)
                    ->count();

                $ignored = CampaignLive::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('payload_clicked', 0)
                    ->count();

                $compromised = CampaignLive::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('emp_compromised', 1)
                    ->count();

                $campaignStats[] = [
                    'user_email' => $userEmail,
                    'total_sent' => $totalSent,
                    'total_clicked' => $clicked,
                    'click_rate' => $total > 0 ? round(($clicked / $total) * 100, 2) : 0,
                    'reported' => $reported,
                    'reported_rate' => $total > 0 ? round(($reported / $total) * 100, 2) : 0,
                    'ignored' => $ignored,
                    'ignored_rate' => $total > 0 ? round(($ignored / $total) * 100, 2) : 0,
                    'compromised' => $compromised,
                    'compromised_rate' => $total > 0 ? round(($compromised / $total) * 100, 2) : 0,
                ];
            }

            return $campaignStats;
        }
    }
    private function emotionalStatistics($group = null, $months = null)
    {

        $companyId = Auth::user()->company_id;

        if ($group && $months) {

            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            $campaigns = Campaign::with('campaignActivity')
                ->where('company_id', $companyId)
                ->where('users_group', $group)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
            if ($campaigns->isEmpty()) {
                return [];
            }
            $total = $campaigns->sum(function ($campaign) {
                return $campaign->campaignActivity->count();
            });
            $genuineEmail = 0;
            $showsInterestInPhishingEmail = 0;
            $looksSuspicious = 0;
            $totallySafe = 0;
            foreach ($campaigns as $campaign) {
                foreach ($campaign->campaignActivity as $activity) {
                    if ($activity->email_sent_at && $activity->email_viewed_at) {
                        $emailSentAt = Carbon::parse($activity->email_sent_at);
                        $emailViewedAt = Carbon::parse($activity->email_viewed_at);

                        $diffMinutes = $emailViewedAt->diffInMinutes($emailSentAt);
                        if ($diffMinutes <= 30 && $diffMinutes > 2) {
                            $genuineEmail++;
                        }
                    }
                }
                foreach ($campaign->campaignActivity as $activity) {
                    if ($activity->email_viewed_at && $activity->payload_clicked_at) {
                        $viewedAt = Carbon::parse($activity->email_viewed_at);
                        $payloadClickedAt = Carbon::parse($activity->payload_clicked_at);

                        $diffMinutes = $payloadClickedAt->diffInSeconds($viewedAt);
                        if ($diffMinutes <= 10 && $diffMinutes > 2) {
                            $showsInterestInPhishingEmail++;
                        }
                        if ($diffMinutes <= 120 && $diffMinutes > 10) {
                            $looksSuspicious++;
                        }
                    }
                }

                foreach ($campaign->campaignActivity as $activity) {
                    if ($activity->payload_clicked_at == null && $activity->compromised_at == null) {
                        $totallySafe++;
                    }
                }
            }

            // Calculate percentages
            $genuineEmailPercent = $total > 0 ? round(($genuineEmail / $total) * 100, 2) : 0;
            $showsInterestInPhishingEmailPercent = $total > 0 ? round(($showsInterestInPhishingEmail / $total) * 100, 2) : 0;
            $looksSuspiciousPercent = $total > 0 ? round(($looksSuspicious / $total) * 100, 2) : 0;
            $totallySafePercent = $total > 0 ? round(($totallySafe / $total) * 100, 2) : 0;
            return [
                'total' => $total,
                'genuineEmail' => $genuineEmail,
                'genuineEmailPercent' => $genuineEmailPercent,
                'showsInterestInPhishingEmail' => $showsInterestInPhishingEmail,
                'showsInterestInPhishingEmailPercent' => $showsInterestInPhishingEmailPercent,
                'looksSuspicious' => $looksSuspicious,
                'looksSuspiciousPercent' => $looksSuspiciousPercent,
                'totallySafe' => $totallySafe,
                'totallySafePercent' => $totallySafePercent
            ];
        } else {
            $campaigns = Campaign::with('campaignActivity')
                ->where('company_id', $companyId)
                ->get();
            if ($campaigns->isEmpty()) {
                return [];
            }
            $total = $campaigns->sum(function ($campaign) {
                return $campaign->campaignActivity->count();
            });
            $genuineEmail = 0;
            $showsInterestInPhishingEmail = 0;
            $looksSuspicious = 0;
            $totallySafe = 0;
            foreach ($campaigns as $campaign) {
                foreach ($campaign->campaignActivity as $activity) {
                    if ($activity->email_sent_at && $activity->email_viewed_at) {
                        $emailSentAt = Carbon::parse($activity->email_sent_at);
                        $emailViewedAt = Carbon::parse($activity->email_viewed_at);

                        $diffMinutes = $emailViewedAt->diffInMinutes($emailSentAt);
                        if ($diffMinutes <= 30 && $diffMinutes > 2) {
                            $genuineEmail++;
                        }
                    }
                }
                foreach ($campaign->campaignActivity as $activity) {
                    if ($activity->email_viewed_at && $activity->payload_clicked_at) {
                        $viewedAt = Carbon::parse($activity->email_viewed_at);
                        $payloadClickedAt = Carbon::parse($activity->payload_clicked_at);

                        $diffMinutes = $payloadClickedAt->diffInSeconds($viewedAt);
                        if ($diffMinutes <= 10 && $diffMinutes > 2) {
                            $showsInterestInPhishingEmail++;
                        }
                        if ($diffMinutes <= 120 && $diffMinutes > 10) {
                            $looksSuspicious++;
                        }
                    }
                }

                foreach ($campaign->campaignActivity as $activity) {
                    if ($activity->payload_clicked_at == null && $activity->compromised_at == null) {
                        $totallySafe++;
                    }
                }
            }

            // Calculate percentages
            $genuineEmailPercent = $total > 0 ? round(($genuineEmail / $total) * 100, 2) : 0;
            $showsInterestInPhishingEmailPercent = $total > 0 ? round(($showsInterestInPhishingEmail / $total) * 100, 2) : 0;
            $looksSuspiciousPercent = $total > 0 ? round(($looksSuspicious / $total) * 100, 2) : 0;
            $totallySafePercent = $total > 0 ? round(($totallySafe / $total) * 100, 2) : 0;
            return [
                'total' => $total,
                'genuineEmail' => $genuineEmail,
                'genuineEmailPercent' => $genuineEmailPercent,
                'showsInterestInPhishingEmail' => $showsInterestInPhishingEmail,
                'showsInterestInPhishingEmailPercent' => $showsInterestInPhishingEmailPercent,
                'looksSuspicious' => $looksSuspicious,
                'looksSuspiciousPercent' => $looksSuspiciousPercent,
                'totallySafe' => $totallySafe,
                'totallySafePercent' => $totallySafePercent
            ];
        }
    }
}
