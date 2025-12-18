<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use App\Models\Users;
use App\Models\QuishingCamp;
use App\Models\UsersGroup;
use App\Models\QuishingLiveCamp;
use App\Models\QshTemplate;
use Illuminate\Support\Facades\Auth;

class QuishingReportService
{
    public function getQuishingSimulationReport(string $companyId, ?string $group = null, ?int $months = null): array
    {
        if ($group && $months) {

            $usersArray = UsersGroup::where('group_id', $group)
                ->where('company_id', $companyId)->first()->users;
            $usersArray = json_decode($usersArray, true);

            if (!$usersArray) {
                throw new \Exception('No users found for the specified group');
            }

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

            $total = QuishingLiveCamp::where('company_id', $companyId)
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $scanned = QuishingLiveCamp::where('company_id', $companyId)
                ->where('qr_scanned', '1')
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            $reportRate = QuishingLiveCamp::where('company_id', $companyId)
                ->where('email_reported', '1')
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            $ignoreRate = QuishingLiveCamp::where('company_id', $companyId)
                ->where('qr_scanned', '0')
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $repeatScanners = QuishingLiveCamp::where('company_id', $companyId)
                ->where('qr_scanned', '1')
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('user_email')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('user_email')
                ->count();

            $remediationRate = $total > 0 ? round(($reportRate / $total) * 100, 2) : 0;

            log_action("Quishing simulation report retrived for last $months months");

            return [
                "cards" => [
                    'total' => $total,
                    'qr_scanned' => $scanned,
                    'qr_scanned_pp' => $this->ppDifference('qr_scanned'),
                    'reported' => $reportRate,
                    'reported_pp' => $this->ppDifference('reported'),
                    'ignored' => $ignoreRate,
                    'ignored_pp' => $this->ppDifference('ignored'),
                    'repeat_scanners' => $repeatScanners,
                    'repeat_scanners_pp' => $this->ppDifference('repeat_scanners'),
                    'remediation_rate_percent' => $remediationRate,
                    'remediation_rate_pp' => $this->ppDifference('remediation_rate_percent'),

                ],
                "phishing_events_overtime" => $this->eventsOverTime($usersArray, $months),
                "most_engaged_quishing_material" => $this->mostEngagedPhishingMaterial($usersArray, $months),
                "grouped_simulation_statistics" => $this->groupedSimulationStatistics($group, $months),
                "employee_simulation_events" => $this->empSimulationEvents($usersArray, $months),
                "timing_statistics" => $this->timingStatistics($usersArray, $months),
                "scans_in_week_days" => $this->scansInWeekDays($usersArray, $months),
                "emotional_statistics" => $this->emotionalStatistics($group, $months),
            ];
        } else {
            $total = QuishingLiveCamp::where('company_id', $companyId)->count();
            $scanned = QuishingLiveCamp::where('company_id', $companyId)
                ->where('qr_scanned', '1')
                ->count();
            $reportRate = QuishingLiveCamp::where('company_id', $companyId)
                ->where('email_reported', '1')
                ->count();
            $ignoreRate = QuishingLiveCamp::where('company_id', $companyId)
                ->where('qr_scanned', '0')
                ->count();

            $repeatScanners = QuishingLiveCamp::where('company_id', $companyId)
                ->where('qr_scanned', '1')
                ->groupBy('user_email')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('user_email')
                ->count();

            $remediationRate = $total > 0 ? round(($reportRate / $total) * 100, 2) : 0;

            log_action("Quishing simulation report for all time");

            return [
                "cards" => [
                    'total' => $total,
                    'qr_scanned' => $scanned,
                    'qr_scanned_pp' => $this->ppDifference('qr_scanned'),
                    'reported' => $reportRate,
                    'reported_pp' => $this->ppDifference('reported'),
                    'ignored' => $ignoreRate,
                    'ignored_pp' => $this->ppDifference('ignored'),
                    'repeat_scanners' => $repeatScanners,
                    'repeat_scanners_pp' => $this->ppDifference('repeat_scanners'),
                    'remediation_rate_percent' => $remediationRate,
                    'remediation_rate_pp' => $this->ppDifference('remediation_rate_percent'),
                ],
                "phishing_events_overtime" => $this->eventsOverTime(),
                "most_engaged_quishing_material" => $this->mostEngagedPhishingMaterial(),
                "grouped_simulation_statistics" => $this->groupedSimulationStatistics(),
                "employee_simulation_events" => $this->empSimulationEvents(),
                "timing_statistics" => $this->timingStatistics(),
                "scans_in_week_days" => $this->scansInWeekDays(),
                "emotional_statistics" => $this->emotionalStatistics(),
            ];
        }
    }

