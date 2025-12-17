<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Users;
use App\Models\Company;
use App\Models\Campaign;
use App\Models\UsersGroup;
use App\Models\CampaignLive;
use App\Models\EmailCampActivity;
use App\Models\TrainingAssignedUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\TrainingAssignedService;
use App\Services\CampaignProcessing\EmailCampaignService;
use App\Services\TrainingSettingService;

class ProcessCampaigns extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'app:process-campaigns';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Process email campaigns, send emails, and manage campaign lifecycle';

  /**
   * Email campaign service instance
   *
   * @var EmailCampaignService
   */
  protected $emailCampaignService;

  /**
   * Create a new command instance.
   *
   * @param EmailCampaignService $emailCampaignService
   */
  public function __construct(EmailCampaignService $emailCampaignService)
  {
    parent::__construct();
    $this->emailCampaignService = $emailCampaignService;
  }

  /**
   * Execute the console command.
   */
  public function handle()
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
        setCompanyTimezone($company->company_id);

        // Schedule pending campaigns
        $this->schedulePendingCampaigns($company->company_id);

        // Send campaign emails for running campaigns
        $this->sendCampaignLiveEmails($company);

        // Send training reminder emails
        $this->sendReminderMail($company);
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
    $campaigns = Campaign::where('status', 'pending')
      ->where('company_id', $companyId)
      ->get();

    if ($campaigns) {
      foreach ($campaigns as $campaign) {
        $scheduleDate = Carbon::parse($campaign->schedule_date);
        $currentDateTime = Carbon::today();

        if ($scheduleDate->lte($currentDateTime)) {
          $this->makeCampaignLive($campaign->campaign_id);
          $campaign->update(['status' => 'running']);
        }
      }
    }
  }

  private function makeCampaignLive($campaignid)
  {
    $campaign = Campaign::where('campaign_id', $campaignid)->first();

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

    $startTime = Carbon::parse($campaign->startTime);
    $endTime = Carbon::parse($campaign->endTime);

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

        $camp_live = CampaignLive::create([
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
          'launch_time' => $campaign->launch_time,
          'phishing_material' => $this->getRandomPhishingMaterial($campaign),
          'sender_profile' => $campaign->sender_profile ?? null,
          'email_lang' => $campaign->email_lang ?? null,
          'sent' => '0',
          'company_id' => $campaign->company_id,
          'send_time' => $randomSendTime
        ]);

        EmailCampActivity::create([
          'campaign_id' => $campaign->campaign_id,
          'campaign_live_id' => $camp_live->id,
          'company_id' => $campaign->company_id,
        ]);

        // Audit log
        audit_log(
          $campaign->company_id,
          $campaign->user_email,
          null,
          'EMAIL_CAMPAIGN_SIMULATED',
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
    if ($campaign->campaign_type == "Phishing" || $campaign->training_module == null) {
      return null;
    }
    $trainingModules = json_decode($campaign->training_module, true);
    return $trainingModules[array_rand($trainingModules)];
  }

  private function getRandomScormTraining($campaign)
  {
    if ($campaign->campaign_type == "Phishing" || $campaign->scorm_training == null) {
      return null;
    }

    $scormTrainings = json_decode($campaign->scorm_training, true);
    return $scormTrainings[array_rand($scormTrainings)];
  }

  private function getRandomPhishingMaterial($campaign)
  {

    if ($campaign->campaign_type == "Training") {
      return null;
    }

    $phishingMaterials = json_decode($campaign->phishing_material, true);
    return $phishingMaterials[array_rand($phishingMaterials)];
  }

  private function sendCampaignLiveEmails(Company $company): void
  {
    $runningCampaigns = Campaign::where('company_id', $company->company_id)
      ->where('status', 'running')
      ->get();

    if ($runningCampaigns->isEmpty()) {
      return;
    }

    foreach ($runningCampaigns as $campaign) {
      $campaignTimezone = $campaign->timeZone ?: $company->company_settings->time_zone;

      // Set process timezone to campaign timezone
      date_default_timezone_set($campaignTimezone);
      config(['app.timezone' => $campaignTimezone]);

      $currentDateTime = Carbon::now();

      // Fetch due live campaigns for this campaign using campaign-local time
      $dueLiveCamps = CampaignLive::where('campaign_id', $campaign->campaign_id)
        ->where('sent', 0)
        ->where('send_time', '<=', $currentDateTime->toDateTimeString())
        ->take(5)
        ->get();

      // Process each due campaign
      foreach ($dueLiveCamps as $liveCampaign) {
        try {
          // Delegate email processing to service
          $this->emailCampaignService->processLiveCampaign($liveCampaign);
        } catch (\Exception $e) {
          echo "Error processing campaign live {$liveCampaign->id}: " . $e->getMessage() . "\n";
          Log::error("Error processing campaign live {$liveCampaign->id}: " . $e->getMessage());
          continue;
        }
      }
    }
  }

  private function checkCompletedCampaigns()
  {
    $campaigns = Campaign::where('status', 'running')
      ->get();
    if (!$campaigns) {
      return;
    }

    foreach ($campaigns as $campaign) {
      $liveCampaigns = CampaignLive::where('campaign_id', $campaign->campaign_id)
        ->where('sent', 0)
        ->count();
      if ($liveCampaigns == 0) {
        $campaign->status = 'completed';
        $campaign->save();
        echo "Campaign " . $campaign->name . " has been marked as completed.\n";
      }
    }

    // Relaunch completed recurring campaigns
    $this->emailCampaignService->relaunchRecurringCampaigns();
  }

  private function sendReminderMail(Company $company): void
  {
    try {
      $remindFreqDays = (int) $company->company_settings->training_assign_remind_freq_days;

      $trainingSetting = new TrainingSettingService;
      $isDisableOverdueTraining = $trainingSetting->checkDisableOverdueTraining($company->company_id);

      $trainingAssignedUsers = TrainingAssignedUser::where('company_id', $company->company_id)->where('completed', 0)
        ->get()
        ->unique('user_email')
        ->values();

      if ($trainingAssignedUsers->isEmpty()) {
        return;
      }

      $currentDate = Carbon::now();
      foreach ($trainingAssignedUsers as $assignedUser) {

        // if overdue setting enabled & all trainings overdue -> skip remainder logic
        if ($isDisableOverdueTraining) {

          $userTrainings = TrainingAssignedUser::where('company_id', $company->company_id)
            ->where('user_email', $assignedUser->user_email)
            ->where('completed', 0)
            ->get();

          $allOverdue = $userTrainings->every(function ($training) use ($currentDate) {
            return Carbon::parse($training->training_due_date)->isBefore($currentDate);
          });

          if ($allOverdue) {
            continue; // skip sending reminder
          }
        }

        if ($assignedUser->last_reminder_date == null && $assignedUser->personal_best == 0) {

          $reminderDate = Carbon::parse($assignedUser->assigned_date)->addDays($remindFreqDays);

          if ($reminderDate->isBefore($currentDate)) {
            echo "Reminder will send \n";
            $this->freqTrainingReminder($assignedUser);

            // Update the last reminder date and save it
            TrainingAssignedUser::where('company_id', $company->company_id)
              ->where('user_email', $assignedUser->user_email)
              ->update(['last_reminder_date' => $currentDate]);
          }
        } else {
          if ($assignedUser->personal_best == 0) {
            $lastReminderDate = Carbon::parse($assignedUser->last_reminder_date);
            $nextReminderDate = $lastReminderDate->addDays($remindFreqDays);
            if ($nextReminderDate->isBefore($currentDate)) {

              $this->freqTrainingReminder($assignedUser);
              TrainingAssignedUser::where('company_id', $company->company_id)
                ->where('user_email', $assignedUser->user_email)
                ->update(['last_reminder_date' => $currentDate]);
            }
          }
        }
      }
    } catch (\Exception $e) {
      echo "Error while sending reminder email: " . $e->getMessage() . "\n";
    }
  }

  private function freqTrainingReminder($assignedUser)
  {
    try {
      // Check if the training is already completed
      $latestTraining = TrainingAssignedUser::where('user_email', $assignedUser->user_email)
        ->where('company_id', $assignedUser->company_id)
        ->orderBy('id', 'desc')
        ->first();

      if (!$latestTraining || $latestTraining->complete == 1) {
        // Training already completed, skip sending reminder
        echo "Training already completed for: " . $assignedUser->user_email . " - Skipping reminder.\n";
        return;
      }

      $trainingAssignedService = new TrainingAssignedService();

      $mailData = [
        'user_email' => $assignedUser->user_email,
        'user_name' => $assignedUser->user_name,
        'company_id' => $assignedUser->company_id,
        'training_due_date' => $latestTraining->training_due_date ?? null,
      ];

      $trainingAssignedService->sendTrainingRemindEmail($mailData);

      echo "Reminder email sent to: " . $assignedUser->user_email . " at " . now() . "\n";
    } catch (\Exception $e) {
      echo "Error sending reminder email: " . $e->getMessage() . "\n";
    }
  }
}
