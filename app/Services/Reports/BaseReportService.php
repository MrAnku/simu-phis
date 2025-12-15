<?php

namespace App\Services\Reports;

use App\Models\UsersGroup;
use App\Models\Users;
use Illuminate\Support\Facades\Auth;
use App\Models\CampaignLive;
use App\Models\QuishingLiveCamp;
use App\Models\WaLiveCampaign;
use App\Models\Campaign;
use App\Models\QuishingCamp;
use App\Models\WaCampaign;
use App\Models\TprmCampaign;
use App\Models\TprmCampaignLive;

class BaseReportService
{
    public function fetchDivisionUsersReporting()
    {
        try {

            $companyId = (string) (Auth::user()->company_id ?? '');

            if ($companyId === '') {
                return ResponseBuilder::divisionUsersEmpty();
            }

            $userGroups = UsersGroup::where('company_id', $companyId)->get();

            $scoreRanges = [
                'Poor' => [0, 20],
                'Fair' => [21, 40],
                'Good' => [41, 60],
                'Very Good' => [61, 80],
                'Excellent' => [81, 100],
            ];

            $divisionGroupDetails = [];

            $totalUsers = Users::where('company_id', $companyId)->count();
            if ($totalUsers === 0) {
                return ResponseBuilder::divisionUsersEmpty();
            }

            foreach ($userGroups as $group) {
                $users = json_decode($group->users, true) ?? [];
                if (empty($users)) continue;

                $groupTotalUsers = count($users);

                // ===== EMAIL =====
                $emailTotal = CampaignLive::where('company_id', $companyId)
                    ->whereIn('user_id', $users)
                    ->count();

                $emailComp = CampaignLive::where('company_id', $companyId)
                    ->whereIn('user_id', $users)
                    ->where('emp_compromised', 1)
                    ->count();

                // ===== QUISHING =====
                $quishingTotal = QuishingLiveCamp::where('company_id', $companyId)
                    ->whereIn('user_id', $users)
                    ->count();

                $quishingComp = QuishingLiveCamp::where('company_id', $companyId)
                    ->whereIn('user_id', $users)
                    ->where('compromised', '1')
                    ->count();

                // ===== WHATSAPP =====
                $waTotal = WaLiveCampaign::where('company_id', $companyId)
                    ->whereIn('user_id', $users)
                    ->count();

                $waComp = WaLiveCampaign::where('company_id', $companyId)
                    ->whereIn('user_id', $users)
                    ->where('compromised', 1)
                    ->count();

                // ===== FINAL COUNTS =====
                $groupTotalSimulations = $emailTotal + $quishingTotal + $waTotal;
                $groupTotalCompromisedSimulations = $emailComp + $quishingComp + $waComp;

                $campaignsRan = Campaign::where('users_group', $group->group_id)
                    ->where('company_id', $companyId)
                    ->count();

                $metrics = MetricCalculator::divisionMetrics(
                    $group,
                    $groupTotalUsers,
                    $groupTotalSimulations,
                    $groupTotalCompromisedSimulations,
                    $campaignsRan,
                    $scoreRanges
                );

                $divisionGroupDetails[] = $metrics;
            }


            $campaignsLast7Days = $this->campaignsLast7Days($companyId);

            return ResponseBuilder::divisionUsersSuccess(
                count($userGroups),
                $divisionGroupDetails,
                $campaignsLast7Days
            );
        } catch (\Throwable $e) {
            return ResponseBuilder::error($e->getMessage());
        }
    }

    /**
     * Returns campaign statistics of last 7 days
     *
     * @param string $companyId
     * @return array
     */
    private function campaignsLast7Days(string $companyId): array
    {
        if ($companyId === '') {
            return [];
        }

        $sevenDaysAgo = now()->subDays(7)->startOfDay();
        $campaignsLast7Days = [];

        // ===== EMAIL =====
        $emailCampaigns = Campaign::where('company_id', $companyId)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->get();

        foreach ($emailCampaigns as $camp) {
            $total = CampaignLive::where('campaign_id', $camp->campaign_id)
                ->where('company_id', $companyId)
                ->count();

            $compromised = CampaignLive::where('campaign_id', $camp->campaign_id)
                ->where('company_id', $companyId)
                ->where('emp_compromised', 1)
                ->count();

            $campaignsLast7Days[] = [
                'campaign_id' => $camp->campaign_id,
                'campaign_name' => $camp->campaign_name,
                'campaign_type' => 'email',
                'total_users' => $total,
                'compromised_users' => $compromised,
                'compromised_rate' => $total > 0 ? round(($compromised / $total) * 100, 2) : 0,
                'created_at' => $camp->created_at->format('Y-m-d H:i:s'),
            ];
        }

        // ===== QUISHING =====
        $quishingCampaigns = QuishingCamp::where('company_id', $companyId)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->get();

        foreach ($quishingCampaigns as $camp) {
            $total = QuishingLiveCamp::where('campaign_id', $camp->campaign_id)
                ->where('company_id', $companyId)
                ->count();

            $compromised = QuishingLiveCamp::where('campaign_id', $camp->campaign_id)
                ->where('company_id', $companyId)
                ->where('compromised', '1')
                ->count();

            $rate = $total > 0 ? round(($compromised / $total) * 100, 2) : 0;

            $campaignsLast7Days[] = [
                'campaign_id' => $camp->campaign_id,
                'campaign_name' => $camp->campaign_name,
                'campaign_type' => 'quishing',
                'total_users' => $total,
                'compromised_users' => $compromised,
                'compromised_rate' => $rate,
                'created_at' => $camp->created_at->format('Y-m-d H:i:s'),
            ];
        }

        // ===== WHATSAPP =====
        $waCampaigns = WaCampaign::where('company_id', $companyId)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->get();

        foreach ($waCampaigns as $camp) {
            $total = WaLiveCampaign::where('campaign_id', $camp->campaign_id)
                ->where('company_id', $companyId)
                ->count();

            $compromised = WaLiveCampaign::where('campaign_id', $camp->campaign_id)
                ->where('company_id', $companyId)
                ->where('compromised', 1)
                ->count();

            $rate = $total > 0 ? round(($compromised / $total) * 100, 2) : 0;

            $campaignsLast7Days[] = [
                'campaign_id' => $camp->campaign_id,
                'campaign_name' => $camp->campaign_name,
                'campaign_type' => 'whatsapp',
                'total_users' => $total,
                'compromised_users' => $compromised,
                'compromised_rate' => $rate,
                'created_at' => $camp->created_at->format('Y-m-d H:i:s'),
            ];
        }

        // ===== TPRM =====
        $tprmCampaigns = TprmCampaign::where('company_id', $companyId)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->get();

        foreach ($tprmCampaigns as $camp) {
            $total = TprmCampaignLive::where('campaign_id', $camp->campaign_id)
                ->where('company_id', $companyId)
                ->count();

            $compromised = TprmCampaignLive::where('campaign_id', $camp->campaign_id)
                ->where('company_id', $companyId)
                ->where('emp_compromised', 1)
                ->count();

            $rate = $total > 0 ? round(($compromised / $total) * 100, 2) : 0;

            $campaignsLast7Days[] = [
                'campaign_id' => $camp->campaign_id,
                'campaign_name' => $camp->campaign_name,
                'campaign_type' => 'tprm',
                'total_users' => $total,
                'compromised_users' => $compromised,
                'compromised_rate' => $rate,
                'created_at' => $camp->created_at->format('Y-m-d H:i:s'),
            ];
        }

        return $campaignsLast7Days;
    }
}
