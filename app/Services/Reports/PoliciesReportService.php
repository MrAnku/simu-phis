<?php

namespace App\Services\Reports;

use App\Models\Policy;
use App\Models\AssignedPolicy;
use Illuminate\Support\Facades\DB;
use App\Services\Reports\MetricCalculator;

class PoliciesReportService
{
    public function fetchPoliciesReport(string $companyId): array
    {
        // Total policies available
        $totalPolicies = Policy::where('company_id', $companyId)->count();

        // Policies assigned (sent to users)
        $assignedPolicies = AssignedPolicy::where('company_id', $companyId)->get();
        $totalAssignedPolicies = $assignedPolicies->count();

        // Policies sent using campaign (assuming policies assigned via campaign have campaign_id set)
        $policiesSentViaCampaign = AssignedPolicy::where('company_id', $companyId)
            ->whereNotNull('campaign_id')
            ->count();

        // Total campaigns ran for policies (distinct campaign_id in assigned_policies)
        $totalPolicyCampaigns = AssignedPolicy::where('company_id', $companyId)
            ->whereNotNull('campaign_id')
            ->distinct('campaign_id')
            ->count('campaign_id');

        // Users who responded for quiz (json_quiz_response not null)
        $usersRespondedQuiz = AssignedPolicy::where('company_id', $companyId)
            ->whereNotNull('json_quiz_response')
            ->count();

        // Users who accepted policy
        $usersAccepted = AssignedPolicy::where('company_id', $companyId)
            ->where('accepted', 1)
            ->count();

        // Users who did not accept policy
        $usersNotAccepted = AssignedPolicy::where('company_id', $companyId)
            ->where('accepted', 0)
            ->count();

        //average time taken by users to read and accept the policy
        $averageTimeToAcceptSeconds = AssignedPolicy::where('company_id', $companyId)
            ->whereNotNull('reading_time') // time storing in seconds
            ->get()
            ->avg('reading_time');

        $averageTimeToAccept = MetricCalculator::formatAverageTimeToAccept($averageTimeToAcceptSeconds);

        // Details for each assigned policy
        $assignedPolicyDetails = MetricCalculator::formatAssignedPolicyDetails($assignedPolicies);

        return [
            'total_policies' => $totalPolicies,
            'total_policies_sent_via_campaign' => $policiesSentViaCampaign,
            'total_assigned_policies' => $totalAssignedPolicies,
            'total_policy_campaigns' => $totalPolicyCampaigns,
            'users_responded_quiz' => $usersRespondedQuiz,
            'users_accepted_policy' => $usersAccepted,
            'users_not_accepted_policy' => $usersNotAccepted,
            'assigned_policies' => $assignedPolicyDetails,
            'average_time_to_accept' => $averageTimeToAccept,
        ];
    }
}
