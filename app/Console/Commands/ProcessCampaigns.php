<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Users;
use App\Models\Company;
use App\Models\Campaign;
use App\Models\UsersGroup;
use App\Models\CampaignLive;
use App\Models\SenderProfile;
use App\Models\OutlookDmiToken;
use Illuminate\Console\Command;
use App\Models\EmailCampActivity;
use Illuminate\Support\Facades\Log;
use App\Models\PhishingEmail;
use App\Models\PhishingWebsite;
use App\Models\TrainingAssignedUser;
use App\Services\CampaignTrainingService;
use App\Services\PolicyAssignedService;
use App\Services\TrainingAssignedService;

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
  protected $description = 'Command description';

  /**
   * Execute the console command.
   */


  public function handle()
  {
    $this->processScheduledCampaigns();
    $this->sendCampaignLiveEmails();
    $this->checkCompletedCampaigns();
    $this->sendReminderMail();
  }

  private function processScheduledCampaigns()
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

        $campaigns = Campaign::where('status', 'pending')
          ->where('company_id', $company->company_id)
          ->get();

        if ($campaigns) {
          foreach ($campaigns as $campaign) {
            $scheduleDate = Carbon::parse($campaign->schedule_date);
            $currentDateTime =  Carbon::today();

            if ($scheduleDate->lte($currentDateTime)) {

              $this->makeCampaignLive($campaign->campaign_id);

              $campaign->update(['status' => 'running']);
            }
          }
        }
      } catch (\Exception $e) {
        echo "Error while making campaign live: " . $e->getMessage();
        continue;
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
      setCompanyTimezone($company->company_id);

      $runningCampaigns = Campaign::where('company_id', $company->company_id)
        ->where('status', 'running')
        ->get();

      if ($runningCampaigns->isEmpty()) {
        continue;
      }

      foreach ($runningCampaigns as $camp) {
        $campaignTimezone = $camp->timeZone ?: $company->company_settings->time_zone;

        // Set process timezone to campaign timezone so Carbon::now() returns campaign-local time
        date_default_timezone_set($campaignTimezone);
        config(['app.timezone' => $campaignTimezone]);

        $currentDateTime = Carbon::now();

        // fetch due live campaigns for this campaign using campaign-local now
        $dueLiveCamps = CampaignLive::where('campaign_id', $camp->campaign_id)
          ->where('sent', 0)
          ->where('send_time', '<=', $currentDateTime->toDateTimeString())
          ->take(5)
          ->get();

        foreach ($dueLiveCamps as $campaign) {

          if ($campaign->phishing_material == null) {
            try {
              $this->sendOnlyTraining($campaign);
            } catch (\Exception $e) {
              echo "Error sending training: " . $e->getMessage() . "\n";
               continue;
            }
          }
          if ($campaign->phishing_material == null && $campaign->camp?->policies != null) {
            try {
              $policyService = new PolicyAssignedService(
                $campaign->campaign_id,
                $campaign->user_name,
                $campaign->user_email,
                $campaign->company_id
              );

              $policyService->assignPolicies($campaign->camp->policies);
            } catch (\Exception $e) {
              echo "Error assigning policy: " . $e->getMessage() . "\n";
               continue;
            }
          }

          if ($campaign->phishing_material) {
            try {
              $this->sendPhishingEmail($campaign);
            } catch (\Exception $e) {
              echo "Error sending phishing email: " . $e->getMessage() . "\n";
               continue;
            }
          }
        }
      }
    }
  }

  private function sendOnlyTraining($campaign)
  {
    $all_camp = Campaign::where('campaign_id', $campaign->campaign_id)->first();

    if ($all_camp->training_assignment == 'all') {

      $trainingModules = [];
      $scormTrainings = [];

      if ($all_camp->training_module !== null) {
        $trainingModules = json_decode($all_camp->training_module, true);
      }

      if ($all_camp->scorm_training !== null) {
        $scormTrainings = json_decode($all_camp->scorm_training, true);
      }

      // $this->assignTraining($campaign, $trainings);
      $sent = CampaignTrainingService::assignTraining($campaign, $trainingModules, false, $scormTrainings);

      if ($sent) {
        echo 'Training assigned successfully to ' . $campaign->user_email . "\n";
      } else {
        echo 'Failed to assign training to ' . $campaign->user_email . "\n";
      }

      $campaign->update(['sent' => 1, 'training_assigned' => 1]);
    } else {

      //incase if the assignment is random

      $sent = CampaignTrainingService::assignTraining($campaign);

      if ($sent) {
        echo 'Training assigned successfully to ' . $campaign->user_email . "\n";
      } else {
        echo 'Failed to assign training to ' . $campaign->user_email . "\n";
      }
      $campaign->update(['sent' => 1, 'training_assigned' => 1]);
    }
  }

  private function sendPhishingEmail($campaign)
  {
    if ($campaign->phishing_material) {
      $phishingMaterial = PhishingEmail::where('id', $campaign->phishing_material)
        ->where('website', '!=', 0)
        ->where('senderProfile', '!=', 0)
        ->first();

      if (!$phishingMaterial) {
        return;
      }

      if ($campaign->sender_profile !== null) {
        $senderProfile = SenderProfile::find($campaign->sender_profile);
      } else {
        // If sender_profile is not set in campaign, use the one from phishing material
        $senderProfile = SenderProfile::find($phishingMaterial->senderProfile);
      }

      $website = PhishingWebsite::find($phishingMaterial->website);

      if (!$senderProfile || !$website) {
        echo "Sender profile or website is not associated with the phishing material.\n";
        return;
      }

      $mailBody = $this->prepareMailBody(
        $website,
        $phishingMaterial,
        $campaign
      );

      $mailData = [
        'email' => $campaign->user_email,
        'from_name' => $senderProfile->from_name,
        'email_subject' => $phishingMaterial->email_subject,
        'mailBody' => $mailBody,
        'from_email' => $senderProfile->from_email,
        'sendMailHost' => $senderProfile->host,
        'sendMailUserName' => $senderProfile->username,
        'sendMailPassword' => $senderProfile->password,
        'company_id' => $campaign->company_id,
        'campaign_id' => $campaign->campaign_id,
        'campaign_type' => 'email'
      ];

      // $this->sendMailConditionally($mailData, $campaign, $company_id);

      if (sendPhishingMail($mailData)) {

        $activity = EmailCampActivity::where('campaign_live_id', $campaign->id)->update(['email_sent_at' => now()]);

        echo "Email sent to: " . $campaign->user_email . "\n";
      } else {
        echo "Email not sent to: " . $campaign->user_email . "\n";
        throw new \Exception("Failed to send email to " . $campaign->user_email);
      }

      $campaign->update(['sent' => 1]);
    }
  }

  private function prepareMailBody($website, $phishingMaterial, $campaign)
  {
    $websiteUrl =  getWebsiteUrl($website, $campaign);

    try {
      $mailBody = file_get_contents(env('CLOUDFRONT_URL') . $phishingMaterial->mailBodyFilePath);
    } catch (\Exception $e) {
      echo "Error fetching mail body: " . $e->getMessage() . "\n";
    }

    $companyName = Company::where('company_id', $campaign->company_id)->value('company_name');

    $mailBody = str_replace('{{website_url}}', $websiteUrl, $mailBody);
    $mailBody = str_replace(
      '{{tracker_img}}',
      '<img src="' . env('APP_URL') . '/trackEmailView/' . $campaign->id . '" alt="" width="1" height="1" style="display:none;">' .
        '<input type="hidden" id="campaign_id" value="' . $campaign->campaign_id . '">' .
        '<input type="hidden" id="campaign_type" value="email">',
      $mailBody
    );

    if ($campaign->email_lang !== 'en' && $campaign->email_lang !== 'am') {
      $mailBody = str_replace('{{user_name}}', '<p id="user_name"></p>', $mailBody);
      $mailBody = str_replace('{{company_name}}', '<p id="company_name"></p>', $mailBody);
      $mailBody = changeEmailLang($mailBody, $campaign->email_lang);
      $mailBody = str_replace('<p id="user_name"></p>', $campaign->user_name, $mailBody);
      $mailBody = str_replace('<p id="company_name"></p>', $companyName, $mailBody);
    } else if ($campaign->email_lang == 'am') {
      $mailBody = str_replace('{{user_name}}', '<p id="user_name"></p>', $mailBody);
      $mailBody = str_replace('{{company_name}}', '<p id="company_name"></p>', $mailBody);
      $mailBody = translateHtmlToAmharic($mailBody);
      $mailBody = str_replace('<p id="user_name"></p>', $campaign->user_name, $mailBody);
      $mailBody = str_replace('<p id="company_name"></p>', $companyName, $mailBody);
    } else {
      $mailBody = str_replace('{{user_name}}', $campaign->user_name, $mailBody);
      $mailBody = str_replace('{{company_name}}', $companyName, $mailBody);
    }

    return $mailBody;
  }


  private function sendMailConditionally($mailData, $campaign, $company_id)
  {
    $sent = false;
    // check user email domain is outlook email
    $isOutlookEmail = checkIfOutlookDomain($campaign->user_email);
    if ($isOutlookEmail) {

      echo "Outlook email detected: " . $campaign->user_email . "\n";

      $accessToken = OutlookDmiToken::where('company_id', $company_id)
        ->where('created_at', '>', now()->subMinutes(60))->first();
      if ($accessToken) {

        echo "Access token found for company ID: " . $company_id . "\n";

        $sent = sendMailUsingDmi($accessToken->access_token, $mailData);
        if ($sent['success'] == true) {

          $sent = true;
        } else {
          $sent = false;
        }
      } else {
        OutlookDmiToken::where('company_id', $company_id)->delete();
        echo "Access token expired or not found for company ID: " . $company_id . "\n";
        if (sendPhishingMail($mailData)) {
          $sent = true;
        } else {
          $sent = false;
        }
      }
    } else {
      echo "Non-Outlook email detected: " . $campaign->user_email . "\n";
      if (sendPhishingMail($mailData)) {

        $sent = true;
      } else {
        $sent = false;
      }
    }

    if ($sent) {
      echo "Email sent successfully to: " . $campaign->user_email . "\n";
    } else {
      echo "Email not sent to: " . $campaign->user_email . "\n";
    }

    $activity = EmailCampActivity::where('campaign_live_id', $campaign->id)->update(['email_sent_at' => now()]);
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

    // Relaunch completed recurring whatsapp campaigns (weekly/monthly/quarterly)
    $completedRecurring = Campaign::where('status', 'completed')
      ->whereIn('email_freq', ['weekly', 'monthly', 'quarterly'])
      ->get();

    foreach ($completedRecurring as $recurr) {
      try {
        try {
          if (!empty($recurr->launch_date)) {
            // launch_date stores only date; use start of day as last launch
            $lastLaunch = Carbon::parse($recurr->launch_date)->startOfDay();
          } else {
            Log::error("ProcessEmail: no launch_date for campaign {$recurr->campaign_id}");
            continue;
          }
        } catch (\Exception $e) {
          Log::error("ProcessEmail: failed to parse launch_date for campaign {$recurr->campaign_id} - " . $e->getMessage());
          continue;
        }

        $nextLaunch = $lastLaunch->copy();

        switch ($recurr->email_freq) {
          case 'weekly':
            $nextLaunch->addWeek();
            break;
          case 'monthly':
            $nextLaunch->addMonth();
            break;
          case 'quarterly':
            $nextLaunch->addMonths(3);
            break;
          default:
            continue 2;
        }

        // check expiry
        if ($recurr->expire_after !== null) {
          try {
            $expireAt = Carbon::parse($recurr->expire_after);
          } catch (\Exception $e) {
            Log::error("ProcessEmail: failed to parse expire_after for campaign {$recurr->campaign_id} - " . $e->getMessage());
            $recurr->update(['status' => 'completed']);
            continue;
          }

          if ($nextLaunch->greaterThanOrEqualTo($expireAt)) {
            continue;
          }
        }

        if (Carbon::now()->greaterThanOrEqualTo($nextLaunch)) {
          $recurr->update([
            'launch_date' => $nextLaunch->toDateString(),
            'status' => 'running',
          ]);

          echo "Relaunching whatsapp campaign of id {$recurr->campaign_id}\n";

          // reset live rows for this campaign
          $liveRows = CampaignLive::where('campaign_id', $recurr->campaign_id)->get();
          foreach ($liveRows as $live) {
            try {
              // Preserve the existing time-of-day for each send_time, only update the date to nextLaunch
              try {
                $currentSendTime = Carbon::parse($live->send_time);
                $newSendTime = Carbon::createFromFormat('Y-m-d H:i:s', $nextLaunch->toDateString() . ' ' . $currentSendTime->format('H:i:s'));
              } catch (\Exception $e) {
                // fallback: if parsing fails, use nextLaunch at startOfDay
                $newSendTime = $nextLaunch->copy()->startOfDay();
              }

              $live->update([
                'sent' => 0,
                'mail_open' => 0,
                'payload_clicked' => 0,
                'emp_compromised' => 0,
                'email_reported' => 0,
                'training_assigned' => 0,
                'send_time' => $newSendTime,
              ]);
            } catch (\Exception $e) {
              Log::error("ProcessEmail: failed to reset Email {$live->id} for campaign {$recurr->campaign_id} - " . $e->getMessage());
            }
          }
        }
      } catch (\Exception $e) {
        Log::error("ProcessQuishing: error while relaunching campaign {$recurr->campaign_id} - " . $e->getMessage());
        continue;
      }
    }
  }


  private function sendReminderMail()
  {
    $companies = Company::where('approved', 1)
      ->where('service_status', 1)
      ->where('role', null)
      ->get();

    if ($companies->isEmpty()) {
      return;
    }

    foreach ($companies as $company) {
      try {
        setCompanyTimezone($company->company_id);


        $remindFreqDays = (int) $company->company_settings->training_assign_remind_freq_days;


        $trainingAssignedUsers = TrainingAssignedUser::where('company_id', $company->company_id)
          ->get()
          ->unique('user_email')
          ->values();

        if ($trainingAssignedUsers->isEmpty()) {
          continue;
        }
        $currentDate = Carbon::now();
        foreach ($trainingAssignedUsers as $assignedUser) {

          if ($assignedUser->last_reminder_date == null && $assignedUser->personal_best == 0) {

            $reminderDate = Carbon::parse($assignedUser->assigned_date)->addDays($remindFreqDays);

            if ($reminderDate->isBefore($currentDate)) {
              echo "Reminder will send";
              $this->freqTrainingReminder($assignedUser);

              // Update the last reminder date and save it
              // $assignedUser->last_reminder_date = $currentDate;
              // $assignedUser->save();
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
        continue;
      }
    }
  }

  private function freqTrainingReminder($assignedUser)
  {
    try {
      $trainingAssignedService = new TrainingAssignedService();
      
      // Get the latest training record for this user to get the correct due date
      $latestTraining = TrainingAssignedUser::where('user_email', $assignedUser->user_email)
        ->where('company_id', $assignedUser->company_id)
        ->orderBy('id', 'desc')
        ->first();
      
      $mailData = [
        'user_email' => $assignedUser->user_email,
        'user_name' => $assignedUser->user_name,
        'company_id' => $assignedUser->company_id,
        'training_due_date' => $latestTraining->training_due_date ?? null,
      ];
      $trainingAssignedService->sendTrainingEmail($mailData);

      echo "Reminder email sent to: " . $assignedUser->user_email . " at " . now() . "\n";
    } catch (\Exception $e) {
      echo "Error sending reminder email: " . $e->getMessage() . "\n";
    }
  }
}
