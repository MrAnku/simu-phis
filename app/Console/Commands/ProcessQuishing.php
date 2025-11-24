<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\QuishingCamp;
use App\Models\QuishingActivity;
use App\Models\QuishingLiveCamp;
use App\Models\Users;
use App\Models\UsersGroup;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Services\CampaignProcessing\QuishingCampaignService;

class ProcessQuishing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-quishing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process quishing (QR code phishing) campaigns';

    /**
     * The quishing campaign service instance.
     *
     * @var QuishingCampaignService
     */
    protected $quishingService;

    /**
     * Create a new command instance.
     *
     * @param QuishingCampaignService $quishingService
     * @return void
     */
    public function __construct(QuishingCampaignService $quishingService)
    {
        parent::__construct();
        $this->quishingService = $quishingService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companies = Company::where('service_status', 1)
            ->where('approved', 1)
            ->where('role', null)
            ->get();

        if ($companies->isEmpty()) {
            return;
        }

        foreach ($companies as $company) {
            try {
                setCompanyTimezone($company->company_id);

                // Schedule pending campaigns
                $this->schedulePendingCampaigns($company->company_id);

                // Send quishing emails for running campaigns
                $this->sendCampaignLiveEmails($company);
            } catch (\Exception $e) {
                echo "Error: " . $e->getMessage() . "\n";
                continue;
            }
        }

        // Check and complete finished campaigns
        $this->checkCompletedCampaigns();
    }

    private function schedulePendingCampaigns(string $companyId): void
    {
        $campaigns = QuishingCamp::where('status', 'pending')
            ->where('company_id', $companyId)
            ->get();

        if ($campaigns) {
            foreach ($campaigns as $campaign) {
                $scheduleDate = Carbon::parse($campaign->schedule_date);
                $currentDate = Carbon::today();

                if ($scheduleDate->lte($currentDate)) {
                    $this->makeCampaignLive($campaign->campaign_id);
                    $campaign->update(['status' => 'running']);
                }
            }
        }
    }

    private function sendCampaignLiveEmails(Company $company): void
    {
        $runningCampaigns = QuishingCamp::where('company_id', $company->company_id)
            ->where('status', 'running')
            ->get();

        if ($runningCampaigns->isEmpty()) {
            return;
        }

        foreach ($runningCampaigns as $camp) {
            $campaignTimezone = $camp->time_zone ?: $company->company_settings->time_zone;

            // Set process timezone to campaign timezone
            date_default_timezone_set($campaignTimezone);
            config(['app.timezone' => $campaignTimezone]);

            $currentDateTime = Carbon::now();

            // Fetch due live campaigns for this campaign using campaign-local now
            $dueLiveCamps = QuishingLiveCamp::where('campaign_id', $camp->campaign_id)
                ->where('sent', '0')
                ->where('send_time', '<=', $currentDateTime->toDateTimeString())
                ->take(5)
                ->get();

            foreach ($dueLiveCamps as $liveCamp) {
                try {
                    // Delegate email processing to service
                    $this->quishingService->processLiveCampaign($liveCamp);
                } catch (\Exception $e) {
                    echo "Error: " . $e->getMessage() . "\n";
                    continue;
                }
            }
        }
    }

    private function checkCompletedCampaigns(): void
    {
        $campaigns = QuishingCamp::where('status', 'running')->get();
        if (!$campaigns) {
            return;
        }

        foreach ($campaigns as $campaign) {
            $campaignLive = $campaign->campLive()->where('sent', '0')->count();
            if ($campaignLive == 0) {
                $campaign->status = 'completed';
                $campaign->save();
            }
        }

        // Relaunch completed recurring quishing campaigns
        $this->quishingService->relaunchRecurringCampaigns();
    }

    private function makeCampaignLive($campaignid)
    {
        $campaign = QuishingCamp::where('campaign_id', $campaignid)->first();

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
        if ($campaign->selected_users == null) {
            $users = Users::whereIn('id', $userIds)->get();
        } else {
            $users = Users::whereIn('id', json_decode($campaign->selected_users, true))->get();
        }

        $startTime = Carbon::parse($campaign->start_time);
        $endTime = Carbon::parse($campaign->end_time);

        // Convert both to timestamps (seconds)
        $min = $startTime->timestamp;
        $max = $endTime->timestamp;


        // Check if users exist in the group
        if (!$users->isEmpty()) {
            foreach ($users as $user) {
                // Generate a random timestamp each time
                $randomTimestamp = mt_rand($min, $max);
                setCompanyTimezone($campaign->company_id);
                $timeZone = config('app.timezone');

                // Convert it back to readable datetime
                $randomSendTime = Carbon::createFromTimestamp($randomTimestamp, $timeZone);

                $camp_live = QuishingLiveCamp::create([
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->campaign_name,
                    'user_id' => $user->id,
                    'user_name' => $user->user_name,
                    'user_email' => $user->user_email,
                    'training_module' => $this->getRandomTrainingModule($campaign),
                    'scorm_training' => $this->getRandomScormTraining($campaign),
                    'days_until_due' => $campaign->days_until_due ?? null,
                    'training_lang' => $campaign->training_lang ?? null,
                    'training_type' => $campaign->training_type ?? null,
                    'send_time' => $randomSendTime,
                    'quishing_material'  => $this->getRandomQuishingMaterial($campaign),
                    'sender_profile'     => $campaign->sender_profile ?? null,
                    'quishing_lang'      => $campaign->quishing_lang ?? null,
                    'company_id'         => $campaign->company_id,
                ]);

                QuishingActivity::create([
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_live_id' => $camp_live->id,
                    'company_id' => $campaign->company_id,
                ]);

                // Audit log
                audit_log(
                    $campaign->company_id,
                    $campaign->user_email,
                    null,
                    'QUISHING_CAMPAIGN_SIMULATED',
                    "The campaign ‘{$campaign->campaign_name}’ has been sent to {$user->user_email}",
                    'normal'
                );
            }

            // Update the campaign status to 'running'
            $campaign->update(['status' => 'running']);


            echo "Campaign is live \n";
        }
    }

    private function getRandomTrainingModule($campaign)
    {
        if ($campaign->campaign_type == "quishing" || $campaign->training_module == null) {
            return null;
        }
        $trainingModules = json_decode($campaign->training_module, true);
        return $trainingModules[array_rand($trainingModules)];
    }

    private function getRandomScormTraining($campaign)
    {
        if ($campaign->campaign_type == "quishing" || $campaign->scorm_training == null) {
            return null;
        }

        $scormTrainings = json_decode($campaign->scorm_training, true);
        return $scormTrainings[array_rand($scormTrainings)];
    }

    private function getRandomQuishingMaterial($campaign)
    {
        $quishingMaterials = json_decode($campaign->quishing_material, true);

        return $quishingMaterials[array_rand($quishingMaterials)];
    }
}
