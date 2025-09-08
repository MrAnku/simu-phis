<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Users;
use App\Models\Policy;
use App\Models\Company;
use App\Models\UsersGroup;
use App\Models\AssignedPolicy;
use App\Models\PolicyCampaign;
use Illuminate\Console\Command;
use App\Mail\PolicyCampaignEmail;
use App\Models\PolicyCampaignLive;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Services\CheckWhitelabelService;

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

        // Check if users exist in the group
        if (!$users->isEmpty()) {
            foreach ($users as $user) {
                $camp_live = PolicyCampaignLive::create([
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->campaign_name,
                    'user_name' => $user->user_name,
                    'user_email' => $user->user_email,
                    'sent' => '0',
                    'policy' => $campaign->policy,
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
            $company_id = $company->company_id;
            setCompanyTimezone($company->company_id);
            $campaigns = PolicyCampaignLive::where('sent', 0)
                ->where('company_id', $company_id)
                ->take(5)
                ->get();

            if ($campaigns->isEmpty()) {

                continue;
            }
            $isWhitelabeled = new CheckWhitelabelService($company_id);
            if ($isWhitelabeled->isCompanyWhitelabeled()) {
                $whiteLableData = $isWhitelabeled->getWhiteLabelData();
                $companyName = $whiteLableData->company_name;
                $companyLogo = env('CLOUDFRONT_URL') . $whiteLableData->dark_logo;
                $learnDomain = "https://" . $whiteLableData->learn_domain;
                $isWhitelabeled->updateSmtpConfig();
            } else {
                $companyName = env('APP_NAME');
                $companyLogo = env('CLOUDFRONT_URL') . "/assets/images/simu-logo-dark.png";
                $learnDomain = env('SIMUPHISH_LEARNING_URL');
            }

            foreach ($campaigns as $campaign) {

                $policy = Policy::where('id', $campaign->policy)->first();

                $mailData = [
                    'user_name' => $campaign->user_name,
                    'company_name' => $companyName,
                    'assigned_at' => $campaign->created_at,
                    'policy_name' => $policy->policy_name,
                    'logo' => $companyLogo,
                    'company_id' => $campaign->company_id,
                    'learn_domain' => $learnDomain,
                ];
                try {
                    $isMailSent = Mail::to($campaign->user_email)->send(new PolicyCampaignEmail($mailData));
                } catch (\Exception $e) {
                    echo 'Failed to send email: ' . $e->getMessage() . "\n";
                }

                if ($isMailSent) {
                    echo 'Email sent to ' . $campaign->user_email . "\n";
                    $campaign->update(['sent' => 1]);

                    $isPolicyExists = AssignedPolicy::where('user_email', $campaign->user_email)
                        ->where('policy', $campaign->policy)
                        ->exists();

                    if ($isPolicyExists) {
                        echo 'Policy already assigned to ' . $campaign->user_email . "\n";
                        continue;
                    }
                    AssignedPolicy::create([
                        'campaign_id' => $campaign->campaign_id,
                        'user_name' => $campaign->user_name,
                        'user_email' => $campaign->user_email,
                        'policy' => $campaign->policy,
                        'company_id' => $campaign->company_id,
                    ]);

                    // Audit log
                    audit_log(
                        $campaign->company_id,
                        $campaign->user_email,
                        null,
                        'POLICY_ASSIGNED',
                        "'{$policy->policy_name}' has been assigned to {$campaign->user_email}",
                        'normal'
                    );
                } else {
                    echo 'Failed to send email to ' . $campaign->user_email . "\n";
                }
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
