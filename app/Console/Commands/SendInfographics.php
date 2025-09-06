<?php

namespace App\Console\Commands;

use App\Mail\InfographicsEmail;
use Carbon\Carbon;
use App\Models\Users;
use App\Models\Company;
use App\Models\UsersGroup;
use Illuminate\Console\Command;
use App\Models\InfoGraphicCampaign;
use Illuminate\Support\Facades\Mail;
use App\Models\InfoGraphicLiveCampaign;
use App\Models\Inforgraphic;
use App\Services\CheckWhitelabelService;

class SendInfographics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-infographics';

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
        $companies = Company::where('approved', 1)
            ->where('service_status', 1)
            ->where('role', null)
            ->get();

        if ($companies->isEmpty()) {
            return;
        }
        foreach ($companies as $company) {

            setCompanyTimezone($company->company_id);

            $campaigns = InfoGraphicCampaign::where('status', 'pending')
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
                        echo 'infographics Campaign : ' . $campaign->campaign_name . ' is not yet scheduled to go live.' . "\n";
                    }
                }
            }
        }
    }

    private function makeCampaignLive($campaignid)
    {
        $campaign = InfoGraphicCampaign::where('campaign_id', $campaignid)->first();

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
                $camp_live = InfoGraphicLiveCampaign::create([
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->campaign_name,
                    'user_name' => $user->user_name,
                    'user_email' => $user->user_email,
                    'sent' => 0,
                    'infographic' => collect($campaign->infographics)->random(),
                    'company_id' => $campaign->company_id,
                ]);

                // Audit log
                 audit_log(
                    $campaign->company_id,
                    $campaign->user_email,
                    null,
                    'INFOGRAPHICS CAMPAIGN LAUNCHED',
                    "'{$campaign->campaign_name}' shoot to {$user->user_email}",
                    'normal'
                );
            }

            echo 'Inforgraphics Campaign is live' . "\n";
        }
    }

    private function sendCampaignLiveEmails()
    {
        $companies = Company::where('approved', 1)
            ->where('service_status', 1)
            ->where('role', null)
            ->get();

        if ($companies->isEmpty()) {
            return;
        }
        foreach ($companies as $company) {
            $company_id = $company->company_id;
            setCompanyTimezone($company->company_id);
            $campaigns = InfoGraphicLiveCampaign::where('sent', 0)
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

                $isWhitelabeled->updateSmtpConfig();
                
            }else{
                $companyName = env('APP_NAME');
                $companyLogo = env('CLOUDFRONT_URL') . "/assets/images/simu-logo-dark.png";
                
            }

            foreach ($campaigns as $campaign) {

                $infographic = Inforgraphic::where('id', $campaign->infographic)->first();

                $mailData = [
                    'user_name' => $campaign->user_name,
                    'company_name' => $companyName,
                    'infographic' => env('CLOUDFRONT_URL') . $infographic->file_path,
                    'logo' => $companyLogo
                ];

                $isMailSent = Mail::to($campaign->user_email)->send(new InfographicsEmail($mailData));

                if ($isMailSent) {
                    echo 'Infographic sent to ' . $campaign->user_email . "\n";
                    $campaign->update(['sent' => 1]);

                  
                } else {
                    echo 'Failed to send infographic to ' . $campaign->user_email . "\n";
                }
            }
        }
    }

    private function checkCompletedCampaigns()
    {
        $campaigns = InfoGraphicCampaign::where('status', 'running')
            ->get();
        if ($campaigns->isEmpty()) {
            return;
        }
        foreach ($campaigns as $campaign) {
            $live = InfoGraphicLiveCampaign::where('campaign_id', $campaign->campaign_id)
                ->where('sent', 0)
                ->get();
            if ($live->isEmpty()) {
                $campaign->update(['status' => 'completed']);
            }
        }
    }
}