    private function ppDifference($type)
    {
        $companyId = Auth::user()->company_id;

        // Map input type to QuishingLiveCamp fields
        $types = [
            'qr_scanned' => function ($query) {
                return $query->where('qr_scanned', '1');
            },
            'reported' => function ($query) {
                return $query->where('email_reported', '1');
            },
            'ignored' => function ($query) {
                return $query->where('qr_scanned', '0');
            },
            'repeat_scanners' => function ($query) {
                return $query->where('qr_scanned', '1')
                    ->groupBy('user_email')
                    ->havingRaw('COUNT(*) > 1');
            },
            'remediation_rate_percent' => function ($query) {
                return $query->where('email_reported', '1');
            },
        ];

        if (!isset($types[$type])) {
            return 0;
        }

        $now = now();
        $currentStart = $now->copy()->subDays(14);
        $previousStart = $now->copy()->subDays(28);

        // Total for denominator
        $totalCurrent = QuishingLiveCamp::where('company_id', $companyId)
            ->whereBetween('created_at', [$currentStart, $now])
            ->count();
        $totalPrevious = QuishingLiveCamp::where('company_id', $companyId)
            ->whereBetween('created_at', [$previousStart, $currentStart])
            ->count();

        // Numerator for each type
        if ($type === 'repeat_scanners') {
            $currentValue = QuishingLiveCamp::where('company_id', $companyId)
                ->whereBetween('created_at', [$currentStart, $now])
                ->where('qr_scanned', '1')
                ->groupBy('user_email')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('user_email')
                ->count();

            $previousValue = QuishingLiveCamp::where('company_id', $companyId)
                ->whereBetween('created_at', [$previousStart, $currentStart])
                ->where('qr_scanned', '1')
                ->groupBy('user_email')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('user_email')
                ->count();

            // Use total unique users as denominator for repeat scanners
            $totalCurrent = QuishingLiveCamp::where('company_id', $companyId)
                ->whereBetween('created_at', [$currentStart, $now])
                ->distinct('user_email')
                ->count('user_email');
            $totalPrevious = QuishingLiveCamp::where('company_id', $companyId)
                ->whereBetween('created_at', [$previousStart, $currentStart])
                ->distinct('user_email')
                ->count('user_email');

            $currentValue = $totalCurrent > 0 ? ($currentValue / $totalCurrent) * 100 : 0;
            $previousValue = $totalPrevious > 0 ? ($previousValue / $totalPrevious) * 100 : 0;
        } elseif ($type === 'remediation_rate_percent') {
            // Remediation rate is reported/total
            $currentReported = QuishingLiveCamp::where('company_id', $companyId)
                ->whereBetween('created_at', [$currentStart, $now])
                ->where('email_reported', '1')
                ->count();
            $previousReported = QuishingLiveCamp::where('company_id', $companyId)
                ->whereBetween('created_at', [$previousStart, $currentStart])
                ->where('email_reported', '1')
                ->count();

            $currentValue = $totalCurrent > 0 ? ($currentReported / $totalCurrent) * 100 : 0;
            $previousValue = $totalPrevious > 0 ? ($previousReported / $totalPrevious) * 100 : 0;
        } else {
            $currentValue = $types[$type](QuishingLiveCamp::where('company_id', $companyId)
                ->whereBetween('created_at', [$currentStart, $now]))->count();
            $previousValue = $types[$type](QuishingLiveCamp::where('company_id', $companyId)
                ->whereBetween('created_at', [$previousStart, $currentStart]))->count();

            // For qr_scanned, reported, ignored: use percent of total
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
        $phishingEmails = QshTemplate::where(function ($query) use ($companyId) {
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
                $engagedRecords = QuishingLiveCamp::where('company_id', $companyId)
                    ->where('quishing_material', $email->id)
                    ->whereIn('user_id', $usersArray)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get();

                // You can process $engagedRecords as needed, e.g., count or push to $mostEngaged
                $mostEngaged[] = [
                    'phishing_email_name' => $email->name,
                    'sent' => $engagedRecords->where('sent', '1')->count(),
                    'mail_open' => $engagedRecords->where('mail_open', '1')->count(),
                    'qr_scanned' => $engagedRecords->where('qr_scanned', '1')->count(),
                    'compromised' => $engagedRecords->where('compromised', '1')->count(),
                    'reported' => $engagedRecords->where('email_reported', '1')->count(),
                    'training_assigned' => $engagedRecords->where('training_assigned', '1')->count(),



                ];
            }
            return $mostEngaged;
        } else {


            foreach ($phishingEmails as $email) {
                $engagedRecords = QuishingLiveCamp::where('company_id', $companyId)
                    ->where('quishing_material', $email->id)
                    ->get();

                // You can process $engagedRecords as needed, e.g., count or push to $mostEngaged
                $mostEngaged[] = [
                    'phishing_email_name' => $email->name,
                    'sent' => $engagedRecords->where('sent', '1')->count(),
                    'mail_open' => $engagedRecords->where('mail_open', '1')->count(),
                    'qr_scanned' => $engagedRecords->where('qr_scanned', '1')->count(),
                    'compromised' => $engagedRecords->where('compromised', '1')->count(),
                    'reported' => $engagedRecords->where('email_reported', '1')->count(),
                    'training_assigned' => $engagedRecords->where('training_assigned', '1')->count(),



                ];
            }
            return $mostEngaged;
        }
    }
    public function eventsOverTime($usersArray = null, $months = null, $company_id = null)
    {
        $companyId = $company_id ?? Auth::user()->company_id;
        $now = Carbon::now();
        $chartData = [];

        if ($usersArray && $months) {


            for ($i = 0; $i < (int)$months; $i++) {
                $monthDate = $now->copy()->subMonthsNoOverflow($i);
                $monthStart = $monthDate->copy()->startOfMonth();
                $monthEnd = $monthDate->copy()->endOfMonth();

                $total = QuishingLiveCamp::where('company_id', $companyId)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('user_id', $usersArray)
                    ->count();

                $scanned = QuishingLiveCamp::where('company_id', $companyId)
                    ->where('qr_scanned', '1')
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('user_id', $usersArray)
                    ->count();

                $reported = QuishingLiveCamp::where('company_id', $companyId)
                    ->where('email_reported', '1')
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('user_id', $usersArray)
                    ->count();

                $ignored = QuishingLiveCamp::where('company_id', $companyId)
                    ->where('qr_scanned', '0')
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('user_id', $usersArray)
                    ->count();

                $scanRate = $total > 0 ? round(($scanned / $total) * 100, 2) : 0;
                $reportRate = $total > 0 ? round(($reported / $total) * 100, 2) : 0;
                $ignoreRate = $total > 0 ? round(($ignored / $total) * 100, 2) : 0;

                // Example target rates, adjust as needed
                $targetScanRate = 5;
                $targetReportRate = 40;
                $targetIgnoreRate = 40;

                $chartData[] = [
                    'month' => $monthDate->format('F Y'),
                    'scanRate' => $scanRate,
                    'targetScanRate' => $targetScanRate,
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

                $total = QuishingLiveCamp::where('company_id', $companyId)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count();

                $scanned = QuishingLiveCamp::where('company_id', $companyId)
                    ->where('qr_scanned', '1')
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count();

                $reported = QuishingLiveCamp::where('company_id', $companyId)
                    ->where('email_reported', '1')
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count();

                $ignored = QuishingLiveCamp::where('company_id', $companyId)
                    ->where('qr_scanned', '0')
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count();

                $scanRate = $total > 0 ? round(($scanned / $total) * 100, 2) : 0;
                $reportRate = $total > 0 ? round(($reported / $total) * 100, 2) : 0;
                $ignoreRate = $total > 0 ? round(($ignored / $total) * 100, 2) : 0;

                // Example target rates, adjust as needed
                $targetScanRate = 5;
                $targetReportRate = 40;
                $targetIgnoreRate = 40;

                $chartData[] = [
                    'month' => $monthDate->format('F Y'),
                    'scanRate' => $scanRate,
                    'targetScanRate' => $targetScanRate,
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
                    return $campaign->campLive->where('sent', '1')->count();
                });

                $scanned = $group->emailCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('qr_scanned', '1')->count();
                });

                $reported = $group->emailCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('email_reported', '1')->count();
                });

