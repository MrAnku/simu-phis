<?php

namespace App\Console\Commands;

use App\Mail\ComicAssignMail;
use App\Mail\InfographicsEmail;
use App\Models\ComicAssignedUser;
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
        try {
            $this->processScheduledCampaigns();
            $this->sendCampaignLiveEmails();
            $this->checkCompletedCampaigns();
        } catch (\Exception $e) {
            echo 'Error occurred: ' . $e->getMessage() . "\n";
        }
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
                    'user_id' => $user->id,
                    'user_name' => $user->user_name,
                    'user_email' => $user->user_email,
                    'sent' => 0,
                    'infographic' => $campaign->inforgraphics != null ? $this->getRandom($campaign->inforgraphics) : null,
                    'comic' => $campaign->comics != null ? $this->getRandom($campaign->comics) : null,
                    'company_id' => $campaign->company_id,
                ]);

                // Audit log
                audit_log(
                    $campaign->company_id,
                    $campaign->user_email,
                    null,
                    'INFOGRAPHICS_CAMPAIGN_SIMULATED',
                    "The campaign ‘{$campaign->campaign_name}’ has been sent to {$user->user_email}",
                    'normal'
                );
            }

            echo 'Inforgraphics Campaign is live' . "\n";
        }
    }

    private function getRandom($arrayInString)
    {
        $array = json_decode($arrayInString, true);
        if (empty($array)) {
            return null;
        }
        $randomIndex = array_rand($array);
        return $array[$randomIndex];
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
                $learningPortalUrl = $whiteLableData->learn_domain;
                $isWhitelabeled->updateSmtpConfig();
            } else {
                $isWhitelabeled->clearSmtpConfig();
                $companyName = env('APP_NAME');
                $companyLogo = env('CLOUDFRONT_URL') . "/assets/images/simu-logo-dark.png";
                $learningPortalUrl = env('SIMUPHISH_LEARNING_URL');
            }

            foreach ($campaigns as $campaign) {
                if ($campaign->infographic != null) {
                    $this->sendInfographicEmail($campaign, $companyName, $companyLogo);
                }
                if ($campaign->comic != null) {
                    $this->assignComic($campaign);
                    try {
                        Mail::to($campaign->user_email)->send(new ComicAssignMail(
                            $campaign->user_name,
                            $companyName,
                            $companyLogo,
                            $learningPortalUrl
                        ));
                    } catch (\Exception $e) {
                        echo 'Failed to send comic assignment email to ' . $campaign->user_email . "\n";
                        continue;
                    }
                }
                $campaign->update(['sent' => 1]);
            }
        }
    }

    private function sendInfographicEmail($campaign, $companyName, $companyLogo)
    {
        if ($campaign->infographic == null) {
            echo 'No infographic assigned for user ' . $campaign->user_email . "\n";

            return;
        }

        $infographic = Inforgraphic::where('id', $campaign->infographic)->first();


        if ($campaign->infographic == null) {
            echo 'No infographic assigned for user ' . $campaign->user_email . "\n";

            return;
        }

        $infographic = Inforgraphic::where('id', $campaign->infographic)->first();

        if (!$infographic) {
            echo 'Infographic not found for user ' . $campaign->user_email . "\n";

            return;
        }

        $mailData = [
            'user_name' => $campaign->user_name,
            'company_name' => $companyName,
            'infographic' => env('CLOUDFRONT_URL') . $infographic->file_path,
            'logo' => $companyLogo
        ];

        $isMailSent = Mail::to($campaign->user_email)->send(new InfographicsEmail($mailData));

        if ($isMailSent) {
            echo 'Infographic sent to ' . $campaign->user_email . "\n";
        } else {
            echo 'Failed to send infographic to ' . $campaign->user_email . "\n";
        }
    }

    private function assignComic($campaign)
    {
        //check assignment
        $assignment = $campaign->camp?->comic_assignment;
        if ($assignment == 'random') {
            //check if this comic to this user is already assigned
            $alreadyAssigned = ComicAssignedUser::where('comic', $campaign->comic)
                ->where('user_email', $campaign->user_email)
                ->first();
            if ($alreadyAssigned) {
                // Comic is already assigned to this user
                echo 'Comic is already assigned to user ' . $campaign->user_email . "\n";
                return;
            }
            ComicAssignedUser::create([
                'campaign_id' => $campaign->campaign_id,
                'user_id' => $campaign->user_id,
                'user_name' => $campaign->user_name,
                'user_email' => $campaign->user_email,
                'comic' => $campaign->comic,
                'assigned_at' => Carbon::now(),
                'seen_at' => null,
                'company_id' => $campaign->company_id,
            ]);
        } else {
            $allComics = json_decode($campaign->camp?->comics, true);
            foreach ($allComics as $comicId) {
                //check if this comic to this user is already assigned
                $alreadyAssigned = ComicAssignedUser::where('comic', $comicId)
                    ->where('user_email', $campaign->user_email)
                    ->first();
                if ($alreadyAssigned) {
                    // Comic is already assigned to this user
                    echo 'Comic is already assigned to user ' . $campaign->user_email . "\n";
                    continue;
                }
                ComicAssignedUser::create([
                    'campaign_id' => $campaign->campaign_id,
                    'user_id' => $campaign->user_id,
                    'user_name' => $campaign->user_name,
                    'user_email' => $campaign->user_email,
                    'comic' => $comicId,
                    'assigned_at' => Carbon::now(),
                    'seen_at' => null,
                    'company_id' => $campaign->company_id,
                ]);
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
