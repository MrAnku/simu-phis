<?php

namespace App\Http\Controllers;

use App\Models\BreachedEmail;
use App\Models\Campaign;
use App\Models\CampaignReport;
use App\Models\Company;
use App\Models\EmailCampActivity;
use App\Models\TpmrVerifiedDomain;
use App\Models\Users;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
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

        $activeAIVishing = DB::table('ai_call_reqs')->where('company_id', auth()->user()->company_id)
            ->where('status', true)->first();

        $activeTprm = TpmrVerifiedDomain::where('company_id', auth()->user()->company_id)
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
        $currentDate = now();

        for ($i = 0; $i < 6; $i++) {
            $monthDate = $currentDate->copy()->subMonthsNoOverflow($i);
            $monthName = $monthDate->format('F');
            $year = $monthDate->format('Y');

            $noOfCampaigns = DB::table('all_campaigns')
                ->whereRaw(
                    'MONTH(STR_TO_DATE(launch_time, "%m/%d/%Y %h:%i %p")) = ? AND YEAR(STR_TO_DATE(launch_time, "%m/%d/%Y %h:%i %p")) = ? AND company_id = ?',
                    [$monthDate->format('m'), $year, Auth::user()->company_id]
                )
                ->count();

            $lastSixMonthsData[] = [
                'month' => __($monthName),
                'no_of_camps' => $noOfCampaigns
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Line chart data fetched successfully',
            'data' => array_reverse($lastSixMonthsData),
        ]);
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

    public function reqNewLimit(Request $request)
    {

        $request->validate([
            'new_limit' => 'required|integer|min:10|max:5000',
            'add_info' => 'nullable|string|max:1000'
        ]);

        DB::table('upgrade_req')->insert([
            'old_limit' => Auth::user()->employees,
            'new_limit' => $request->new_limit,
            'usage_percent' => $request->usage,
            'add_info' => empty($request->add_info) ? null : $request->add_info,
            'status' => 0,
            'company_id' => Auth::user()->company_id,
            'partner_id' => Auth::user()->partner_id,
            'created_at' => now(),
        ]);

        log_action("Employees limit upgrade request submitted");

        return redirect()->back()->with('success', __('Upgrade request submitted'));
    }

    public function appLangChange(Request $request)
    {
        if (in_array($request->language, ['en', 'ar', 'ru'])) {
            session(['locale' => $request->language]);
            Company::where('company_id', Auth::user()->company_id)->update(['lang' => $request->language]);
        }
        return redirect()->back();
    }
}
