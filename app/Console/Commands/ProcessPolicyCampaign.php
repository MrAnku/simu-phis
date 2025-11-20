<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Users;
use App\Models\Company;
use App\Models\UsersGroup;
use App\Models\PolicyCampaign;
use Illuminate\Console\Command;
use App\Models\PolicyCampaignLive;
use Illuminate\Support\Facades\DB;
use App\Services\PolicyAssignedService;

class ProcessPolicyCampaign extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-policy-campaign';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->processScheduledCampaigns();
        $this->sendCampaignLiveEmails();
        $this->checkCompletedCampaigns();
    }

    private function processScheduledCampaigns()
    {
        $companies = DB::table('company')
            ->where('approved', 1)
            ->where('service_status', 1)
            ->where('role', null)
            ->get();


        if ($companies->isEmpty()) {
            return;
        }
        foreach ($companies as $company) {

            setCompanyTimezone($company->company_id);

            $campaigns = PolicyCampaign::where('status', 'pending')
                ->where('company_id', $company->company_id)
                ->get();

            if ($campaigns) {
                foreach ($campaigns as $campaign) {
                    $scheduledTime = Carbon::parse($campaign->scheduled_at);
                    $currentDateTime = Carbon::now();

                    if ($scheduledTime->lessThanOrEqualTo($currentDateTime)) {
                        $this->makeCampaignLive($campaign->campaign_id);

                        $campaign->update(['status' => 'running']);
                    } else {
                        echo 'Campaign : ' . $campaign->campaign_name . ' is not yet scheduled to go live.' . "\n";
                    }
                }
            }
        }
    }

    private function makeCampaignLive($campaignid)
    {
        $campaign = PolicyCampaign::where('campaign_id', $campaignid)->first();

        // check if the group exists
        $group = UsersGroup::where('group_id', $campaign->users_group)->first();
        if (!$group) {
            echo "Group not found for campaign ID: " . $campaignid . "\n";
            return;
        }

        $userIdsJson = UsersGroup::where('group_id', $campaign->users_group)->value('users');
        if (!$userIdsJson) {
            echo "No users found in group for campaign ID: " . $campaignid . "\n";
            return;
        }
        $userIds = json_decode($userIdsJson, true);
        $users = Users::whereIn('id', $userIds)->get();

        $policies = json_decode($campaign->policy, true);

        // Check if users exist in the group
        if (!$users->isEmpty()) {
            foreach ($users as $user) {
                $camp_live = PolicyCampaignLive::create([
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->campaign_name,
                    'user_name' => $user->user_name,
                    'user_email' => $user->user_email,
                    'sent' => '0',
                    'policy' => $policies[array_rand($policies)],
                    'company_id' => $campaign->company_id,
                ]);

                // Audit log
                audit_log(
                    $campaign->company_id,
                    $campaign->user_email,
                    null,
                    'POLICY_CAMPAIGN_SIMULATED',
                    "The campaign ‘{$campaign->campaign_name}’ has been sent to {$user->user_email}",
                    'normal'
                );
            }

            echo 'Policy Campaign is live' . "\n";
        }
    }

    private function sendCampaignLiveEmails()
    {
        $companies = Company::where('approved', 1)
            ->where('role', null)
            ->where('service_status', 1)
            ->get();

        if ($companies->isEmpty()) {
            return;
        }
        foreach ($companies as $company) {
            try {

                $company_id = $company->company_id;
                setCompanyTimezone($company->company_id);
                $campaigns = PolicyCampaignLive::where('sent', 0)
                    ->where('company_id', $company_id)
                    ->take(5)
                    ->get();

                if ($campaigns->isEmpty()) {

                    continue;
                }

                foreach ($campaigns as $campaign) {

                    try {
                        $policyService = new PolicyAssignedService(
                            $campaign->campaign_id,
                            $campaign->user_name,
                            $campaign->user_email,
                            $campaign->company_id
                        );
                        $policies = PolicyCampaign::where('campaign_id', $campaign->campaign_id)->value('policy');

                        $policyService->assignPolicies($policies);

                        $campaign->update(['sent' => 1]);
                    } catch (\Exception $e) {
                        echo "Error sending policy email: " . $e->getMessage() . "\n";
                        continue;
                    }
                }
            } catch (\Exception $e) {
                echo "Error processing company ID {$company->company_id}: " . $e->getMessage() . "\n";
                continue;
            }
        }
    }

    private function checkCompletedCampaigns()
    {
        $campaigns = PolicyCampaign::where('status', 'running')
            ->get();
        if ($campaigns->isEmpty()) {
            return;
        }
        foreach ($campaigns as $campaign) {
            $live = PolicyCampaignLive::where('campaign_id', $campaign->campaign_id)
                ->where('sent', 0)
                ->get();
            if ($live->isEmpty()) {
                $campaign->update(['status' => 'completed']);
            }
        }
    }
}
