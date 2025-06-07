<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\TprmCampaign;
use App\Models\TprmUsersGroup;
use App\Models\TprmCampaignLive;
use Illuminate\Http\Request;
use App\Models\PhishingEmail;
use App\Http\Controllers\Controller;
use App\Models\TprmUsers;
use Illuminate\Support\Facades\Auth;

class ApiTprmReportController extends Controller
{
    public function tprmSimulationReport(Request $request)
    {
        $companyId = Auth::user()->company_id;

        if ($request->query('users_group') && $request->query('months')) {

            $group = $request->query('users_group');
            $months = $request->query('months');
            $months = (int)$months;

            $usersArray = TprmUsers::where('company_id', $companyId)
                ->where('group_id', $group)
                ->pluck('id')
                ->toArray();
            if (!$usersArray) {
                return response()->json([
                    'success' => false,
                    'message' => 'No users found for the specified group',
                ], 404);
            }

            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            $total = TprmCampaignLive::where('company_id', $companyId)
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $clicked = TprmCampaignLive::where('company_id', $companyId)
                ->where('payload_clicked', 1)
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            $reportRate = TprmCampaignLive::where('company_id', $companyId)
                ->where('email_reported', 1)
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            $ignoreRate = TprmCampaignLive::where('company_id', $companyId)
                ->where('payload_clicked', 0)
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $repeatClickers = TprmCampaignLive::where('company_id', $companyId)
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
                'message' => 'Tprm simulation report retrieved successfully',
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
                    "most_engaged_phishing_material" => $this->mostEngagedPhishingMaterial($usersArray, $months),
                    "grouped_simulation_statistics" => $this->groupedSimulationStatistics($group, $months),
                    "employee_simulation_events" => $this->empSimulationEvents($usersArray, $months),
                    "timing_statistics" => $this->timingStatistics($usersArray, $months),
                    "clicks_in_week_days" => $this->clicksInWeekDays($usersArray, $months),
                    "emotional_statistics" => $this->emotionalStatistics($group, $months),
                ]
            ], 200);
        } else {
            $total = TprmCampaignLive::where('company_id', $companyId)->count();
            $clicked = TprmCampaignLive::where('company_id', $companyId)
                ->where('payload_clicked', 1)
                ->count();
            $reportRate = TprmCampaignLive::where('company_id', $companyId)
                ->where('email_reported', 1)
                ->count();
            $ignoreRate = TprmCampaignLive::where('company_id', $companyId)
                ->where('payload_clicked', 0)
                ->count();

            $repeatClickers = TprmCampaignLive::where('company_id', $companyId)
                ->where('payload_clicked', 1)
                ->groupBy('user_email')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('user_email')
                ->count();

            $remediationRate = $total > 0 ? round(($reportRate / $total) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'message' => 'Tprm simulation report retrieved successfully',
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

    private function ppDifference()
    {
        $companyId = Auth::user()->company_id;
        $now = Carbon::now();
        $currentStart = $now->copy()->subDays(7);  // Last 7 days
        $previousStart = $now->copy()->subDays(14); // Previous 7 days
        $previousEnd = $now->copy()->subDays(7);

        // Current period
        $totalCurrent = TprmCampaignLive::where('company_id', $companyId)
            ->whereBetween('created_at', [$currentStart, $now])
            ->count();

        $clickedCurrent = TprmCampaignLive::where('company_id', $companyId)
            ->where('payload_clicked', 1)
            ->whereBetween('created_at', [$currentStart, $now])
            ->count();

        $clickRateCurrent = $totalCurrent > 0 ? ($clickedCurrent / $totalCurrent) * 100 : 0;

        // Previous period
        $totalPrevious = TprmCampaignLive::where('company_id', $companyId)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        $clickedPrevious = TprmCampaignLive::where('company_id', $companyId)
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
    private function mostEngagedPhishingMaterial($usersArray = null, $months = null){

        $companyId = Auth::user()->company_id;
        $phishingEmails = PhishingEmail::where(function($query) use ($companyId) {
                $query->where('company_id', 'default')
                    ->orWhere('company_id', $companyId);
            })
            ->whereHas('tprmCampLive')
            ->get();
        if($phishingEmails->isEmpty()){
                return [];
            }
        $mostEngaged = [];

        if ($usersArray && $months) {
            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            foreach ($phishingEmails as $email) {
                $engagedRecords = TprmCampaignLive::where('company_id', $companyId)
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
                    'reported' => $engagedRecords->where('email_reported', 1)->count()



                ];
            }
            return $mostEngaged;

        }else{
          

            foreach ($phishingEmails as $email) {
                $engagedRecords = TprmCampaignLive::where('company_id', $companyId)
                    ->where('phishing_material', $email->id)
                    ->get();

                // You can process $engagedRecords as needed, e.g., count or push to $mostEngaged
                $mostEngaged[] = [
                    'phishing_email_name' => $email->name,
                    'sent' => $engagedRecords->where('sent', 1)->count(),
                    'mail_open' => $engagedRecords->where('mail_open', 1)->count(),
                    'payload_clicked' => $engagedRecords->where('payload_clicked', 1)->count(),
                    'compromised' => $engagedRecords->where('emp_compromised', 1)->count(),
                    'reported' => $engagedRecords->where('email_reported', 1)->count()



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

                $total = TprmCampaignLive::where('company_id', $companyId)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('user_id', $usersArray)
                    ->count();

                $clicked = TprmCampaignLive::where('company_id', $companyId)
                    ->where('payload_clicked', 1)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('user_id', $usersArray)
                    ->count();

                $reported = TprmCampaignLive::where('company_id', $companyId)
                    ->where('email_reported', 1)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('user_id', $usersArray)
                    ->count();

                $ignored = TprmCampaignLive::where('company_id', $companyId)
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

                $total = TprmCampaignLive::where('company_id', $companyId)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count();

                $clicked = TprmCampaignLive::where('company_id', $companyId)
                    ->where('payload_clicked', 1)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count();

                $reported = TprmCampaignLive::where('company_id', $companyId)
                    ->where('email_reported', 1)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count();

                $ignored = TprmCampaignLive::where('company_id', $companyId)
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
            $groups = TprmUsersGroup::with('tprmCampaigns.campLive')
            ->where('company_id', $companyId)
            ->where('group_id', $group)
            ->get();
            if ($groups->isEmpty()) {
                return [];
            }
            return $groups->map(function ($group) {
                $total = $group->tprmCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->count();
                });
                $totalSent = $group->tprmCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('sent', 1)->count();
                });

                $clicked = $group->tprmCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('payload_clicked', 1)->count();
                });

                $reported = $group->tprmCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('email_reported', 1)->count();
                });

                $ignored = $group->tprmCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('payload_clicked', 0)->count();
                });
                $compromised = $group->tprmCampaigns->sum(function ($campaign) {
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
            $groups = TprmUsersGroup::with('tprmCampaigns.campLive')->where('company_id', $companyId)->get();
            if ($groups->isEmpty()) {
                return [];
            }
            return $groups->map(function ($group) {
                $total = $group->tprmCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->count();
                });
                $totalSent = $group->tprmCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('sent', 1)->count();
                });

                $clicked = $group->tprmCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('payload_clicked', 1)->count();
                });

                $reported = $group->tprmCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('email_reported', 1)->count();
                });

                $ignored = $group->tprmCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('payload_clicked', 0)->count();
                });
                $compromised = $group->tprmCampaigns->sum(function ($campaign) {
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

            $TprmCampaignLive = [
                'avg_time_to_click_in_hours' => round(
                    TprmCampaignLive::where('company_id', $companyId)
                        ->whereIn('user_id', $usersArray)
                        ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_seconds')
                        ->value('avg_seconds') / 3600,
                    2
                ),
                'percent_within_10_min' => round(
                    (
                        TprmCampaignLive::where('company_id', $companyId)
                        ->whereIn('user_id', $usersArray)
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) <= 10')
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) > 1')
                        ->count()
                        /
                        max(
                            TprmCampaignLive::where('company_id', $companyId)
                                ->count(),
                            1
                        )
                    ) * 100,
                    2
                ),
                'clicked_within_1_hour' => round(
                    (
                        TprmCampaignLive::where('company_id', $companyId)
                        ->whereIn('user_id', $usersArray)
                        ->where('payload_clicked', 1)
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) <= 60')
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) > 1')
                        ->count()
                        /
                        max(
                            TprmCampaignLive::where('company_id', $companyId)

                                ->count(),
                            1
                        )
                    ) * 100,
                    2
                ),
                'clicked_within_1_day' => round(
                    (
                        TprmCampaignLive::where('company_id', $companyId)
                        ->whereIn('user_id', $usersArray)
                        ->where('payload_clicked', 1)
                        ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) <= 24')
                        ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) > 1')
                        ->count()
                        /
                        max(
                            TprmCampaignLive::where('company_id', $companyId)

                                ->count(),
                            1
                        )
                    ) * 100,
                    2
                ),
            ];
            return $TprmCampaignLive;
        } else {
            $TprmCampaignLive = [
                'avg_time_to_click_in_hours' => round(
                    TprmCampaignLive::where('company_id', $companyId)
                        ->whereNotNull('created_at')
                        ->whereNotNull('updated_at')
                        ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_seconds')
                        ->value('avg_seconds') / 3600,
                    2
                ),
                'percent_within_10_min' => round(
                    (
                        TprmCampaignLive::where('company_id', $companyId)
                        ->whereNotNull('created_at')
                        ->whereNotNull('updated_at')
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) <= 10')
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) > 1')
                        ->count()
                        /
                        max(
                            TprmCampaignLive::where('company_id', $companyId)
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
                        TprmCampaignLive::where('company_id', $companyId)
                        ->whereNotNull('created_at')
                        ->whereNotNull('updated_at')
                        ->where('payload_clicked', 1)
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) <= 60')
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) > 1')
                        ->count()
                        /
                        max(
                            TprmCampaignLive::where('company_id', $companyId)
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
                        TprmCampaignLive::where('company_id', $companyId)
                        ->whereNotNull('created_at')
                        ->whereNotNull('updated_at')
                        ->where('payload_clicked', 1)
                        ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) <= 24')
                        ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) > 1')
                        ->count()
                        /
                        max(
                            TprmCampaignLive::where('company_id', $companyId)
                                ->whereNotNull('created_at')
                                ->whereNotNull('updated_at')
                                ->count(),
                            1
                        )
                    ) * 100,
                    2
                ),
            ];
            return $TprmCampaignLive;
        }
    }

    private function clicksInWeekDays($usersArray = null, $months = null)
    {
        $companyId = Auth::user()->company_id;

        if ($usersArray && $months) {

            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();


            $weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $total = TprmCampaignLive::where('company_id', $companyId)
                ->where('payload_clicked', 1)
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $clicksByDay = TprmCampaignLive::where('company_id', $companyId)
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
            $total = TprmCampaignLive::where('company_id', $companyId)
                ->where('payload_clicked', 1)
                ->count();

            $clicksByDay = TprmCampaignLive::where('company_id', $companyId)
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
            $uniqueUsers = TprmUsers::where('company_id', $companyId)
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

                $total = TprmCampaignLive::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $totalSent = TprmCampaignLive::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('sent', 1)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $clicked = TprmCampaignLive::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('payload_clicked', 1)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $reported = TprmCampaignLive::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('email_reported', 1)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $ignored = TprmCampaignLive::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('payload_clicked', 0)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $compromised = TprmCampaignLive::where('company_id', $companyId)
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
            $uniqueUsers = TprmUsers::where('company_id', $companyId)
                ->select('user_email')
                ->distinct()
                ->get();

            $campaignStats = [];

            foreach ($uniqueUsers as $user) {
                $userEmail = $user->user_email;

                $total = TprmCampaignLive::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->count();

                $totalSent = TprmCampaignLive::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('sent', 1)
                    ->count();

                $clicked = TprmCampaignLive::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('payload_clicked', 1)
                    ->count();

                $reported = TprmCampaignLive::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('email_reported', 1)
                    ->count();

                $ignored = TprmCampaignLive::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('payload_clicked', 0)
                    ->count();

                $compromised = TprmCampaignLive::where('company_id', $companyId)
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

            $campaigns = TprmCampaign::with('campaignActivity')
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
            $campaigns = TprmCampaign::with('campaignActivity')
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