                $ignored = $group->emailCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('qr_scanned', '0')->count();
                });
                $compromised = $group->emailCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('compromised', '1')->count();
                });

                return [
                    'group_name' => $group->group_name,
                    'total_sent' => $totalSent,
                    'total_qr_scanned' => $scanned,
                    'scan_rate' => $total > 0 ? round(($scanned / $total) * 100, 2) : 0,
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
                    return $campaign->campLive->where('sent', '1')->count();
                });

                $scanned = $group->emailCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('qr_scanned', '1')->count();
                });

                $reported = $group->emailCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('email_reported', '1')->count();
                });

                $ignored = $group->emailCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('qr_scanned', '0')->count();
                });
                $compromised = $group->emailCampaigns->sum(function ($campaign) {
                    return $campaign->campLive->where('compromised', '1')->count();
                });

                return [
                    'group_name' => $group->group_name,
                    'total_sent' => $totalSent,
                    'total_qr_scanned' => $scanned,
                    'scan_rate' => $total > 0 ? round(($scanned / $total) * 100, 2) : 0,
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

            $QuishingLiveCamp = [
                'avg_time_to_scan_in_hours' => round(
                    QuishingLiveCamp::where('company_id', $companyId)
                        ->whereIn('user_id', $usersArray)
                        ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_seconds')
                        ->value('avg_seconds') / 3600,
                    2
                ),
                'percent_within_10_min' => round(
                    (
                        QuishingLiveCamp::where('company_id', $companyId)
                        ->whereIn('user_id', $usersArray)
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) <= 10')
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) > 1')
                        ->count()
                        /
                        max(
                            QuishingLiveCamp::where('company_id', $companyId)
                                ->count(),
                            1
                        )
                    ) * 100,
                    2
                ),
                'scanned_within_1_hour' => round(
                    (
                        QuishingLiveCamp::where('company_id', $companyId)
                        ->whereIn('user_id', $usersArray)
                        ->where('qr_scanned', '1')
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) <= 60')
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) > 1')
                        ->count()
                        /
                        max(
                            QuishingLiveCamp::where('company_id', $companyId)

                                ->count(),
                            1
                        )
                    ) * 100,
                    2
                ),
                'scanned_within_1_day' => round(
                    (
                        QuishingLiveCamp::where('company_id', $companyId)
                        ->whereIn('user_id', $usersArray)
                        ->where('qr_scanned', '1')
                        ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) <= 24')
                        ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) > 1')
                        ->count()
                        /
                        max(
                            QuishingLiveCamp::where('company_id', $companyId)

                                ->count(),
                            1
                        )
                    ) * 100,
                    2
                ),
            ];
            return $QuishingLiveCamp;
        } else {
            $QuishingLiveCamp = [
                'avg_time_to_scan_in_hours' => round(
                    QuishingLiveCamp::where('company_id', $companyId)
                        ->whereNotNull('created_at')
                        ->whereNotNull('updated_at')
                        ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_seconds')
                        ->value('avg_seconds') / 3600,
                    2
                ),
                'percent_within_10_min' => round(
                    (
                        QuishingLiveCamp::where('company_id', $companyId)
                        ->whereNotNull('created_at')
                        ->whereNotNull('updated_at')
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) <= 10')
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) > 1')
                        ->count()
                        /
                        max(
                            QuishingLiveCamp::where('company_id', $companyId)
                                ->whereNotNull('created_at')
                                ->whereNotNull('updated_at')
                                ->count(),
                            1
                        )
                    ) * 100,
                    2
                ),
                'scanned_within_1_hour' => round(
                    (
                        QuishingLiveCamp::where('company_id', $companyId)
                        ->whereNotNull('created_at')
                        ->whereNotNull('updated_at')
                        ->where('qr_scanned', '1')
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) <= 60')
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) > 1')
                        ->count()
                        /
                        max(
                            QuishingLiveCamp::where('company_id', $companyId)
                                ->whereNotNull('created_at')
                                ->whereNotNull('updated_at')
                                ->count(),
                            1
                        )
                    ) * 100,
                    2
                ),
                'scanned_within_1_day' => round(
                    (
                        QuishingLiveCamp::where('company_id', $companyId)
                        ->whereNotNull('created_at')
                        ->whereNotNull('updated_at')
                        ->where('qr_scanned', '1')
                        ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) <= 24')
                        ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) > 1')
                        ->count()
                        /
                        max(
                            QuishingLiveCamp::where('company_id', $companyId)
                                ->whereNotNull('created_at')
                                ->whereNotNull('updated_at')
                                ->count(),
                            1
                        )
                    ) * 100,
                    2
                ),
            ];
            return $QuishingLiveCamp;
        }
    }

    private function scansInWeekDays($usersArray = null, $months = null)
    {
        $companyId = Auth::user()->company_id;

        if ($usersArray && $months) {

            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();


            $weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $total = QuishingLiveCamp::where('company_id', $companyId)
                ->where('qr_scanned', '1')
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $scansByDay = QuishingLiveCamp::where('company_id', $companyId)
                ->where('qr_scanned', '1')
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
                $count = isset($scansByDay[$dayIndex]) ? $scansByDay[$dayIndex] : 0;
                $percent = $total > 0 ? round(($count / $total) * 100, 2) : 0;
                $result[] = [
                    'day' => $dayName,
                    'percentage' => $percent
                ];
            }

            return $result;
        } else {
            $weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $total = QuishingLiveCamp::where('company_id', $companyId)
                ->where('qr_scanned', '1')
                ->count();

            $scansByDay = QuishingLiveCamp::where('company_id', $companyId)
                ->where('qr_scanned', '1')
                ->selectRaw('DAYOFWEEK(created_at) as day, COUNT(*) as count')
                ->groupBy('day')
                ->pluck('count', 'day')
                ->toArray();

            $result = [];
            foreach ($weekDays as $i => $dayName) {
                // DAYOFWEEK returns 1 (Sunday) to 7 (Saturday)
                $dayIndex = $i + 1;
                $count = isset($scansByDay[$dayIndex]) ? $scansByDay[$dayIndex] : 0;
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

                $total = QuishingLiveCamp::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $totalSent = QuishingLiveCamp::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('sent', '1')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $scanned = QuishingLiveCamp::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('qr_scanned', '1')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $reported = QuishingLiveCamp::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('email_reported', '1')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $ignored = QuishingLiveCamp::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('qr_scanned', '0')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $compromised = QuishingLiveCamp::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('compromised', '1')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $campaignStats[] = [
                    'user_email' => $userEmail,
                    'total_sent' => $totalSent,
                    'total_scanned' => $scanned,
                    'scan_rate' => $total > 0 ? round(($scanned / $total) * 100, 2) : 0,
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

                $total = QuishingLiveCamp::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->count();

                $totalSent = QuishingLiveCamp::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('sent', '1')
                    ->count();

                $scanned = QuishingLiveCamp::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('qr_scanned', '1')
                    ->count();

                $reported = QuishingLiveCamp::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('email_reported', '1')
                    ->count();

                $ignored = QuishingLiveCamp::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('qr_scanned', '0')
                    ->count();

                $compromised = QuishingLiveCamp::where('company_id', $companyId)
                    ->where('user_email', $userEmail)
                    ->where('compromised', '1')
                    ->count();

                $campaignStats[] = [
                    'user_email' => $userEmail,
                    'total_sent' => $totalSent,
                    'total_scanned' => $scanned,
                    'scan_rate' => $total > 0 ? round(($scanned / $total) * 100, 2) : 0,
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

            $campaigns = QuishingCamp::with('campaignActivity')
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
            $campaigns = QuishingCamp::with('campaignActivity')
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
