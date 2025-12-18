<?php

namespace App\Services\Reports;

use App\Models\PhishingWebsite;
use App\Models\Users;
use App\Models\UsersGroup;
use App\Models\WaCampaign;
use App\Models\WaLiveCampaign;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WhatsappReportService
{
    private string $companyId;
    private ?array $usersArray = null;
    private ?Carbon $startDate = null;
    private ?Carbon $endDate = null;

    /**
     * Get the WhatsApp simulation report data.
     *
     * @param string $companyId
     * @param string|null $group
     * @param int|null $months
     * @return array
     */
    public function getWhatsappSimulationReport(string $companyId, ?string $group = null, ?int $months = null): array
    {
        $this->companyId = $companyId;

        if ($group && $months) {
            $this->initializeGroupAndDateFilters($group, $months);
        }

        $cards = $this->calculateCards();

        log_action(
            $months
                ? "Whatsapp simulation report retrived for last {$months} months"
                : 'Whatsapp simulation report retrived for all time'
        );

        return [
            "cards" => $cards,
            "phishing_events_overtime" => $this->eventsOverTime($this->usersArray, $months),
            "most_engaged_phishing_website" => $this->mostEngagedPhishingWebsite($this->usersArray, $months),
            "grouped_simulation_statistics" => $this->groupedSimulationStatistics($group, $months),
            "employee_simulation_events" => $this->empSimulationEvents($this->usersArray, $months),
            "timing_statistics" => $this->timingStatistics($this->usersArray, $months),
            "scans_in_week_days" => $this->scansInWeekDays($this->usersArray, $months),
            "emotional_statistics" => $this->emotionalStatistics($group, $months),
        ];
    }

    /**
     * Initialize group and date filters based on inputs.
     *
     * @param string $group
     * @param int $months
     * @return void
     * @throws \Exception
     */
    private function initializeGroupAndDateFilters(string $group, int $months): void
    {
        $usersGroup = UsersGroup::where('group_id', $group)
            ->where('company_id', $this->companyId)
            ->first();

        if (!$usersGroup || !$usersGroup->users) {
            throw new \Exception('No users found for the specified group');
        }

        $this->usersArray = json_decode($usersGroup->users, true);

        $this->startDate = now()->subMonths($months)->startOfMonth();
        $companyCreatedDate = Auth::user()->created_at;

        if ($companyCreatedDate) {
            $companyCreatedDate = Carbon::parse($companyCreatedDate);
            if ($this->startDate < $companyCreatedDate) {
                $this->startDate = $companyCreatedDate->startOfMonth();
            }
        }

        $this->endDate = now();
    }

    /**
     * Build the base query for WaLiveCampaign.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function baseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = WaLiveCampaign::where('company_id', $this->companyId)
            ->where('employee_type', 'normal');

        if ($this->usersArray) {
            $query->whereIn('user_id', $this->usersArray);
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [$this->startDate, $this->endDate]);
        }

        return $query;
    }

    /**
     * Safely calculate percentage, ensuring no NaN or null values.
     *
     * @param float|int|null $numerator
     * @param float|int|null $denominator
     * @param int $decimals
     * @return float
     */
    private function safePercentage($numerator, $denominator, int $decimals = 2): float
    {
        $num = $numerator ?? 0;
        $denom = $denominator ?? 0;

        if ($denom == 0 || $num == 0) {
            return 0.0;
        }

        $result = ($num / $denom) * 100;

        // Ensure the result is a valid number
        if (!is_finite($result) || is_nan($result)) {
            return 0.0;
        }

        return round($result, $decimals);
    }

    /**
     * Calculate card statistics.
     *
     * @return array
     */
    private function calculateCards(): array
    {
        $stats = $this->baseQuery()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN payload_clicked = 1 THEN 1 ELSE 0 END) as payload_clicked,
                SUM(CASE WHEN payload_clicked = 0 THEN 1 ELSE 0 END) as ignored
            ')
            ->first();

        // Ensure all values are integers, never null
        $total = (int)($stats->total ?? 0);
        $payloadClicked = (int)($stats->payload_clicked ?? 0);
        $ignored = (int)($stats->ignored ?? 0);

        $repeatClickers = $this->baseQuery()
            ->where('payload_clicked', 1)
            ->groupBy('user_email')
            ->havingRaw('COUNT(*) > 1')
            ->count(DB::raw('DISTINCT user_email'));

        $repeatClickers = (int)($repeatClickers ?? 0);

        $remediationRate = $this->safePercentage($ignored, $total);

        return [
            'total' => $total,
            'payload_clicked' => $payloadClicked,
            'payload_clicked_pp' => $this->ppDifference('payload_clicked'),
            'ignored' => $ignored,
            'ignored_pp' => $this->ppDifference('ignored'),
            'repeat_clickers' => $repeatClickers,
            'repeat_clickers_pp' => $this->ppDifference('repeat_clickers'),
            'remediation_rate_percent' => $remediationRate,
            'remediation_rate_pp' => $this->ppDifference('remediation_rate_percent'),
        ];
    }

    /**
     * Calculate percentage point difference for a given type.
     *
     * @param string $type
     * @return float
     */
    private function ppDifference(string $type): float
    {
        $now = now();
        $currentStart = $now->copy()->subDays(14);
        $previousStart = $now->copy()->subDays(28);

        if ($type === 'repeat_clickers') {
            return $this->calculateRepeatClickersPP($currentStart, $previousStart, $now);
        }

        if ($type === 'remediation_rate_percent') {
            return $this->calculateRemediationRatePP($currentStart, $previousStart, $now);
        }

        $condition = $this->getConditionForType($type);

        $query = WaLiveCampaign::where('company_id', $this->companyId);

        $current = (clone $query)
            ->whereBetween('created_at', [$currentStart, $now])
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN {$condition} THEN 1 ELSE 0 END) as matched
            ")
            ->first();

        $previous = (clone $query)
            ->whereBetween('created_at', [$previousStart, $currentStart])
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN {$condition} THEN 1 ELSE 0 END) as matched
            ")
            ->first();

        $currentValue = $current->total > 0 ? ($current->matched / $current->total) * 100 : 0;
        $previousValue = $previous->total > 0 ? ($previous->matched / $previous->total) * 100 : 0;

        return round($currentValue - $previousValue, 2);
    }

    /**
     * Get SQL condition string for a specific type.
     *
     * @param string $type
     * @return string
     */
    private function getConditionForType(string $type): string
    {
        return match ($type) {
            'payload_clicked' => 'payload_clicked = 1',
            'ignored' => 'payload_clicked = 0',
            'compromised' => 'compromised = 1',
            default => '1=0',
        };
    }

    /**
     * Calculate PP difference for repeat clickers.
     *
     * @param Carbon $currentStart
     * @param Carbon $previousStart
     * @param Carbon $now
     * @return float
     */
    private function calculateRepeatClickersPP($currentStart, $previousStart, $now): float
    {
        // Current period
        $currentValue = WaLiveCampaign::where('company_id', $this->companyId)
            ->whereBetween('created_at', [$currentStart, $now])
            ->where('payload_clicked', 1)
            ->groupBy('user_email')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_email')
            ->count();

        $totalCurrent = WaLiveCampaign::where('company_id', $this->companyId)
            ->whereBetween('created_at', [$currentStart, $now])
            ->distinct('user_email')
            ->count('user_email');

        // Previous period
        $previousValue = WaLiveCampaign::where('company_id', $this->companyId)
            ->whereBetween('created_at', [$previousStart, $currentStart])
            ->where('payload_clicked', 1)
            ->groupBy('user_email')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_email')
            ->count();

        $totalPrevious = WaLiveCampaign::where('company_id', $this->companyId)
            ->whereBetween('created_at', [$previousStart, $currentStart])
            ->distinct('user_email')
            ->count('user_email');

        $currentPercent = $totalCurrent > 0 ? ($currentValue / $totalCurrent) * 100 : 0;
        $previousPercent = $totalPrevious > 0 ? ($previousValue / $totalPrevious) * 100 : 0;

        return round($currentPercent - $previousPercent, 2);
    }

    /**
     * Calculate PP difference for remediation rate.
     *
     * @param Carbon $currentStart
     * @param Carbon $previousStart
     * @param Carbon $now
     * @return float
     */
    private function calculateRemediationRatePP($currentStart, $previousStart, $now): float
    {
        $query = WaLiveCampaign::where('company_id', $this->companyId);

        $current = (clone $query)
            ->whereBetween('created_at', [$currentStart, $now])
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN payload_clicked = 0 THEN 1 ELSE 0 END) as ignored
            ')
            ->first();

        $previous = (clone $query)
            ->whereBetween('created_at', [$previousStart, $currentStart])
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN payload_clicked = 0 THEN 1 ELSE 0 END) as ignored
            ')
            ->first();

        $currentValue = $current->total > 0 ? ($current->ignored / $current->total) * 100 : 0;
        $previousValue = $previous->total > 0 ? ($previous->ignored / $previous->total) * 100 : 0;

        return round($currentValue - $previousValue, 2);
    }

    /**
     * Get most engaged phishing websites.
     *
     * @param array|null $usersArray
     * @param int|null $months
     * @return array
     */
    private function mostEngagedPhishingWebsite(?array $usersArray = null, ?int $months = null): array
    {
        $phishingWebsites = PhishingWebsite::where(function ($query) {
            $query->where('company_id', 'default')
                ->orWhere('company_id', $this->companyId);
        })
            ->whereHas('whatsappCampLive')
            ->get();

        if ($phishingWebsites->isEmpty()) {
            return [];
        }

        $result = [];

        if ($usersArray && $months) {
            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            foreach ($phishingWebsites as $website) {
                $query = WaLiveCampaign::where('company_id', $this->companyId)
                    ->where('phishing_website', $website->id)
                    ->whereIn('user_id', $usersArray)
                    ->whereBetween('created_at', [$startDate, $endDate]);

                $stats = $query->selectRaw('
                    SUM(CASE WHEN sent = 1 THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN payload_clicked = 1 THEN 1 ELSE 0 END) as payload_clicked,
                    SUM(CASE WHEN compromised = 1 THEN 1 ELSE 0 END) as compromised,
                    SUM(CASE WHEN training_assigned = 1 THEN 1 ELSE 0 END) as training_assigned
                ')->first();

                $result[] = [
                    'phishing_website_name' => $website->name,
                    'sent' => $stats->sent,
                    'payload_clicked' => $stats->payload_clicked,
                    'compromised' => $stats->compromised,
                    'training_assigned' => $stats->training_assigned,
                ];
            }
        } else {
            foreach ($phishingWebsites as $website) {
                $query = WaLiveCampaign::where('company_id', $this->companyId)
                    ->where('phishing_website', $website->id);

                $stats = $query->selectRaw('
                    SUM(CASE WHEN sent = 1 THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN payload_clicked = 1 THEN 1 ELSE 0 END) as payload_clicked,
                    SUM(CASE WHEN compromised = 1 THEN 1 ELSE 0 END) as compromised,
                    SUM(CASE WHEN training_assigned = 1 THEN 1 ELSE 0 END) as training_assigned
                ')->first();

                $result[] = [
                    'phishing_website_name' => $website->name,
                    'sent' => $stats->sent,
                    'payload_clicked' => $stats->payload_clicked,
                    'compromised' => $stats->compromised,
                    'training_assigned' => $stats->training_assigned,
                ];
            }
        }

        return $result;
    }

    /**
     * Get WhatsApp simulation events over time.
     *
     * @param array|null $usersArray
     * @param int|null $months
     * @param string|null $company_id
     * @return array
     */
    public function eventsOverTime(?array $usersArray = null, ?int $months = null, ?string $company_id = null): array
    {
        $companyId = $company_id ?? $this->companyId;
        $monthsToShow = $months ?? 5;
        $now = Carbon::now();
        $chartData = [];

        for ($i = 0; $i < $monthsToShow; $i++) {
            $monthDate = $now->copy()->subMonthsNoOverflow($i);
            $monthStart = $monthDate->copy()->startOfMonth();
            $monthEnd = $monthDate->copy()->endOfMonth();

            $query = WaLiveCampaign::where('company_id', $companyId)
                ->whereBetween('created_at', [$monthStart, $monthEnd]);

            if ($usersArray) {
                $query->whereIn('user_id', $usersArray);
            }

            $stats = $query->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN payload_clicked = 1 THEN 1 ELSE 0 END) as payload_clicked,
                SUM(CASE WHEN payload_clicked = 0 THEN 1 ELSE 0 END) as ignored
            ')->first();

            $clickRate = $stats->total > 0 ? round(($stats->payload_clicked / $stats->total) * 100, 2) : 0;
            $ignoreRate = $stats->total > 0 ? round(($stats->ignored / $stats->total) * 100, 2) : 0;

            $chartData[] = [
                'month' => $monthDate->format('F Y'),
                'clickRate' => $clickRate,
                'targetClickRate' => 5,
                'ignoreRate' => $ignoreRate,
                'targetIgnoreRate' => 40,
            ];
        }

        return array_reverse($chartData);
    }

    /**
     * Get grouped simulation statistics.
     *
     * @param string|null $group
     * @param int|null $months
     * @return array
     */
    private function groupedSimulationStatistics(?string $group = null, ?int $months = null): array
    {
        $query = UsersGroup::with('whatsappCampaigns.campLive')
            ->where('company_id', $this->companyId);

        if ($group) {
            $query->where('group_id', $group);
        }

        $groups = $query->get();

        if ($groups->isEmpty()) {
            return [];
        }

        return $groups->map(function ($group) {
            $total = $group->whatsappCampaigns->sum(function ($campaign) {
                return $campaign->campLive->count();
            });

            $stats = [
                'totalSent' => 0,
                'payloadClicked' => 0,
                'ignored' => 0,
                'compromised' => 0,
            ];

            foreach ($group->whatsappCampaigns as $campaign) {
                $stats['totalSent'] += $campaign->campLive->where('sent', 1)->count();
                $stats['payloadClicked'] += $campaign->campLive->where('payload_clicked', 1)->count();
                $stats['ignored'] += $campaign->campLive->where('payload_clicked', 0)->count();
                $stats['compromised'] += $campaign->campLive->where('compromised', 1)->count();
            }

            return [
                'group_name' => $group->group_name,
                'total_sent' => $stats['totalSent'],
                'total_payload_clicked' => $stats['payloadClicked'],
                'click_rate' => $total > 0 ? round(($stats['payloadClicked'] / $total) * 100, 2) : 0,
                'ignored' => $stats['ignored'],
                'ignored_rate' => $total > 0 ? round(($stats['ignored'] / $total) * 100, 2) : 0,
                'compromised' => $stats['compromised'],
                'compromised_rate' => $total > 0 ? round(($stats['compromised'] / $total) * 100, 2) : 0,
            ];
        })->toArray();
    }

    /**
     * Get timing statistics.
     *
     * @param array|null $usersArray
     * @param int|null $months
     * @return array
     */
    private function timingStatistics(?array $usersArray = null, ?int $months = null): array
    {
        $query = $usersArray
            ? WaLiveCampaign::where('company_id', $this->companyId)->whereIn('user_id', $usersArray)
            : WaLiveCampaign::where('company_id', $this->companyId)
            ->whereNotNull('created_at')
            ->whereNotNull('updated_at');

        $total = (clone $query)->count();

        return [
            'avg_time_to_click_in_hours' => round(
                ((clone $query)->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_seconds')
                    ->value('avg_seconds') ?? 0) / 3600,
                2
            ),
            'percent_within_10_min' => $this->calculateTimePercentage($query, $total, 10, 'MINUTE'),
            'clicked_within_1_hour' => $this->calculateTimePercentage(
                (clone $query)->where('payload_clicked', 1),
                $total,
                60,
                'MINUTE'
            ),
            'clicked_within_1_day' => $this->calculateTimePercentage(
                (clone $query)->where('payload_clicked', 1),
                $total,
                24,
                'HOUR'
            ),
        ];
    }

    /**
     * Calculate percentage of events within a time threshold.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $total
     * @param int $threshold
     * @param string $unit
     * @return float
     */
    private function calculateTimePercentage($query, int $total, int $threshold, string $unit): float
    {
        if ($total === 0) {
            return 0;
        }

        $matched = (clone $query)
            ->whereRaw("TIMESTAMPDIFF({$unit}, created_at, updated_at) <= ?", [$threshold])
            ->whereRaw("TIMESTAMPDIFF({$unit}, created_at, updated_at) > 1")
            ->count();

        return round(($matched / $total) * 100, 2);
    }

    /**
     * Get scans distributed by weekdays.
     *
     * @param array|null $usersArray
     * @param int|null $months
     * @return array
     */
    private function scansInWeekDays(?array $usersArray = null, ?int $months = null): array
    {
        $weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $query = WaLiveCampaign::where('company_id', $this->companyId)
            ->where('payload_clicked', 1);

        if ($usersArray) {
            $query->whereIn('user_id', $usersArray);
        }

        if ($months) {
            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $total = $query->count();
        $scansByDay = (clone $query)
            ->selectRaw('DAYOFWEEK(created_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->pluck('count', 'day')
            ->toArray();

        return collect($weekDays)->map(function ($dayName, $index) use ($scansByDay, $total) {
            $dayIndex = $index + 1;
            $count = $scansByDay[$dayIndex] ?? 0;
            $percent = $total > 0 ? round(($count / $total) * 100, 2) : 0;

            return [
                'day' => $dayName,
                'percentage' => $percent
            ];
        })->toArray();
    }

    /**
     * Get employee simulation events.
     *
     * @param array|null $usersArray
     * @param int|null $months
     * @return array
     */
    private function empSimulationEvents(?array $usersArray = null, ?int $months = null): array
    {
        $query = Users::where('company_id', $this->companyId);

        if ($usersArray) {
            $query->whereIn('id', $usersArray);
        } else {
            $query->select('user_email')->distinct();
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            return [];
        }

        return $users->map(function ($user) use ($months) {
            $userEmail = $user->user_email;

            $query = WaLiveCampaign::where('company_id', $this->companyId)
                ->where('user_email', $userEmail);

            if ($months) {
                $startDate = now()->subMonths($months)->startOfMonth();
                $endDate = now();
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }

            $stats = $query->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN sent = 1 THEN 1 ELSE 0 END) as total_sent,
                SUM(CASE WHEN payload_clicked = 1 THEN 1 ELSE 0 END) as payload_clicked,
                SUM(CASE WHEN payload_clicked = 0 THEN 1 ELSE 0 END) as ignored,
                SUM(CASE WHEN compromised = 1 THEN 1 ELSE 0 END) as compromised
            ')->first();

            return [
                'user_email' => $userEmail,
                'total_sent' => $stats->total_sent,
                'total_scanned' => $stats->payload_clicked,
                'click_rate' => $stats->total > 0 ? round(($stats->payload_clicked / $stats->total) * 100, 2) : 0,
                'ignored' => $stats->ignored,
                'ignored_rate' => $stats->total > 0 ? round(($stats->ignored / $stats->total) * 100, 2) : 0,
                'compromised' => $stats->compromised,
                'compromised_rate' => $stats->total > 0 ? round(($stats->compromised / $stats->total) * 100, 2) : 0,
            ];
        })->toArray();
    }

    /**
     * Get emotional statistics.
     *
     * @param string|null $group
     * @param int|null $months
     * @return array
     */
    private function emotionalStatistics(?string $group = null, ?int $months = null): array
    {
        $query = WaCampaign::with('campaignActivity')
            ->where('company_id', $this->companyId);

        if ($group) {
            $query->where('users_group', $group);
        }

        if ($months) {
            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $campaigns = $query->get();

        if ($campaigns->isEmpty()) {
            return [];
        }

        $total = $campaigns->sum(function ($campaign) {
            return $campaign->campaignActivity->count();
        });

        $genuineWhatsapp = 0;
        $showsInterestInPhishingWebsite = 0;
        $looksSuspicious = 0;
        $totallySafe = 0;

        foreach ($campaigns as $campaign) {
            foreach ($campaign->campaignActivity as $activity) {
                if ($activity->whatsapp_sent_at && $activity->payload_clicked_at) {
                    $whatsappSentAt = Carbon::parse($activity->whatsapp_sent_at);
                    $payloadClickedAt = Carbon::parse($activity->payload_clicked_at);

                    $diffMinutes = $payloadClickedAt->diffInMinutes($whatsappSentAt);
                    if ($diffMinutes <= 30 && $diffMinutes > 2) {
                        $genuineWhatsapp++;
                    }
                }
            }

            foreach ($campaign->campaignActivity as $activity) {
                if ($activity->payload_clicked_at && $activity->compromised_at) {
                    $clickedAt = Carbon::parse($activity->payload_clicked_at);
                    $compromisedAt = Carbon::parse($activity->compromised_at);

                    $diffMinutes = $compromisedAt->diffInSeconds($clickedAt);
                    if ($diffMinutes <= 10 && $diffMinutes > 2) {
                        $showsInterestInPhishingWebsite++;
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
        $genuineWhatsappPercent = $total > 0 ? round(($genuineWhatsapp / $total) * 100, 2) : 0;
        $showsInterestInPhishingWebsitePercent = $total > 0 ? round(($showsInterestInPhishingWebsite / $total) * 100, 2) : 0;
        $looksSuspiciousPercent = $total > 0 ? round(($looksSuspicious / $total) * 100, 2) : 0;
        $totallySafePercent = $total > 0 ? round(($totallySafe / $total) * 100, 2) : 0;

        return [
            'total' => $total,
            'genuineWhatapp' => $genuineWhatsapp,
            'genuineWhatsappPercent' => $genuineWhatsappPercent,
            'showsInterestInPhishingWebsite' => $showsInterestInPhishingWebsite,
            'showsInterestInPhishingWebsitePercent' => $showsInterestInPhishingWebsitePercent,
            'looksSuspicious' => $looksSuspicious,
            'looksSuspiciousPercent' => $looksSuspiciousPercent,
            'totallySafe' => $totallySafe,
            'totallySafePercent' => $totallySafePercent
        ];
    }
}
