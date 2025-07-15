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
use Illuminate\Support\Facades\DB;
use App\Mail\TrainingAssignedEmail;
use App\Models\PhishingEmail;
use App\Models\PhishingWebsite;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\Mail;
use App\Services\CampaignTrainingService;

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
    $this->updateRunningCampaigns();
    // $this->sendReminderMail();
  }

  private function processScheduledCampaigns()
  {
    $companies = DB::table('company')
      ->where('approved', 1)
      ->where('service_status', 1)
      ->get();

    if ($companies->isEmpty()) {
      return;
    }
    foreach ($companies as $company) {

      setCompanyTimezone($company->company_id);

      $campaigns = Campaign::where('status', 'pending')
        ->where('company_id', $company->company_id)
        ->get();

      if ($campaigns) {
        foreach ($campaigns as $campaign) {
          $launchTime = Carbon::createFromFormat('m/d/Y g:i A', $campaign->launch_time);
          $currentDateTime = Carbon::now();

          if ($launchTime->lessThan($currentDateTime)) {

            $this->makeCampaignLive($campaign->campaign_id);

            $campaign->update(['status' => 'running']);
          }
        }
      }
    }
  }

  private function makeCampaignLive($campaignid)
  {
    $campaign = Campaign::where('campaign_id', $campaignid)->first();

    $userIdsJson = UsersGroup::where('group_id', $campaign->users_group)->value('users');
    $userIds = json_decode($userIdsJson, true);
    $users = Users::whereIn('id', $userIds)->get();
    // $users = Users::where('group_id', $campaign->users_group)->get();

    // Check if users exist in the group
    if (!$users->isEmpty()) {
      foreach ($users as $user) {
        $camp_live = CampaignLive::create([
          'campaign_id' => $campaign->campaign_id,
          'campaign_name' => $campaign->campaign_name,
          'user_id' => $user->id,
          'user_name' => $user->user_name,
          'user_email' => $user->user_email,
          'training_module' => ($campaign->training_module == null) ? null : json_decode($campaign->training_module, true)[array_rand(json_decode($campaign->training_module, true))],
          'days_until_due' => $campaign->days_until_due ?? null,
          'training_lang' => $campaign->training_lang ?? null,
          'training_type' => $campaign->training_type ?? null,
          'launch_time' => $campaign->launch_time,
          'phishing_material' => $campaign->phishing_material == null ? null : json_decode($campaign->phishing_material, true)[array_rand(json_decode($campaign->phishing_material, true))],
          'sender_profile' => $campaign->sender_profile ?? null,
          'email_lang' => $campaign->email_lang ?? null,
          'sent' => '0',
          'company_id' => $campaign->company_id,
        ]);

        EmailCampActivity::create([
          'campaign_id' => $campaign->campaign_id,
          'campaign_live_id' => $camp_live->id,
          'company_id' => $campaign->company_id,
        ]);
      }

      // Update the campaign status to 'running'
      $campaign->update(['status' => 'running']);


      echo 'Campaign is live';
    }
  }

  private function sendCampaignLiveEmails()
  {
    $companies = DB::table('company')->where('approved', 1)->where('service_status', 1)->get();

    if ($companies->isEmpty()) {
      return;
    }
    foreach ($companies as $company) {
      setCompanyTimezone($company->company_id);

      $company_id = $company->company_id;

      $campaigns = CampaignLive::where('sent', 0)
        ->where('company_id', $company_id)
        ->take(5)
        ->get();

      foreach ($campaigns as $campaign) {

        if ($campaign->phishing_material == null) {
          try {
            $this->sendOnlyTraining($campaign);
          } catch (\Exception $e) {
            echo "Error sending training: " . $e->getMessage() . "\n";
          }
        }

        if ($campaign->phishing_material) {
          try {
            $this->sendPhishingEmail($campaign);
          } catch (\Exception $e) {
            echo "Error sending phishing email: " . $e->getMessage() . "\n";
          }
        }
      }
    }
  }

  private function sendOnlyTraining($campaign)
  {
    $all_camp = Campaign::where('campaign_id', $campaign->campaign_id)->first();

    if ($all_camp->training_assignment == 'all') {

      $trainings = json_decode($all_camp->training_module, true);
      // $this->assignTraining($campaign, $trainings);
      $sent = CampaignTrainingService::assignTraining($campaign, $trainings);

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
      ];

      // $this->sendMailConditionally($mailData, $campaign, $company_id);

      if (sendPhishingMail($mailData)) {

        $activity = EmailCampActivity::where('campaign_live_id', $campaign->id)->update(['email_sent_at' => now()]);

        echo "Email sent to: " . $campaign->user_email . "\n";
      } else {
        echo "Email not sent to: " . $campaign->user_email . "\n";
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


    $mailBody = str_replace('{{website_url}}', $websiteUrl, $mailBody);
    $mailBody = str_replace('{{user_name}}', $campaign->user_name, $mailBody);
    $mailBody = str_replace('{{tracker_img}}', '<img src="' . env('APP_URL') . '/trackEmailView/' . $campaign->id . '" alt="" width="1" height="1" style="display:none;">', $mailBody);

    if ($campaign->email_lang !== 'en' && $campaign->email_lang !== 'am') {

      $mailBody = changeEmailLang($mailBody, $campaign->email_lang);
    }

    if ($campaign->email_lang == 'am') {

      $mailBody = translateHtmlToAmharic($mailBody);
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

  

  private function updateRunningCampaigns()
  {
    $oneOffCampaigns = Campaign::where('status', 'running')->where('email_freq', 'one')->get();

    foreach ($oneOffCampaigns as $campaign) {

      $checkSent = CampaignLive::where('sent', 0)->where('campaign_id', $campaign->campaign_id)->count();

      if ($checkSent == 0) {
        Campaign::where('campaign_id', $campaign->campaign_id)->update(['status' => 'completed']);

        echo 'Campaign completed';
      }
    }

    $recurrCampaigns = Campaign::where('status', 'running')->where('email_freq', '!=', 'one')->get();

    if ($recurrCampaigns) {

      foreach ($recurrCampaigns as $recurrCampaign) {

        $checkSent = CampaignLive::where('sent', 0)->where('campaign_id', $recurrCampaign->campaign_id)->count();

        if ($checkSent == 0) {

          if ($recurrCampaign->expire_after !== null) {

            $launchTime = Carbon::createFromFormat("m/d/Y g:i A", $recurrCampaign->launch_time);
            $expire_after = Carbon::createFromFormat("Y-m-d", $recurrCampaign->expire_after);

            if ($launchTime->lessThan($expire_after)) {

              $email_freq = $recurrCampaign->email_freq;

              switch ($email_freq) {
                case 'weekly':
                  $launchTime->addWeek();
                  break;
                case 'monthly':
                  $launchTime->addMonth();
                  break;
                case 'quaterly':
                  $launchTime->addMonths(3);
                  break;
                default:
                  break;
              }

              $recurrCampaign->update(['launch_time' => $launchTime->format('m/d/Y g:i A'), 'status' => 'pending']);
            } else {
              $recurrCampaign->update(['status' => 'completed']);
            }
          } else {

            $launchTime = Carbon::createFromFormat("m/d/Y g:i A", $recurrCampaign->launch_time);
            $email_freq = $recurrCampaign->email_freq;

            switch ($email_freq) {
              case 'weekly':
                $launchTime->addWeek();
                break;
              case 'monthly':
                $launchTime->addMonth();
                break;
              case 'quaterly':
                $launchTime->addMonths(3);
                break;
              default:
                break;
            }

            $recurrCampaign->update(['launch_time' => $launchTime->format('m/d/Y g:i A'), 'status' => 'pending']);
          }
        }
      }
    }
  }

  

  

  private function sendReminderMail()
  {
    $companies = Company::all();

    foreach ($companies as $company) {
      setCompanyTimezone($company->company_id);


      $remindFreqDays = (int) $company->company_settings->training_assign_remind_freq_days;


      $trainingAssignedUsers = TrainingAssignedUser::where('company_id', $company->company_id)->get();

      if (!$trainingAssignedUsers) {
        continue;
      }
      $currentDate = Carbon::now();
      foreach ($trainingAssignedUsers as $assignedUser) {

        if ($assignedUser->last_reminder_date == null && $assignedUser->personal_best == 0) {

          $reminderDate = Carbon::parse($assignedUser->assigned_date)->addDays($remindFreqDays);

          if ($reminderDate->isBefore($currentDate)) {
            echo "Reminder will send";
            // $this->freqTrainingReminder($assignedUser);

            // Update the last reminder date and save it
            $assignedUser->last_reminder_date = $currentDate;
            $assignedUser->save();
          }
        } else {
          if ($assignedUser->personal_best == 0) {
            $lastReminderDate = Carbon::parse($assignedUser->last_reminder_date);
            $nextReminderDate = $lastReminderDate->addDays($remindFreqDays);
            if ($nextReminderDate->isBefore($currentDate)) {

              // $this->freqTrainingReminder($assignedUser);
              $assignedUser->last_reminder_date = $currentDate;
              $assignedUser->save();
            }
          }
        }
      }
    }
  }

  private function freqTrainingReminder($assignedUser)
  {
    $learnSiteAndLogo = checkWhitelabeled($assignedUser->company_id);

    $mailData = [
      'user_name' => $assignedUser->user_name,
      'training_name' => $assignedUser->training_type == 'games' ? $assignedUser->trainingGame->name : $assignedUser->trainingData->name,
      // 'login_email' => $userCredentials->login_username,
      // 'login_pass' => $userCredentials->login_password,
      'company_name' => $learnSiteAndLogo['company_name'],
      'company_email' => $learnSiteAndLogo['company_email'],
      'learning_site' => $learnSiteAndLogo['learn_domain'],
      'logo' => $learnSiteAndLogo['logo']
    ];

    $isMailSent = Mail::to($assignedUser->user_email)->send(new TrainingAssignedEmail($mailData));
  }
}
