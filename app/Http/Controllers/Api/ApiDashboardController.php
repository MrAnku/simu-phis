<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Users;
use App\Models\Company;
use App\Models\Campaign;
use App\Models\UsersGroup;
use App\Models\WaCampaign;
use App\Models\CampaignLive;
use App\Models\QuishingCamp;
use App\Models\TprmCampaign;
use Illuminate\Http\Request;
use App\Models\BreachedEmail;
use App\Models\PhishingEmail;
use App\Models\AiCallCampaign;
use App\Models\AiCallCampLive;
use App\Models\CompanyLicense;
use App\Models\WaLiveCampaign;
use App\Models\QuishingLiveCamp;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\Auth;

class ApiDashboardController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $recentSixCampaigns = Campaign::where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->take(6)
            ->get();


        $breachedEmails = BreachedEmail::with('userData')->where('company_id', $companyId)->take(5)->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'campaigns' => $this->campaignCounts(),
                'totalAssets' => $this->getTotalAssets(),
                'recentSixCampaigns' => $recentSixCampaigns,
                'email_and_trainings' => $this->getEmailAndTrainingCounts(),
                'simulation_activity' => $this->getSimulationActivity(),
                'package' => $this->getPackage(),
                'risk_score_distribution' => $this->getRiskScoreDistribution(),
                'division_score' => $this->getDivisionScore(),
                'breachedEmails' => $breachedEmails,
                'usageCounts' => $this->osBrowserUsage()
            ]
        ], 200);
    }

    public function acceptEula(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $action = $request->input('action');

        if($action !== 'accepted') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid action'
            ], 400);
        }

        // Update the company's EULA acceptance status
        Company::where('company_id', $companyId)->update([
            'eula_accepted' => 1,
            'eula_accepted_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'EULA accepted successfully'
        ], 200);
    }

    public function sortByTimeCampaignCard(Request $request)
    {
        $months = $request->query('months');
        $campaign = $request->query('campaign');
        if ($campaign == 'email') {
            $counts = Campaign::where('company_id', Auth::user()->company_id)
                ->whereBetween('created_at', [now()->subMonths($months), now()])
                ->count();
        } else if ($campaign == 'whatsapp') {
            $counts = WaCampaign::where('company_id', Auth::user()->company_id)
                ->whereBetween('created_at', [now()->subMonths($months), now()])
                ->count();
        } else if ($campaign == 'ai_vishing') {
            $counts = AiCallCampaign::where('company_id', Auth::user()->company_id)
                ->whereBetween('created_at', [now()->subMonths($months), now()])
                ->count();
        } else if ($campaign == 'quishing') {
            $counts = QuishingCamp::where('company_id', Auth::user()->company_id)
                ->whereBetween('created_at', [now()->subMonths($months), now()])
                ->count();
        } else if ($campaign == 'tprm') {
            $counts = TprmCampaign::where('company_id', Auth::user()->company_id)
                ->whereBetween('created_at', [now()->subMonths($months), now()])
                ->count();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Campaign counts fetched successfully',
            'data' => [
                'counts' => $counts,
                'months' => $months,
                'campaign' => $campaign
            ]
        ], 200);
    }

    private function getDivisionScore()
    {
        $companyId = Auth::user()->company_id;

        $groupScores = [];
        $groups = UsersGroup::where('company_id', $companyId)->get();

        foreach ($groups as $group) {
            $usersArray = json_decode($group->users, true);
            if (!$usersArray || empty($usersArray)) {
                continue;
            }


            // Calculate risk score for this group
            $totalSimulations = CampaignLive::where('company_id', $companyId)
                ->whereIn('user_id', $usersArray)
                ->count();
            $compromisedSimulations = CampaignLive::where('company_id', $companyId)
                ->whereIn('user_id', $usersArray)
                ->where('emp_compromised', 1)
                ->count();

            $totalQuishing = QuishingLiveCamp::where('company_id', $companyId)
                ->whereIn('user_id', $usersArray)
                ->count();
            $compromisedQuishing = QuishingLiveCamp::where('company_id', $companyId)
                ->whereIn('user_id', $usersArray)
                ->where('compromised', '1')
                ->count();

            $totalAiVishing = AiCallCampLive::where('company_id', $companyId)
                ->whereIn('user_id', $usersArray)
                ->count();
            $compromisedAiVishing = AiCallCampLive::where('company_id', $companyId)
                ->whereIn('user_id', $usersArray)
                ->where('training_assigned', 1)
                ->count();



            $totalWhatsapp = \App\Models\WaLiveCampaign::where('company_id', $companyId)
                ->whereIn('user_id', $usersArray)
                ->count();
            $compromisedWhatsapp = \App\Models\WaLiveCampaign::where('company_id', $companyId)
                ->whereIn('user_id', $usersArray)
                ->where('compromised', 1)
                ->count();

            $totalAll = $totalSimulations + $totalQuishing + $totalAiVishing + $totalWhatsapp;
            $compromisedAll = $compromisedSimulations + $compromisedQuishing + $compromisedAiVishing + $compromisedWhatsapp;

            // Risk score calculation (same as user-level, but for group)
            $riskScore = $totalAll > 0
                ? 100 - round(($compromisedAll / $totalAll) * 100)
                : 100;

            // Organization impact: percent of users compromised in group
            $totalUsers = count($usersArray);
            $compromisedUsers = CampaignLive::where('company_id', $companyId)
                ->whereIn('user_id', $usersArray)
                ->where('emp_compromised', 1)
                ->distinct('user_id')
                ->count('user_id');
            $organizationImpact = $totalUsers > 0 ? round(($compromisedUsers / $totalUsers) * 100, 2) : 0;

            // End user impact: percent of simulations compromised
            $endUserImpact = $totalAll > 0 ? round(($compromisedAll / $totalAll) * 100, 2) : 0;

            $groupScores[] = [
                'group_id' => $group->group_id,
                'group_name' => $group->group_name,
                'risk_score' => $riskScore,
                'breakdown' => [
                    'organization_impact' => $organizationImpact,
                    'end_user_impact' => $endUserImpact,
                    'total_simulations' => $totalAll,
                    'total_compromised' => $compromisedAll,
                ],
            ];
        }

        return $groupScores;
    }

    private function getRiskScoreDistribution()
    {
        $companyId = Auth::user()->company_id;

        $scoreRanges = [
            'poor' => [0, 20],
            'fair' => [21, 40],
            'good' => [41, 60],
            'verygood' => [61, 80],
            'excellent' => [81, 100],
        ];

        $distribution = [
            'poor' => 0,
            'fair' => 0,
            'good' => 0,
            'verygood' => 0,
            'excellent' => 0,
        ];

        $totalUsers = \App\Models\Users::where('company_id', $companyId)->count();

        if ($totalUsers === 0) {
            return $distribution;
        }

        $users = \App\Models\Users::where('company_id', $companyId)->pluck('id');

        foreach ($users as $userId) {
            // Email simulation
            $totalSimulations = \App\Models\CampaignLive::where('company_id', $companyId)
                ->where('user_id', $userId)
                ->count();
            $compromisedSimulations = \App\Models\CampaignLive::where('company_id', $companyId)
                ->where('user_id', $userId)
                ->where('emp_compromised', 1)
                ->count();

            // Quishing simulation
            $totalQuishing = \App\Models\QuishingLiveCamp::where('company_id', $companyId)
                ->where('user_id', $userId)
                ->count();
            $compromisedQuishing = \App\Models\QuishingLiveCamp::where('company_id', $companyId)
                ->where('user_id', $userId)
                ->where('compromised', '1')
                ->count();

            // TPRM simulation
            $totalTprm = \App\Models\TprmCampaignLive::where('company_id', $companyId)
                ->where('user_id', $userId)
                ->count();
            $compromisedTprm = \App\Models\TprmCampaignLive::where('company_id', $companyId)
                ->where('user_id', $userId)
                ->where('emp_compromised', 1)
                ->count();

            // WhatsApp simulation
            $totalWhatsapp = \App\Models\WaLiveCampaign::where('company_id', $companyId)
                ->where('user_id', $userId)
                ->count();
            $compromisedWhatsapp = \App\Models\WaLiveCampaign::where('company_id', $companyId)
                ->where('user_id', $userId)
                ->where('compromised', 1)
                ->count();

            $totalAll = $totalSimulations + $totalQuishing + $totalTprm + $totalWhatsapp;
            $compromisedAll = $compromisedSimulations + $compromisedQuishing + $compromisedTprm + $compromisedWhatsapp;

            $riskScore = $totalAll > 0
                ? 100 - round(($compromisedAll / $totalAll) * 100)
                : 100; // If no simulations, assume excellent

            foreach ($scoreRanges as $label => [$min, $max]) {
                if ($riskScore >= $min && $riskScore <= $max) {
                    $distribution[$label]++;
                    break;
                }
            }
        }

        return $distribution;
    }
    private function getSimulationActivity()
    {
        $companyId = Auth::user()->company_id;

        $now = Carbon::now();
        $campaignTypes = [
            'email' => Campaign::class,
            'quishing' => QuishingCamp::class,
            'whatsapp' => WaCampaign::class,
            'ai_vishing' => AiCallCampaign::class,
            'tprm' => TprmCampaign::class,
        ];

        $result = [];

        for ($i = 0; $i < 6; $i++) {
            $monthDate = $now->copy()->subMonthsNoOverflow($i);
            $monthStart = $monthDate->copy()->startOfMonth();
            $monthEnd = $monthDate->copy()->endOfMonth();
            $monthLabel = $monthDate->format('M y');

            $monthData = ['month' => $monthLabel];

            foreach ($campaignTypes as $type => $model) {
                $count = $model::where('company_id', $companyId)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count();
                $monthData[$type . '_campaigns'] = $count;
            }

            $result[] = $monthData;
        }

        return array_reverse($result);
    }

    private function getEmailAndTrainingCounts()
    {
        $companyId = Auth::user()->company_id;

        //assigned
        $trainingAssigned = TrainingAssignedUser::where('company_id', $companyId)
            ->count();

        //completed

        $trainingCompleted = TrainingAssignedUser::where('company_id', $companyId)
            ->where('completed', 1)
            ->count();

        //inprogress
        $trainingInProgress = TrainingAssignedUser::where('company_id', $companyId)
            ->where('completed', 0)
            ->count();

        //certified
        $trainingCertified = TrainingAssignedUser::where('company_id', $companyId)
            ->where('certificate_id', '!=', null)
            ->count();

        return [
            'trainingAssigned' => $trainingAssigned,
            'trainingCompleted' => $trainingCompleted,
            'trainingInProgress' => $trainingInProgress,
            'trainingCertified' => $trainingCertified,
        ];
    }

    private function campaignCounts()
    {
        $companyId = Auth::user()->company_id;
        $emailCampaigns = Campaign::where('company_id', $companyId)
            ->count();

        $whatsappCampaigns = WaCampaign::where('company_id', $companyId)
            ->count();
        $aiVishingCampaigns = AiCallCampaign::where('company_id', $companyId)
            ->count();
        $quishingCampaigns = QuishingCamp::where('company_id', $companyId)
            ->count();
        $tprmCampaigns = TprmCampaign::where('company_id', $companyId)
            ->count();
        return [
            'emailCampaigns' => $emailCampaigns,
            'emailCampaignPercent' => $this->getCampaignPercent('email'),
            'whatsappCampaigns' => $whatsappCampaigns,
            'whatsappCampaignPercent' => $this->getCampaignPercent('whatsapp'),
            'aiVishingCampaigns' => $aiVishingCampaigns,
            'aiVishingCampaignPercent' => $this->getCampaignPercent('ai_vishing'),
            'quishingCampaigns' => $quishingCampaigns,
            'quishingCampaignPercent' => $this->getCampaignPercent('quishing'),
            'tprmCampaigns' => $tprmCampaigns,
            'tprmCampaignPercent' => $this->getCampaignPercent('tprm'),
        ];
    }

    private function getCampaignPercent($type)
    {
        $now = now();
        $startCurrent = $now->copy()->subDays(14);
        $startPrevious = $now->copy()->subDays(28);
        $companyId = Auth::user()->company_id;

        if ($type === 'email') {
            $currentPeriodCount = Campaign::where('company_id', $companyId)
                ->whereBetween('created_at', [$startCurrent, $now])
                ->count();

            $previousPeriodCount = Campaign::where('company_id', $companyId)
                ->whereBetween('created_at', [$startPrevious, $startCurrent])
                ->count();
        } else if ($type === 'whatsapp') {
            $currentPeriodCount = WaCampaign::where('company_id', $companyId)
                ->whereBetween('created_at', [$startCurrent, $now])
                ->count();

            $previousPeriodCount = WaCampaign::where('company_id', $companyId)
                ->whereBetween('created_at', [$startPrevious, $startCurrent])
                ->count();
        } else if ($type === 'ai_vishing') {
            $currentPeriodCount = AiCallCampaign::where('company_id', $companyId)
                ->whereBetween('created_at', [$startCurrent, $now])
                ->count();

            $previousPeriodCount = AiCallCampaign::where('company_id', $companyId)
                ->whereBetween('created_at', [$startPrevious, $startCurrent])
                ->count();
        } else if ($type === 'quishing') {
            $currentPeriodCount = QuishingCamp::where('company_id', $companyId)
                ->whereBetween('created_at', [$startCurrent, $now])
                ->count();

            $previousPeriodCount = QuishingCamp::where('company_id', $companyId)
                ->whereBetween('created_at', [$startPrevious, $startCurrent])
                ->count();
        } else {
            $currentPeriodCount = TprmCampaign::where('company_id', $companyId)
                ->whereBetween('created_at', [$startCurrent, $now])
                ->count();

            $previousPeriodCount = TprmCampaign::where('company_id', $companyId)
                ->whereBetween('created_at', [$startPrevious, $startCurrent])
                ->count();
        }



        if ($previousPeriodCount > 0) {
            $emailCampaignsPercent = round((($currentPeriodCount - $previousPeriodCount) / $previousPeriodCount) * 100, 2);
        } else {
            $emailCampaignsPercent = $currentPeriodCount > 0 ? 100 : 0;
        }
        return $emailCampaignsPercent;
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
            $companyId = Auth::user()->company_id;
            $now = Carbon::now();
            $startDate = $now->copy()->subDays(6)->startOfDay(); // last 7 days including today

            // Group by date to avoid duplicate bubbles for the same day
            $records = WaLiveCampaign::where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $now->endOfDay()])
                ->get(['payload_clicked', 'compromised', 'created_at']);

            // Prepare data grouped by date and type
            $bubbleData = [];
            $grouped = [];

            foreach ($records as $record) {
                $date = Carbon::parse($record->created_at)->format('d M');

                if (!isset($grouped[$date])) {
                    $grouped[$date] = [
                        'Link Clicked' => 0,
                        'Compromised' => 0,
                    ];
                }

                if ($record->payload_clicked > 0) {
                    $grouped[$date]['Link Clicked'] += (int)$record->payload_clicked;
                }
                if ($record->compromised > 0) {
                    $grouped[$date]['Compromised'] += (int)$record->compromised;
                }
            }

            // Generate bubble data for each day/type
            foreach ($grouped as $date => $types) {
                if ($types['Link Clicked'] > 0) {
                    $bubbleData[] = [
                        'x' => rand(10, 17) + (rand(0, 20) / 10),
                        'y' => round((rand(20, 150) / 100), 2),
                        'value' => $types['Link Clicked'],
                        'type' => 'Link Clicked',
                        'date' => $date,
                    ];
                }
                if ($types['Compromised'] > 0) {
                    $bubbleData[] = [
                        'x' => rand(10, 17) + (rand(0, 20) / 10),
                        'y' => round((rand(20, 150) / 100), 2),
                        'value' => $types['Compromised'],
                        'type' => 'Compromised',
                        'date' => $date,
                    ];
                }
            }

            // Prepare last 7 days array
            $last7Days = [];
            for ($i = 6; $i >= 0; $i--) {
                $last7Days[] = $now->copy()->subDays($i)->format('d M');
            }

            return response()->json([
                'status' => 'success',
                'message' => 'WhatsApp bubble chart data retrieved successfully',
                'data' => $bubbleData,
                'last_7_days' => $last7Days
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve WhatsApp bubble chart data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function whatsappReportNew(){
        try {
            $companyId = Auth::user()->company_id;
            $now = Carbon::now();
            $startDate = $now->copy()->subDays(6)->startOfDay(); // last 7 days including today

            // Fetch records for the last 7 days
            $records = WaLiveCampaign::where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $now->endOfDay()])
                ->get(['sent', 'payload_clicked', 'compromised', 'training_assigned', 'created_at']);

            // Prepare last 7 days array
            $last7Days = [];
            for ($i = 6; $i >= 0; $i--) {
                $last7Days[] = $now->copy()->subDays($i)->format('Y-m-d');
            }

            // Initialize counts for each day
            $result = [];
            foreach ($last7Days as $date) {
                $result[$date] = [
                    'sent' => 0,
                    'payload_clicked' => 0,
                    'compromised' => 0,
                    'training_assigned' => 0,
                ];
            }

            // Count records for each day
            foreach ($records as $record) {
                $date = Carbon::parse($record->created_at)->format('Y-m-d');
                if (!isset($result[$date])) continue;
                if ($record->sent == 1) {
                    $result[$date]['sent'] += 1;
                }
                if ($record->payload_clicked == 1) {
                    $result[$date]['payload_clicked'] += 1;
                }
                if ($record->compromised == 1) {
                    $result[$date]['compromised'] += 1;
                }
                if ($record->training_assigned == 1) {
                    $result[$date]['training_assigned'] += 1;
                }
            }

            // Format output for frontend (array of days)
            $output = [];
            foreach ($last7Days as $date) {
                $output[] = array_merge(['date' => Carbon::parse($date)->format('d M')], $result[$date]);
            }

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp chart data retrieved successfully',
                'data' => $output,
                'last_7_days' => array_map(function ($d) {
                    return Carbon::parse($d)->format('d M');
                }, $last7Days)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '. $e->getMessage()
            ], 500);
        }
    }

    public function aiCallReport()
    {
        try {
            $companyId = Auth::user()->company_id;
            $now = Carbon::now();
            $startDate = $now->copy()->subDays(6)->startOfDay(); // last 7 days including today

            // Fetch records for the last 7 days
            $records = AiCallCampLive::where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $now->endOfDay()])
                ->get(['training_assigned', 'call_send_response', 'call_end_response', 'call_report', 'status', 'compromised', 'created_at']);

            // Prepare last 7 days array
            $last7Days = [];
            for ($i = 6; $i >= 0; $i--) {
                $last7Days[] = $now->copy()->subDays($i)->format('Y-m-d');
            }

            // Initialize counts for each day with new naming
            $result = [];
            foreach ($last7Days as $date) {
                $result[$date] = [
                    'training_assigned' => 0,
                    'calls_completed' => 0,
                    'calls_pending' => 0,
                    'calls_waiting' => 0,
                    'calls_sent' => 0,
                    'calls_responded' => 0,
                    'transcription_logged' => 0,
                    'compromised' => 0,
                ];
            }

            // Count records for each day with new keys
            foreach ($records as $record) {
                $date = Carbon::parse($record->created_at)->format('Y-m-d');
                if (!isset($result[$date])) continue;
                if ($record->training_assigned == 1) {
                    $result[$date]['training_assigned']++;
                }
                if ($record->status === 'completed') {
                    $result[$date]['calls_completed']++;
                }
                if ($record->status === 'pending') {
                    $result[$date]['calls_pending']++;
                }
                if ($record->status === 'waiting') {
                    $result[$date]['calls_waiting']++;
                }
                if ($record->call_send_response !== null) {
                    $result[$date]['calls_sent']++;
                }
                if ($record->call_end_response !== null) {
                    $result[$date]['calls_responded']++;
                }
                if ($record->call_report !== null) {
                    $result[$date]['transcription_logged']++;
                }
                if ($record->compromised == 1) {
                    $result[$date]['compromised']++;
                }
            }

            // Format output for frontend (array of days)
            $output = [];
            foreach ($last7Days as $date) {
                $output[] = array_merge(['date' => Carbon::parse($date)->format('d M')], $result[$date]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Ai chart data retrieved successfully',
                'data' => $output,
                'last_7_days' => array_map(function ($d) {
                    return Carbon::parse($d)->format('d M');
                }, $last7Days)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '. $e->getMessage()
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

        //division
        $licenses = CompanyLicense::where('company_id', $companyId)->first();

        if (!$licenses) {
            return response()->json(['error' => 'No license found for this company'], 404);
        }

        return [
            'division' => [
                'total_alloted' => (int) $licenses->employees,
                'total_used' => (int) $licenses->used_employees,
            ],
            'bluecollar' => [
                'total_alloted' => (int) $licenses->blue_collar_employees,
                'total_used' => (int) $licenses->used_blue_collar_employees,
            ],
            'tprm' => [
                'total_alloted' => (int) $licenses->tprm_employees,
                'total_used' => (int) $licenses->used_tprm_employees,
            ],
        ];
    }

    public function riskComparison()
    {
        $companyId = Auth::user()->company_id;

        // Email Simulation
        $totalEmail = \App\Models\CampaignLive::where('company_id', $companyId)->count();
        $compromisedEmail = \App\Models\CampaignLive::where('company_id', $companyId)
            ->where('emp_compromised', 1)
            ->count();

        // Quishing Simulation
        $totalQuishing = \App\Models\QuishingLiveCamp::where('company_id', $companyId)->count();
        $compromisedQuishing = \App\Models\QuishingLiveCamp::where('company_id', $companyId)
            ->where('compromised', '1')
            ->count();

        // WhatsApp Simulation
        $totalWhatsapp = \App\Models\WaLiveCampaign::where('company_id', $companyId)->count();
        $compromisedWhatsapp = \App\Models\WaLiveCampaign::where('company_id', $companyId)
            ->where('compromised', 1)
            ->count();

        // AI Vishing Simulation
        $totalAiVishing = \App\Models\AiCallCampLive::where('company_id', $companyId)->count();
        $compromisedAiVishing = \App\Models\AiCallCampLive::where('company_id', $companyId)
            ->where('compromised', 1)
            ->count();

        // Calculate risk score for each type
        $riskScores = [
            'email' => $totalEmail > 0 ? 100 - round(($compromisedEmail / $totalEmail) * 100, 2) : 100,
            'quishing' => $totalQuishing > 0 ? 100 - round(($compromisedQuishing / $totalQuishing) * 100, 2) : 100,
            'whatsapp' => $totalWhatsapp > 0 ? 100 - round(($compromisedWhatsapp / $totalWhatsapp) * 100, 2) : 100,
            'ai_vishing' => $totalAiVishing > 0 ? 100 - round(($compromisedAiVishing / $totalAiVishing) * 100, 2) : 100,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Risk comparison data retrieved successfully',
            'data' => [
                'email' => [
                    'total' => $totalEmail,
                    'compromised' => $compromisedEmail,
                    'risk_score' => $riskScores['email'],
                ],
                'quishing' => [
                    'total' => $totalQuishing,
                    'compromised' => $compromisedQuishing,
                    'risk_score' => $riskScores['quishing'],
                ],
                'whatsapp' => [
                    'total' => $totalWhatsapp,
                    'compromised' => $compromisedWhatsapp,
                    'risk_score' => $riskScores['whatsapp'],
                ],
                'ai_vishing' => [
                    'total' => $totalAiVishing,
                    'compromised' => $compromisedAiVishing,
                    'risk_score' => $riskScores['ai_vishing'],
                ],
            ]
        ], 200);
    }

    public function getTotalAssets()
    {
        $companyId = Auth::user()->company_id;


        // Get phishing emails
        $phishingEmailsCount = DB::table('phishing_emails')
            ->where(function ($query) use ($companyId) {
                $query->where('company_id', 'default')
                    ->orWhere('company_id', $companyId);
            })
            ->count();
        $quishingEmailsCount = DB::table('qsh_templates')
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


        // Get phishing websites
        $phishingWebsitesCount = DB::table('phishing_websites')
            ->where(function ($query) use ($companyId) {
                $query->where('company_id', 'default')
                    ->orWhere('company_id', $companyId);
            })
            ->count();


        return [
            'phishing_emails' => $phishingEmailsCount,
            'quishing_emails' => $quishingEmailsCount,
            'training_modules' => $trainingModulesCount,
            'phishing_websites' => $phishingWebsitesCount,
            'senderprofile' => $senderprofileCount
        ];
    }


    public function osBrowserUsage()
    {
        $companyId = Auth::user()->company_id;

        // Models for activities
        $activityModels = [
            \App\Models\EmailCampActivity::class,
            \App\Models\QuishingActivity::class,
            \App\Models\WhatsappActivity::class,
            \App\Models\TprmActivity::class,
        ];

        // OS usage counts
        $osCounts = [
            'windows' => 0,
            'mac' => 0,
            'android' => 0,
        ];

        // Browser usage counts
        $browserCounts = [
            'chrome' => 0,
            'firefox' => 0,
            'edge' => 0,
        ];

        foreach ($activityModels as $model) {
            $osCounts['mac'] += $model::whereNotNull('client_details')
                ->whereJsonContains('client_details->platform', 'OS X')
                ->where('company_id', $companyId)
                ->count();

            $osCounts['windows'] += $model::whereNotNull('client_details')
                ->whereJsonContains('client_details->platform', 'Windows')
                ->where('company_id', $companyId)
                ->count();

            $osCounts['android'] += $model::whereNotNull('client_details')
                ->whereJsonContains('client_details->platform', 'Android')
                ->where('company_id', $companyId)
                ->count();

            $browserCounts['chrome'] += $model::whereNotNull('client_details')
                ->whereJsonContains('client_details->browser', 'Chrome')
                ->where('company_id', $companyId)
                ->count();

            $browserCounts['firefox'] += $model::whereNotNull('client_details')
                ->whereJsonContains('client_details->browser', 'Firefox')
                ->where('company_id', $companyId)
                ->count();

            $browserCounts['edge'] += $model::whereNotNull('client_details')
                ->whereJsonContains('client_details->browser', 'Edge')
                ->where('company_id', $companyId)
                ->count();
        }

        $usage = [
            'os' => $osCounts,
            'browser' => $browserCounts,
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
            //check if the $months is not before the company created date
            $startDate = now()->subMonths($months)->startOfMonth();
            $companyCreatedDate = Auth::user()->created_at;
            if ($companyCreatedDate) {
                $companyCreatedDate = Carbon::parse($companyCreatedDate);

                if ($startDate < $companyCreatedDate) {
                    $months = $companyCreatedDate->diffInMonths(now());
                    $startDate = $companyCreatedDate->startOfMonth();
                }
            }
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
                        'clicked_pp' => $this->ppDifference('clicked'),
                        'reported' => $reportRate,
                        'reported_pp' => $this->ppDifference('reported'),
                        'ignored' => $ignoreRate,
                        'ignored_pp' => $this->ppDifference('ignored'),
                        'repeat_clickers' => $repeatClickers,
                        'repeat_clickers_pp' => $this->ppDifference('repeat_clickers'),
                        'remediation_rate_percent' => $remediationRate,
                        'remediation_rate_pp' => $this->ppDifference('remediation_rate'),
                    ],
                    "phishing_events_overtime" => $this->eventsOverTime($usersArray, $months),
                    "most_engaged_phishing_material" => $this->mostEngagedPhishingMaterial($usersArray, $months),
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
                        'clicked_pp' => $this->ppDifference('clicked'),
                        'reported' => $reportRate,
                        'reported_pp' => $this->ppDifference('reported'),
                        'ignored' => $ignoreRate,
                        'ignored_pp' => $this->ppDifference('ignored'),
                        'repeat_clickers' => $repeatClickers,
                        'repeat_clickers_pp' => $this->ppDifference('repeat_clickers'),
                        'remediation_rate_percent' => $remediationRate,
                        'remediation_rate_pp' => $this->ppDifference('remediation_rate'),
                    ],
                    "phishing_events_overtime" => $this->eventsOverTime(),
                    "most_engaged_phishing_material" => $this->mostEngagedPhishingMaterial(),
                    "grouped_simulation_statistics" => $this->groupedSimulationStatistics(),
                    "employee_simulation_events" => $this->empSimulationEvents(),
                    "timing_statistics" => $this->timingStatistics(),
                    "clicks_in_week_days" => $this->clicksInWeekDays(),
                    "emotional_statistics" => $this->emotionalStatistics(),
                ]
            ], 200);
        }
    }

    private function ppDifference($type = null)
    {
        $companyId = Auth::user()->company_id;

        // Define the mapping for each type to its query
        $types = [
            'clicked' => function ($query) {
                return $query->where('payload_clicked', 1);
            },
            'reported' => function ($query) {
                return $query->where('email_reported', 1);
            },
            'ignored' => function ($query) {
                return $query->where('payload_clicked', 0);
            },
            'repeat_clickers' => function ($query) {
                return $query->where('payload_clicked', 1)
                    ->groupBy('user_email')
                    ->havingRaw('COUNT(*) > 1');
            },
            'remediation_rate' => function ($query) {
                return $query->where('email_reported', 1);
            },
        ];

        if (!isset($types[$type])) {
            return 0;
        }

        $now = now();
        $currentStart = $now->copy()->subDays(14);
        $previousStart = $now->copy()->subDays(28);

        // Total for denominator
        $totalCurrent = \App\Models\CampaignLive::where('company_id', $companyId)
            ->whereBetween('created_at', [$currentStart, $now])
            ->count();
        $totalPrevious = \App\Models\CampaignLive::where('company_id', $companyId)
            ->whereBetween('created_at', [$previousStart, $currentStart])
            ->count();

        // Numerator for each type
        if ($type === 'repeat_clickers') {
            $currentValue = \App\Models\CampaignLive::where('company_id', $companyId)
                ->whereBetween('created_at', [$currentStart, $now])
                ->where('payload_clicked', 1)
                ->groupBy('user_email')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('user_email')
                ->count();

            $previousValue = \App\Models\CampaignLive::where('company_id', $companyId)
                ->whereBetween('created_at', [$previousStart, $currentStart])
                ->where('payload_clicked', 1)
                ->groupBy('user_email')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('user_email')
                ->count();

            // Use total unique users as denominator for repeat clickers
            $totalCurrent = \App\Models\CampaignLive::where('company_id', $companyId)
                ->whereBetween('created_at', [$currentStart, $now])
                ->distinct('user_email')
                ->count('user_email');
            $totalPrevious = \App\Models\CampaignLive::where('company_id', $companyId)
                ->whereBetween('created_at', [$previousStart, $currentStart])
                ->distinct('user_email')
                ->count('user_email');
        } elseif ($type === 'remediation_rate') {
            // Remediation rate is reported/total
            $currentReported = \App\Models\CampaignLive::where('company_id', $companyId)
                ->whereBetween('created_at', [$currentStart, $now])
                ->where('email_reported', 1)
                ->count();
            $previousReported = \App\Models\CampaignLive::where('company_id', $companyId)
                ->whereBetween('created_at', [$previousStart, $currentStart])
                ->where('email_reported', 1)
                ->count();

            $currentValue = $totalCurrent > 0 ? ($currentReported / $totalCurrent) * 100 : 0;
            $previousValue = $totalPrevious > 0 ? ($previousReported / $totalPrevious) * 100 : 0;
        } else {
            $currentValue = $types[$type](\App\Models\CampaignLive::where('company_id', $companyId)
                ->whereBetween('created_at', [$currentStart, $now]))->count();
            $previousValue = $types[$type](\App\Models\CampaignLive::where('company_id', $companyId)
                ->whereBetween('created_at', [$previousStart, $currentStart]))->count();

            // For clicked, reported, ignored: use percent of total
            $currentValue = $totalCurrent > 0 ? ($currentValue / $totalCurrent) * 100 : 0;
            $previousValue = $totalPrevious > 0 ? ($previousValue / $totalPrevious) * 100 : 0;
        }

        // Calculate percentage point difference
        $ppDiff = round($currentValue - $previousValue, 2);

        return $ppDiff;
    }
    private function mostEngagedPhishingMaterial($usersArray = null, $months = null)
    {

        $companyId = Auth::user()->company_id;
        $phishingEmails = PhishingEmail::where(function ($query) use ($companyId) {
            $query->where('company_id', 'default')
                ->orWhere('company_id', $companyId);
        })
            ->whereHas('emailCampLive')
            ->get();
        if ($phishingEmails->isEmpty()) {
            return [];
        }
        $mostEngaged = [];

        if ($usersArray && $months) {
            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            foreach ($phishingEmails as $email) {
                $engagedRecords = CampaignLive::where('company_id', $companyId)
                    ->where('phishing_material', $email->id)
                    ->whereIn('user_id', $usersArray)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get();

                // You can process $engagedRecords as needed, e.g., count or push to $mostEngaged
                $mostEngaged[] = [
                    'phishing_email_name' => $email->name,
                    'sent' => $engagedRecords->where('sent', 1)->count(),
                    'mail_open' => $engagedRecords->where('mail_open', 1)->count(),
                    'payload_clicked' => $engagedRecords->where('payload_clicked', 1)->count(),
                    'compromised' => $engagedRecords->where('emp_compromised', 1)->count(),
                    'reported' => $engagedRecords->where('email_reported', 1)->count(),
                    'training_assigned' => $engagedRecords->where('training_assigned', 1)->count(),



                ];
            }
            return $mostEngaged;
        } else {


            foreach ($phishingEmails as $email) {
                $engagedRecords = CampaignLive::where('company_id', $companyId)
                    ->where('phishing_material', $email->id)
                    ->get();

                // You can process $engagedRecords as needed, e.g., count or push to $mostEngaged
                $mostEngaged[] = [
                    'phishing_email_name' => $email->name,
                    'sent' => $engagedRecords->where('sent', 1)->count(),
                    'mail_open' => $engagedRecords->where('mail_open', 1)->count(),
                    'payload_clicked' => $engagedRecords->where('payload_clicked', 1)->count(),
                    'compromised' => $engagedRecords->where('emp_compromised', 1)->count(),
                    'reported' => $engagedRecords->where('email_reported', 1)->count(),
                    'training_assigned' => $engagedRecords->where('training_assigned', 1)->count(),



                ];
            }
            return $mostEngaged;
        }
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
