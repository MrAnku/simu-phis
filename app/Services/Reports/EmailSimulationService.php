<?php

namespace App\Services\Reports;

use App\Models\Campaign;
use App\Models\CampaignLive;
use App\Models\Users;
use App\Models\UsersGroup;
use App\Models\PhishingEmail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmailSimulationService
{
    private string $companyId;
    private ?array $usersArray = null;
    private ?Carbon $startDate = null;
    private ?Carbon $endDate = null;

    /**
     * Get the email simulation report data.
     *
     * @param string $companyId
     * @param string|null $group
     * @param int|null $months
     * @return array
     */
    public function getEmailSimulationReport(string $companyId, ?string $group = null, ?int $months = null): array
    {
        $this->companyId = $companyId;

        if ($group && $months) {
            $this->initializeGroupAndDateFilters($group, $months);
        }

        $cards = $this->calculateCards();

        log_action(
            $months
                ? "email simulation report visited for last {$months} months"
                : 'email simulation report visited for all time'
        );

        return [
            "cards" => $cards,
            "phishing_events_overtime" => $this->eventsOverTime($months),
            "most_engaged_phishing_material" => $this->mostEngagedPhishingMaterial(),
            "grouped_simulation_statistics" => $this->groupedSimulationStatistics($group, $months),
            "employee_simulation_events" => $this->empSimulationEvents(),
            "timing_statistics" => $this->timingStatistics(),
            "clicks_in_week_days" => $this->clicksInWeekDays(),
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
     * Build the base query for CampaignLive.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function baseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = CampaignLive::where('company_id', $this->companyId);

        if ($this->usersArray) {
            $query->whereIn('user_id', $this->usersArray);
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [$this->startDate, $this->endDate]);
        }

        return $query;
    }

    /**
     * Calculate card statistics.
     *
     * @return array
     */
    private function calculateCards(): array
    {
        // Single optimized query to get all counts at once
        $stats = $this->baseQuery()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN payload_clicked = 1 THEN 1 ELSE 0 END) as clicked,
                SUM(CASE WHEN email_reported = 1 THEN 1 ELSE 0 END) as reported,
                SUM(CASE WHEN payload_clicked = 0 THEN 1 ELSE 0 END) as ignored
            ')
            ->first();

        $repeatClickers = $this->baseQuery()
            ->where('payload_clicked', 1)
            ->groupBy('user_email')
            ->havingRaw('COUNT(*) > 1')
            ->count(DB::raw('DISTINCT user_email'));

        $remediationRate = $stats->total > 0 ? round(($stats->reported / $stats->total) * 100, 2) : 0;

        return [
            'total' => $stats->total,
            'clicked' => $stats->clicked,
            'clicked_pp' => $this->ppDifference('clicked'),
            'reported' => $stats->reported,
            'reported_pp' => $this->ppDifference('reported'),
            'ignored' => $stats->ignored,
            'ignored_pp' => $this->ppDifference('ignored'),
            'repeat_clickers' => $repeatClickers,
            'repeat_clickers_pp' => $this->ppDifference('repeat_clickers'),
            'remediation_rate_percent' => $remediationRate,
            'remediation_rate_pp' => $this->ppDifference('remediation_rate'),
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

        $query = CampaignLive::where('company_id', $this->companyId);

        if ($type === 'repeat_clickers') {
            return $this->calculateRepeatClickersPP($currentStart, $previousStart, $now);
        }

        if ($type === 'remediation_rate') {
            return $this->calculateRemediationRatePP($currentStart, $previousStart, $now);
        }

        // For clicked, reported, ignored - use optimized single query
        $condition = $this->getConditionForType($type);

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
            'clicked' => 'payload_clicked = 1',
            'reported' => 'email_reported = 1',
            'ignored' => 'payload_clicked = 0',
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
        $query = CampaignLive::where('company_id', $this->companyId)
            ->where('payload_clicked', 1);

        $currentStats = (clone $query)
            ->whereBetween('created_at', [$currentStart, $now])
            ->selectRaw('
                COUNT(DISTINCT user_email) as total_users,
                COUNT(DISTINCT CASE WHEN user_email IN (
                    SELECT user_email FROM campaign_live
                    WHERE company_id = ? AND payload_clicked = 1 
                    AND created_at BETWEEN ? AND ?
                    GROUP BY user_email HAVING COUNT(*) > 1
                ) THEN user_email END) as repeat_clickers
            ', [$this->companyId, $currentStart, $now])
            ->first();

        $previousStats = (clone $query)
            ->whereBetween('created_at', [$previousStart, $currentStart])
            ->selectRaw('
                COUNT(DISTINCT user_email) as total_users,
                COUNT(DISTINCT CASE WHEN user_email IN (
                    SELECT user_email FROM campaign_live
                    WHERE company_id = ? AND payload_clicked = 1 
                    AND created_at BETWEEN ? AND ?
                    GROUP BY user_email HAVING COUNT(*) > 1
                ) THEN user_email END) as repeat_clickers
            ', [$this->companyId, $previousStart, $currentStart])
            ->first();

        $currentValue = $currentStats->total_users > 0
            ? ($currentStats->repeat_clickers / $currentStats->total_users) * 100
            : 0;
        $previousValue = $previousStats->total_users > 0
            ? ($previousStats->repeat_clickers / $previousStats->total_users) * 100
            : 0;

        return round($currentValue - $previousValue, 2);
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
        $query = CampaignLive::where('company_id', $this->companyId);

        $current = (clone $query)
            ->whereBetween('created_at', [$currentStart, $now])
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN email_reported = 1 THEN 1 ELSE 0 END) as reported
            ')
            ->first();

        $previous = (clone $query)
            ->whereBetween('created_at', [$previousStart, $currentStart])
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN email_reported = 1 THEN 1 ELSE 0 END) as reported
            ')
            ->first();

        $currentValue = $current->total > 0 ? ($current->reported / $current->total) * 100 : 0;
        $previousValue = $previous->total > 0 ? ($previous->reported / $previous->total) * 100 : 0;

        return round($currentValue - $previousValue, 2);
    }

    /**
     * Get most engaged phishing materials.
     *
     * @return array
     */
    private function mostEngagedPhishingMaterial(): array
    {
        $phishingEmails = PhishingEmail::where(function ($query) {
            $query->where('company_id', 'default')
                ->orWhere('company_id', $this->companyId);
        })
            ->whereHas('emailCampLive')
            ->get();

        if ($phishingEmails->isEmpty()) {
            return [];
        }

        $result = [];
        foreach ($phishingEmails as $email) {
            $stats = $this->baseQuery()
                ->where('phishing_material', $email->id)
                ->selectRaw('
                    SUM(CASE WHEN sent = 1 THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN mail_open = 1 THEN 1 ELSE 0 END) as mail_open,
                    SUM(CASE WHEN payload_clicked = 1 THEN 1 ELSE 0 END) as payload_clicked,
                    SUM(CASE WHEN emp_compromised = 1 THEN 1 ELSE 0 END) as compromised,
                    SUM(CASE WHEN email_reported = 1 THEN 1 ELSE 0 END) as reported,
                    SUM(CASE WHEN training_assigned = 1 THEN 1 ELSE 0 END) as training_assigned
                ')
                ->first();

            $result[] = [
                'phishing_email_name' => $email->name,
                'sent' => $stats->sent,
                'mail_open' => $stats->mail_open,
                'payload_clicked' => $stats->payload_clicked,
                'compromised' => $stats->compromised,
                'reported' => $stats->reported,
                'training_assigned' => $stats->training_assigned,
            ];
        }

        return $result;
    }

    /**
     * Get email simulation events over time.
     *
     * @param int|null $months
     * @return array
     */
    private function eventsOverTime(?int $months = null): array
    {
        $monthsToShow = $months ?? 5;
        $now = Carbon::now();
        $chartData = [];

        for ($i = 0; $i < $monthsToShow; $i++) {
            $monthDate = $now->copy()->subMonthsNoOverflow($i);
            $monthStart = $monthDate->copy()->startOfMonth();
            $monthEnd = $monthDate->copy()->endOfMonth();

            $query = CampaignLive::where('company_id', $this->companyId)
                ->whereBetween('created_at', [$monthStart, $monthEnd]);

            if ($this->usersArray) {
                $query->whereIn('user_id', $this->usersArray);
            }

            $stats = $query->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN payload_clicked = 1 THEN 1 ELSE 0 END) as clicked,
                SUM(CASE WHEN email_reported = 1 THEN 1 ELSE 0 END) as reported,
                SUM(CASE WHEN payload_clicked = 0 THEN 1 ELSE 0 END) as ignored
            ')->first();

            $clickRate = $stats->total > 0 ? round(($stats->clicked / $stats->total) * 100, 2) : 0;
            $reportRate = $stats->total > 0 ? round(($stats->reported / $stats->total) * 100, 2) : 0;
            $ignoreRate = $stats->total > 0 ? round(($stats->ignored / $stats->total) * 100, 2) : 0;

            $chartData[] = [
                'month' => $monthDate->format('F Y'),
                'clickRate' => $clickRate,
                'targetClickRate' => 5,
                'reportRate' => $reportRate,
                'targetReportRate' => 40,
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
        $query = UsersGroup::with('emailCampaigns.campLive')
            ->where('company_id', $this->companyId);

        if ($group) {
            $query->where('group_id', $group);
        }

        $groups = $query->get();

        if ($groups->isEmpty()) {
            return [];
        }

        return $groups->map(function ($group) {
            $total = $group->emailCampaigns->sum(function ($campaign) {
                return $campaign->campLive->count();
            });

            $stats = [
                'totalSent' => 0,
                'clicked' => 0,
                'reported' => 0,
                'ignored' => 0,
                'compromised' => 0,
            ];

            foreach ($group->emailCampaigns as $campaign) {
                $stats['totalSent'] += $campaign->campLive->where('sent', 1)->count();
                $stats['clicked'] += $campaign->campLive->where('payload_clicked', 1)->count();
                $stats['reported'] += $campaign->campLive->where('email_reported', 1)->count();
                $stats['ignored'] += $campaign->campLive->where('payload_clicked', 0)->count();
                $stats['compromised'] += $campaign->campLive->where('emp_compromised', 1)->count();
            }

            return [
                'group_name' => $group->group_name,
                'total_sent' => $stats['totalSent'],
                'total_clicked' => $stats['clicked'],
                'click_rate' => $total > 0 ? round(($stats['clicked'] / $total) * 100, 2) : 0,
                'reported' => $stats['reported'],
                'reported_rate' => $total > 0 ? round(($stats['reported'] / $total) * 100, 2) : 0,
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
     * @return array
     */
    private function timingStatistics(): array
    {
        $query = $this->baseQuery()
            ->whereNotNull('created_at')
            ->whereNotNull('updated_at');

        return [
            'avg_time_to_click_in_hours' => round(
                ($query->clone()->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_seconds')
                    ->value('avg_seconds') ?? 0) / 3600,
                2
            ),
            'percent_within_10_min' => $this->calculateTimePercentage($query->clone(), 10, 'MINUTE'),
            'clicked_within_1_hour' => $this->calculateTimePercentage(
                $query->clone()->where('payload_clicked', 1),
                60,
                'MINUTE'
            ),
            'clicked_within_1_day' => $this->calculateTimePercentage(
                $query->clone()->where('payload_clicked', 1),
                24,
                'HOUR'
            ),
        ];
    }

    /**
     * Calculate percentage of events within a time threshold.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $threshold
     * @param string $unit
     * @return float
     */
    private function calculateTimePercentage($query, int $threshold, string $unit): float
    {
        $total = (clone $query)->count();

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
     * Get clicks distributed by weekdays.
     *
     * @param string|null $company_id
     * @return array
     */
    private function clicksInWeekDays(?string $company_id = null): array
    {
        $companyId = $company_id ?? $this->companyId;
        $weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $query = CampaignLive::where('company_id', $companyId)
            ->where('payload_clicked', 1);

        if ($this->usersArray) {
            $query->whereIn('user_id', $this->usersArray);
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [$this->startDate, $this->endDate]);
        }

        $total = $query->count();
        $clicksByDay = (clone $query)
            ->selectRaw('DAYOFWEEK(created_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->pluck('count', 'day')
            ->toArray();

        return collect($weekDays)->map(function ($dayName, $index) use ($clicksByDay, $total) {
            $dayIndex = $index + 1;
            $count = $clicksByDay[$dayIndex] ?? 0;
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
     * @return array
     */
    private function empSimulationEvents(): array
    {
        $query = Users::where('company_id', $this->companyId);

        if ($this->usersArray) {
            $query->whereIn('id', $this->usersArray);
        } else {
            $query->select('user_email')->distinct();
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            return [];
        }

        return $users->map(function ($user) {
            $userEmail = $user->user_email;

            $query = CampaignLive::where('company_id', $this->companyId)
                ->where('user_email', $userEmail);

            if ($this->startDate && $this->endDate) {
                $query->whereBetween('created_at', [$this->startDate, $this->endDate]);
            }

            $stats = $query->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN sent = 1 THEN 1 ELSE 0 END) as total_sent,
                SUM(CASE WHEN payload_clicked = 1 THEN 1 ELSE 0 END) as clicked,
                SUM(CASE WHEN email_reported = 1 THEN 1 ELSE 0 END) as reported,
                SUM(CASE WHEN payload_clicked = 0 THEN 1 ELSE 0 END) as ignored,
                SUM(CASE WHEN emp_compromised = 1 THEN 1 ELSE 0 END) as compromised
            ')->first();

            return [
                'user_email' => $userEmail,
                'total_sent' => $stats->total_sent,
                'total_clicked' => $stats->clicked,
                'click_rate' => $stats->total > 0 ? round(($stats->clicked / $stats->total) * 100, 2) : 0,
                'reported' => $stats->reported,
                'reported_rate' => $stats->total > 0 ? round(($stats->reported / $stats->total) * 100, 2) : 0,
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
        $query = Campaign::with('campaignActivity')
            ->where('company_id', $this->companyId);

        if ($group) {
            $query->where('users_group', $group);
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [$this->startDate, $this->endDate]);
        }

        $campaigns = $query->get();

        if ($campaigns->isEmpty()) {
            return [];
        }

        $total = $campaigns->sum(fn($campaign) => $campaign->campaignActivity->count());
        $stats = [
            'genuineEmail' => 0,
            'showsInterestInPhishingEmail' => 0,
            'looksSuspicious' => 0,
            'totallySafe' => 0,
        ];

        foreach ($campaigns as $campaign) {
            foreach ($campaign->campaignActivity as $activity) {
                // Genuine email check
                if ($activity->email_sent_at && $activity->email_viewed_at) {
                    $diffMinutes = Carbon::parse($activity->email_viewed_at)
                        ->diffInMinutes(Carbon::parse($activity->email_sent_at));

                    if ($diffMinutes > 2 && $diffMinutes <= 30) {
                        $stats['genuineEmail']++;
                    }
                }

                // Interest and suspicious checks
                if ($activity->email_viewed_at && $activity->payload_clicked_at) {
                    $diffSeconds = Carbon::parse($activity->payload_clicked_at)
                        ->diffInSeconds(Carbon::parse($activity->email_viewed_at));

                    if ($diffSeconds > 2 && $diffSeconds <= 10) {
                        $stats['showsInterestInPhishingEmail']++;
                    } elseif ($diffSeconds > 10 && $diffSeconds <= 120) {
                        $stats['looksSuspicious']++;
                    }
                }

                // Totally safe check
                if (!$activity->payload_clicked_at && !$activity->compromised_at) {
                    $stats['totallySafe']++;
                }
            }
        }

        return [
            'total' => $total,
            'genuineEmail' => $stats['genuineEmail'],
            'genuineEmailPercent' => $total > 0 ? round(($stats['genuineEmail'] / $total) * 100, 2) : 0,
            'showsInterestInPhishingEmail' => $stats['showsInterestInPhishingEmail'],
            'showsInterestInPhishingEmailPercent' => $total > 0 ? round(($stats['showsInterestInPhishingEmail'] / $total) * 100, 2) : 0,
            'looksSuspicious' => $stats['looksSuspicious'],
            'looksSuspiciousPercent' => $total > 0 ? round(($stats['looksSuspicious'] / $total) * 100, 2) : 0,
            'totallySafe' => $stats['totallySafe'],
            'totallySafePercent' => $total > 0 ? round(($stats['totallySafe'] / $total) * 100, 2) : 0,
        ];
    }
}
