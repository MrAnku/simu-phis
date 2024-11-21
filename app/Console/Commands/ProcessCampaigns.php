<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Users;
use App\Models\Company;
use App\Models\Campaign;
use App\Models\TprmUsers;
use App\Mail\CampaignMail;
use Illuminate\Support\Str;
use App\Models\CampaignLive;
use App\Models\TprmCampaign;
use App\Models\SenderProfile;
use App\Models\AiCallCampaign;
use App\Models\AiCallCampLive;
use App\Models\CampaignReport;
use App\Models\TrainingModule;
use Illuminate\Console\Command;
use App\Models\TprmCampaignLive;
use App\Models\TprmCampaignReport;
use Illuminate\Support\Facades\DB;
use App\Mail\TrainingAssignedEmail;
use Illuminate\Support\Facades\Log;
use App\Models\TrainingAssignedUser;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

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
  public function __construct()
  {
    parent::__construct();
  }

  public function handle()
  {
    $this->processScheduledCampaigns();
    $this->tprocessScheduledCampaigns();
    $this->sendCampaignEmails();
    $this->tsendCampaignEmails();
    $this->updateRunningCampaigns();
    $this->tupdateRunningCampaigns();
    $this->processAiCalls();
    $this->analyseAicallReports();
    $this->checkAllAiCallsHandled();
  }

  private function processScheduledCampaigns()
  {
    $companies = DB::table('company')->get();

    foreach ($companies as $company) {
      $company_id = $company->company_id;

      $campaigns = Campaign::where('status', 'pending')
        ->where('company_id', $company_id)
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

  private function tprocessScheduledCampaigns()
  {
    $companies = DB::table('company')->get();

    foreach ($companies as $company) {
      $company_id = $company->company_id;

      $campaigns = TprmCampaign::where('status', 'pending')
        ->where('company_id', $company_id)
        ->get();

      if ($campaigns) {
        foreach ($campaigns as $campaign) {
          $launchTime = Carbon::createFromFormat('m/d/Y g:i A', $campaign->launch_time);
          $currentDateTime = Carbon::now();

          if ($launchTime->lessThan($currentDateTime)) {

            $this->tmakeCampaignLive($campaign->campaign_id);

            $campaign->update(['status' => 'running']);
          }
        }
      }
    }
  }

  private function makeCampaignLive($campaignid)
  {
    // Retrieve the campaign instance
    $campaign = Campaign::where('campaign_id', $campaignid)->first();

    // Retrieve the users in the specified group
    $users = Users::where('group_id', $campaign->users_group)->get();

    // Check if users exist in the group
    if ($users->isEmpty()) {
      Log::error('No employees available in this group GroupID:' . $campaign->users_group);
    } else {
      // Iterate through the users and create CampaignLive entries
      foreach ($users as $user) {
        CampaignLive::create([
          'campaign_id' => $campaign->campaign_id,
          'campaign_name' => $campaign->campaign_name,
          'user_id' => $user->id,
          'user_name' => $user->user_name,
          'user_email' => $user->user_email,
          'training_module' => $campaign->training_module,
          'training_lang' => $campaign->training_lang,
          'training_type' => $campaign->training_type,
          'launch_time' => $campaign->launch_time,
          'phishing_material' => $campaign->phishing_material,
          'email_lang' => $campaign->email_lang,
          'sent' => '0',
          'company_id' => $campaign->company_id,
        ]);
      }

      // Update the campaign status to 'running'
      $campaign->update(['status' => 'running']);

      // Update the status in CampaignReport
      CampaignReport::where('campaign_id', $campaignid)->update(['status' => 'running']);

      echo 'Campaign is live';
    }
  }

  private function tmakeCampaignLive($campaignid)
  {
    // Retrieve the campaign instance
    $campaign = TprmCampaign::where('campaign_id', $campaignid)->first();

    // Retrieve the users in the specified group
    $users = TprmUsers::where('group_id', $campaign->users_group)->get();

    // Check if users exist in the group
    if ($users->isEmpty()) {
      Log::error('No employees available in this group GroupID:' . $campaign->users_group);
    } else {
      // Iterate through the users and create CampaignLive entries
      foreach ($users as $user) {
        TprmCampaignLive::create([
          'campaign_id' => $campaign->campaign_id,
          'campaign_name' => $campaign->campaign_name,
          'user_id' => $user->id,
          'user_name' => $user->user_name,
          'user_email' => $user->user_email,
          'training_module' => $campaign->training_module,
          'training_lang' => $campaign->training_lang,
          'launch_time' => $campaign->launch_time,
          'phishing_material' => $campaign->phishing_material,
          'email_lang' => $campaign->email_lang,
          'sent' => '0',
          'company_id' => $campaign->company_id,
        ]);
      }

      // Update the campaign status to 'running'
      $campaign->update(['status' => 'running']);

      // Update the status in CampaignReport
      TprmCampaignReport::where('campaign_id', $campaignid)->update(['status' => 'running']);

      echo 'Campaign is live';
    }
  }


  private function sendCampaignEmails()
  {
    $companies = DB::table('company')->get();

    foreach ($companies as $company) {
      $company_id = $company->company_id;

      $campaigns = CampaignLive::where('sent', 0)
        ->where('company_id', $company_id)
        ->take(5)
        ->get();

      foreach ($campaigns as $campaign) {
        $email = $campaign->user_email;
        $user_Name = $campaign->user_name;
        $campaign_id = $campaign->campaign_id;
        $usrId = $campaign->user_id;
        $email_lang = $campaign->email_lang;
        $phishingMaterialId = $campaign->phishing_material;
        $training_module = $campaign->training_module;
        $training_lang = $campaign->training_lang;

        if ($phishingMaterialId) {
          $phishingMaterial = DB::table('phishing_emails')->find($phishingMaterialId);

          if ($phishingMaterial) {
            $senderProfile = SenderProfile::find($phishingMaterial->senderProfile);
            $websiteColumns = DB::table('phishing_websites')->find($phishingMaterial->website);

            if ($senderProfile && $websiteColumns) {

              // Generate random parts
              $randomString1 = Str::random(6);
              $randomString2 = Str::random(10);
              $slugName = Str::slug($websiteColumns->name);

              // Construct the base URL
              $baseUrl = "https://{$randomString1}.{$websiteColumns->domain}/{$randomString2}";

              // Define query parameters
              $params = [
                'v' => 'r',
                'c' => Str::random(10),
                'p' => $websiteColumns->id,
                'l' => $slugName,
                'token' => $campaign->id,
                'usrid' => $usrId
              ];

              // Build query string and final URL
              $queryString = http_build_query($params);
              $websiteFilePath = $baseUrl . '?' . $queryString;



              // $websiteFilePath = $websiteColumns->domain . '/' . $websiteColumns->file;
              // $websiteFilePath .= '?sessionid=' . generateRandom(100) . '&token=' . $campaign->id . '&usrid=' . $usrId;

              // $mailBody = file_get_contents($phishingMaterial->mailBodyFilePath);
              $mailBody = public_path('storage/' . $phishingMaterial->mailBodyFilePath);

              $mailBody = file_get_contents($mailBody);

              $mailBody = str_replace('{{website_url}}', $websiteFilePath, $mailBody);
              $mailBody = str_replace('{{user_name}}', $user_Name, $mailBody);
              $mailBody = str_replace('{{tracker_img}}', '<img src="' . env('APP_URL') . '/trackEmailView/' . $campaign->id . '" alt="" width="1" height="1" style="display:none;">', $mailBody);



              if ($email_lang !== 'en') {
                $templateBodyPath = public_path('translated_temp/translated_file.html');
                // Ensure the directory exists
                if (!File::exists(dirname($templateBodyPath))) {
                  File::makeDirectory(dirname($templateBodyPath), 0755, true);
                }
                // Put the file in the public directory
                File::put($templateBodyPath, $mailBody);
                $mailBody = $this->changeEmailLang($templateBodyPath, $email_lang);
              }

              $mailData = [
                'email' => $email,
                'from_name' => $senderProfile->from_name,
                'email_subject' => $phishingMaterial->email_subject,
                'mailBody' => $mailBody,
                'from_email' => $senderProfile->from_email,
                'sendMailHost' => $senderProfile->host,
                'sendMailUserName' => $senderProfile->username,
                'sendMailPassword' => $senderProfile->password,
              ];

              $mailSentRes = $this->sendMail($mailData);

              $campaign->update(['sent' => 1]);

              $this->updateCampaignReports($campaign_id, 'emails_delivered');
            } else {
              echo "sender profile or website is not associated";
            }
          }
        }

        if (!$phishingMaterialId) {

          $this->sendTraining($campaign);
        }
      }
    }
  }

  private function tsendCampaignEmails()
  {
    $companies = DB::table('company')->get();
    echo "Fetched " . count($companies) . " companies.\n";

    foreach ($companies as $company) {
      $company_id = $company->company_id;
      echo "Processing company ID: " . $company_id . "\n";

      $campaigns = TprmCampaignLive::where('sent', 0)
        ->where('company_id', $company_id)
        ->take(5)
        ->get();

      echo "Found " . count($campaigns) . " campaigns to process.\n";

      foreach ($campaigns as $campaign) {
        $email = $campaign->user_email;
        $user_Name = $campaign->user_name;
        $campaign_id = $campaign->campaign_id;
        $usrId = $campaign->user_id;
        $email_lang = $campaign->email_lang;
        $phishingMaterialId = $campaign->phishing_material;
        $training_module = $campaign->training_module;
        $training_lang = $campaign->training_lang;

        echo "Processing campaign ID: " . $campaign_id . " for user: " . $user_Name . " (" . $email . ")\n";

        if ($phishingMaterialId) {
          echo "Phishing material ID: " . $phishingMaterialId . "\n";
          $phishingMaterial = DB::table('phishing_emails')->find($phishingMaterialId);

          if ($phishingMaterial) {
            echo "Found phishing material for campaign.\n";
            $senderProfile = SenderProfile::find($phishingMaterial->senderProfile);
            $websiteColumns = DB::table('phishing_websites')->find($phishingMaterial->website);

            if ($senderProfile && $websiteColumns) {
              echo "Found sender profile and website columns.\n";

              // Generate random parts
              $randomString1 = Str::random(6);
              $randomString2 = Str::random(10);
              $slugName = Str::slug($websiteColumns->name);

              // Construct the base URL
              $baseUrl = "https://{$randomString1}.{$websiteColumns->domain}/{$randomString2}";

              echo "Generated website URL: " . $baseUrl . "\n";

              // Define query parameters
              $params = [
                'v' => 'r',
                'c' => Str::random(10),
                'p' => $websiteColumns->id,
                'l' => $slugName,
                'token' => $campaign->id,
                'usrid' => $usrId,
                'tprm' => '1',
              ];

              // Build query string and final URL
              $queryString = http_build_query($params);
              $websiteFilePath = $baseUrl . '?' . $queryString;

              echo "Final URL with query string: " . $websiteFilePath . "\n";

              $mailBody = public_path('storage/' . $phishingMaterial->mailBodyFilePath);
              $mailBody = file_get_contents($mailBody);

              // Perform replacements in mail body
              $mailBody = str_replace('{{website_url}}', $websiteFilePath, $mailBody);
              $mailBody = str_replace('{{user_name}}', $user_Name, $mailBody);
              $mailBody = str_replace('{{tracker_img}}', '<img src="' . env('APP_URL') . '/ttrackEmailView/' . $campaign->id . '" alt="" width="1" height="1" style="display:none;">', $mailBody);

              if ($email_lang !== 'en') {
                $templateBodyPath = public_path('translated_temp/translated_file.html');
                // Ensure the directory exists
                if (!File::exists(dirname($templateBodyPath))) {
                  File::makeDirectory(dirname($templateBodyPath), 0755, true);
                }
                // Put the file in the public directory
                File::put($templateBodyPath, $mailBody);
                $mailBody = $this->changeEmailLang($templateBodyPath, $email_lang);
                echo "Email language translated to: " . $email_lang . "\n";
              }

              $mailData = [
                'email' => $email,
                'from_name' => $senderProfile->from_name,
                'email_subject' => $phishingMaterial->email_subject,
                'mailBody' => $mailBody,
                'from_email' => $senderProfile->from_email,
                'sendMailHost' => $senderProfile->host,
                'sendMailUserName' => $senderProfile->username,
                'sendMailPassword' => $senderProfile->password,
              ];
              // echo "Email data: " . json_encode($mailData, JSON_PRETTY_PRINT) . "\n";

              echo "Sending email to: " . $email . "\n";
              $mailSentRes = $this->sendMail($mailData);

              // Update campaign as sent
              $campaign->update(['sent' => 1]);
              echo "Campaign marked as sent.\n";

              $this->tupdateCampaignReports($campaign_id, 'emails_delivered');
            } else {
              echo "Sender profile or website columns not found.\n";
            }
          } else {
            echo "No phishing material found for the campaign.\n";
          }
        }

        if (!$phishingMaterialId) {
          echo "No phishing material ID, sending training instead.\n";
          $this->sendTraining($campaign);
        }
      }
    }
  }


  private function tupdateCampaignReports($campaign_id, $field)
  {
    $report = TprmCampaignReport::where('campaign_id', $campaign_id)->first();

    if ($report) {
      if (is_array($field)) {
        foreach ($field as $f) {
          $report->$f += 1;
        }
      } else {
        $report->$field += 1;
      }

      $report->save();
    }
  }

  private function sendTraining($campaign)
  {
    // Check if training is already assigned to the user
    $checkAssignedUser = DB::table('training_assigned_users')
      ->where('user_id', $campaign->user_id)
      ->where('training', $campaign->training_module)
      ->first();

    if ($checkAssignedUser) {
      $checkAssignedUseremail = $checkAssignedUser->user_email;

      // Fetch user credentials
      $userCredentials = DB::table('user_login')
        ->where('login_username', $checkAssignedUseremail)
        ->first();

      $checkAssignedUserLoginEmail = $userCredentials->login_username;
      $checkAssignedUserLoginPass = $userCredentials->login_password;

      $learnSiteAndLogo = $this->checkWhitelabeled($campaign->company_id);

      $mailData = [
        'user_name' => $campaign->user_name,
        'training_name' => $this->trainingModuleName($campaign->training_module),
        'login_email' => $checkAssignedUserLoginEmail,
        'login_pass' => $checkAssignedUserLoginPass,
        'company_name' => $learnSiteAndLogo['company_name'],
        'company_email' => $learnSiteAndLogo['company_email'],
        'learning_site' => $learnSiteAndLogo['learn_domain'],
        'logo' => $learnSiteAndLogo['logo']
      ];

      $isMailSent = Mail::to($checkAssignedUserLoginEmail)->send(new TrainingAssignedEmail($mailData));

      if ($isMailSent) {
        $campaign->update(['sent' => 1]);
        $this->updateCampaignReports($campaign->campaign_id, 'emails_delivered');
      }
    } else {
      // Check if user login already exists
      $checkLoginExist = DB::table('user_login')
        ->where('login_username', $campaign->user_email)
        ->first();

      if ($checkLoginExist) {
        $checkAssignedUserLoginEmail = $checkLoginExist->login_username;
        $checkAssignedUserLoginPass = $checkLoginExist->login_password;

        // Insert into training_assigned_users table
        $current_date = now()->toDateString();
        $date_after_14_days = now()->addDays(14)->toDateString();
        $res2 = DB::table('training_assigned_users')
          ->insert([
            'campaign_id' => $campaign->campaign_id,
            'user_id' => $campaign->user_id,
            'user_name' => $campaign->user_name,
            'user_email' => $campaign->user_email,
            'training' => $campaign->training_module,
            'training_lang' => $campaign->training_lang,
            'training_type' => $campaign->training_type,
            'assigned_date' => $current_date,
            'training_due_date' => $date_after_14_days,
            'company_id' => $campaign->company_id
          ]);

        if ($res2) {
          // echo "user created successfully";

          $learnSiteAndLogo = $this->checkWhitelabeled($campaign->company_id);

          $mailData = [
            'user_name' => $campaign->user_name,
            'training_name' => $this->trainingModuleName($campaign->training_module),
            'login_email' => $checkAssignedUserLoginEmail,
            'login_pass' => $checkAssignedUserLoginPass,
            'company_name' => $learnSiteAndLogo['company_name'],
            'company_email' => $learnSiteAndLogo['company_email'],
            'learning_site' => $learnSiteAndLogo['learn_domain'],
            'logo' => $learnSiteAndLogo['logo']
          ];

          $isMailSent = Mail::to($checkAssignedUserLoginEmail)->send(new TrainingAssignedEmail($mailData));

          if ($isMailSent) {
            // Update campaign_live table
            DB::table('campaign_live')
              ->where('id', $campaign->id)
              ->update(['training_assigned' => 1]);

            // Update campaign_reports table
            $reportsTrainingAssignCount = DB::table('campaign_reports')
              ->where('campaign_id', $campaign->campaign_id)
              ->first();

            if ($reportsTrainingAssignCount) {
              $training_assigned = (int)$reportsTrainingAssignCount->training_assigned + 1;

              DB::table('campaign_reports')
                ->where('campaign_id', $campaign->campaign_id)
                ->update(['training_assigned' => $training_assigned]);
            }

            $campaign->update(['sent' => 1]);
            $this->updateCampaignReports($campaign->campaign_id, 'emails_delivered');
          }
        } else {
          return response()->json(['error' => 'Failed to create user']);
        }
      } else {
        // Insert into training_assigned_users and user_login tables
        $current_date = now()->toDateString();
        $date_after_14_days = now()->addDays(14)->toDateString();

        $res2 = DB::table('training_assigned_users')
          ->insert([
            'campaign_id' => $campaign->campaign_id,
            'user_id' => $campaign->user_id,
            'user_name' => $campaign->user_name,
            'user_email' => $campaign->user_email,
            'training' => $campaign->training,
            'training_lang' => $campaign->training_lang,
            'training_type' => $campaign->training_type,
            'assigned_date' => $current_date,
            'training_due_date' => $date_after_14_days,
            'company_id' => $campaign->company_id
          ]);

        $userLoginPass = generateRandom(16);

        $res3 = DB::table('user_login')
          ->insert([
            'user_id' => $campaign->user_id,
            'login_username' => $campaign->user_email,
            'login_password' => $userLoginPass
          ]);

        if ($res2 && $res3) {
          // echo "user created successfully";

          $learnSiteAndLogo = $this->checkWhitelabeled($campaign->company_id);

          $mailData = [
            'user_name' => $campaign->user_name,
            'training_name' => $this->trainingModuleName($campaign->training_module),
            'login_email' => $campaign->user_email,
            'login_pass' => $userLoginPass,
            'company_name' => $learnSiteAndLogo['company_name'],
            'company_email' => $learnSiteAndLogo['company_email'],
            'learning_site' => $learnSiteAndLogo['learn_domain'],
            'logo' => $learnSiteAndLogo['logo']
          ];

          $isMailSent = Mail::to($campaign->user_email)->send(new TrainingAssignedEmail($mailData));

          if ($isMailSent) {
            // Update campaign_live table
            DB::table('campaign_live')
              ->where('id', $campaign->id)
              ->update(['training_assigned' => 1]);

            // Update campaign_reports table
            $reportsTrainingAssignCount = DB::table('campaign_reports')
              ->where('campaign_id', $campaign->campaign_id)
              ->first();

            if ($reportsTrainingAssignCount) {
              $training_assigned = (int)$reportsTrainingAssignCount->training_assigned + 1;

              DB::table('campaign_reports')
                ->where('campaign_id', $campaign->campaign_id)
                ->update(['training_assigned' => $training_assigned]);
            }

            $campaign->update(['sent' => 1]);
            $this->updateCampaignReports($campaign->campaign_id, 'emails_delivered');
          }
        } else {
          return response()->json(['error' => 'Failed to create user']);
        }
      }
    }
  }

  private function sendTrainingAi($campaign)
  {

    $checkAssignedUser = DB::table('training_assigned_users')
      ->where('user_id', $campaign->user_id)
      ->where('training', $campaign->training)
      ->first();

    if ($checkAssignedUser) {
      $checkAssignedUseremail = $checkAssignedUser->user_email;

      // Fetch user credentials
      $userCredentials = DB::table('user_login')
        ->where('login_username', $checkAssignedUseremail)
        ->first();

      $checkAssignedUserLoginEmail = $userCredentials->login_username;
      $checkAssignedUserLoginPass = $userCredentials->login_password;

      $learnSiteAndLogo = $this->checkWhitelabeled($campaign->company_id);

      $mailData = [
        'user_name' => $campaign->employee_name,
        'training_name' => $this->trainingModuleName($campaign->training),
        'login_email' => $checkAssignedUserLoginEmail,
        'login_pass' => $checkAssignedUserLoginPass,
        'company_name' => $learnSiteAndLogo['company_name'],
        'company_email' => $learnSiteAndLogo['company_email'],
        'learning_site' => $learnSiteAndLogo['learn_domain'],
        'logo' => $learnSiteAndLogo['logo']
      ];

      $isMailSent = Mail::to($checkAssignedUserLoginEmail)->send(new TrainingAssignedEmail($mailData));

      // if ($isMailSent) {
      // $campaign->update(['sent' => 1]);
      // $this->updateCampaignReports($campaign->campaign_id, 'emails_delivered');
      // }
    } else {
      // Check if user login already exists
      $checkLoginExist = DB::table('user_login')
        ->where('login_username', $campaign->employee_email)
        ->first();

      if ($checkLoginExist) {
        $checkAssignedUserLoginEmail = $checkLoginExist->login_username;
        $checkAssignedUserLoginPass = $checkLoginExist->login_password;

        // Insert into training_assigned_users table
        $current_date = now()->toDateString();
        $date_after_14_days = now()->addDays(14)->toDateString();
        $res2 = DB::table('training_assigned_users')
          ->insert([
            'campaign_id' => $campaign->campaign_id,
            'user_id' => $campaign->user_id,
            'user_name' => $campaign->employee_name,
            'user_email' => $campaign->employee_email,
            'training' => $campaign->training,
            'training_lang' => $campaign->training_lang,
            'training_type' => $campaign->training_type,
            'assigned_date' => $current_date,
            'training_due_date' => $date_after_14_days,
            'company_id' => $campaign->company_id
          ]);

        if ($res2) {
          // echo "user created successfully";

          $learnSiteAndLogo = $this->checkWhitelabeled($campaign->company_id);

          $mailData = [
            'user_name' => $campaign->employee_name,
            'training_name' => $this->trainingModuleName($campaign->training),
            'login_email' => $checkAssignedUserLoginEmail,
            'login_pass' => $checkAssignedUserLoginPass,
            'company_name' => $learnSiteAndLogo['company_name'],
            'company_email' => $learnSiteAndLogo['company_email'],
            'learning_site' => $learnSiteAndLogo['learn_domain'],
            'logo' => $learnSiteAndLogo['logo']
          ];

          $isMailSent = Mail::to($checkAssignedUserLoginEmail)->send(new TrainingAssignedEmail($mailData));
        } else {
          return response()->json(['error' => 'Failed to create user']);
        }
      } else {
        // Insert into training_assigned_users and user_login tables
        $current_date = now()->toDateString();
        $date_after_14_days = now()->addDays(14)->toDateString();

        $res2 = DB::table('training_assigned_users')
          ->insert([
            'campaign_id' => $campaign->campaign_id,
            'user_id' => $campaign->user_id,
            'user_name' => $campaign->employee_name,
            'user_email' => $campaign->employee_email,
            'training' => $campaign->training,
            'training_lang' => $campaign->training_lang,
            'training_type' => $campaign->training_type,
            'assigned_date' => $current_date,
            'training_due_date' => $date_after_14_days,
            'company_id' => $campaign->company_id
          ]);

        $userLoginPass = generateRandom(16);

        $res3 = DB::table('user_login')
          ->insert([
            'user_id' => $campaign->user_id,
            'login_username' => $campaign->employee_email,
            'login_password' => $userLoginPass
          ]);

        if ($res2 && $res3) {
          // echo "user created successfully";

          $learnSiteAndLogo = $this->checkWhitelabeled($campaign->company_id);

          $mailData = [
            'user_name' => $campaign->employee_name,
            'training_name' => $this->trainingModuleName($campaign->training),
            'login_email' => $campaign->employee_email,
            'login_pass' => $userLoginPass,
            'company_name' => $learnSiteAndLogo['company_name'],
            'company_email' => $learnSiteAndLogo['company_email'],
            'learning_site' => $learnSiteAndLogo['learn_domain'],
            'logo' => $learnSiteAndLogo['logo']
          ];

          $isMailSent = Mail::to($campaign->employee_email)->send(new TrainingAssignedEmail($mailData));
        } else {
          return response()->json(['error' => 'Failed to create user']);
        }
      }
    }
  }

  private function checkWhitelabeled($company_id)
  {
    $company = Company::with('partner')->where('company_id', $company_id)->first();

    $partner_id = $company->partner->partner_id;
    $company_email = $company->email;

    $isWhitelabled = DB::table('white_labelled_partner')
      ->where('partner_id', $partner_id)
      ->where('approved_by_admin', 1)
      ->first();

    if ($isWhitelabled) {
      return [
        'company_email' => $company_email,
        'learn_domain' => $isWhitelabled->learn_domain,
        'company_name' => $isWhitelabled->company_name,
        'logo' => env('APP_URL') . '/storage/uploads/whitelabeled/' . $isWhitelabled->dark_logo
      ];
    }

    return [
      'company_email' => env('MAIL_FROM_ADDRESS'),
      'learn_domain' => 'learn.simuphish.com',
      'company_name' => 'simUphish',
      'logo' => env('APP_URL') . '/assets/images/simu-logo-dark.png'
    ];
  }

  private function trainingModuleName($moduleid)
  {
    $training = TrainingModule::find($moduleid);
    return $training->name;
  }

  private function updateRunningCampaigns()
  {
    $oneOffCampaigns = Campaign::where('status', 'running')->where('email_freq', 'one')->get();

    foreach ($oneOffCampaigns as $campaign) {

      $checkSent = CampaignLive::where('sent', 0)->where('campaign_id', $campaign->campaign_id)->count();

      if ($checkSent == 0) {
        Campaign::where('campaign_id', $campaign->campaign_id)->update(['status' => 'completed']);
        CampaignReport::where('campaign_id', $campaign->campaign_id)->update(['status' => 'completed']);

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

              CampaignReport::where('campaign_id', $recurrCampaign->campaign_id)->update([
                'status' => 'pending',
                'scheduled_date' => $launchTime->format("m/d/Y g:i A")
              ]);
            } else {
              $recurrCampaign->update(['status' => 'completed']);

              CampaignReport::where('campaign_id', $recurrCampaign->campaign_id)->update([
                'status' => 'completed'
              ]);
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

            CampaignReport::where('campaign_id', $recurrCampaign->campaign_id)->update([
              'status' => 'pending',
              'scheduled_date' => $launchTime->format("m/d/Y g:i A")
            ]);
          }
        }
      }
    }
  }

  private function tupdateRunningCampaigns()
  {
    $oneOffCampaigns = TprmCampaign::where('status', 'running')->where('email_freq', 'one')->get();

    foreach ($oneOffCampaigns as $campaign) {

      $checkSent = TprmCampaignLive::where('sent', 0)->where('campaign_id', $campaign->campaign_id)->count();

      if ($checkSent == 0) {
        TprmCampaign::where('campaign_id', $campaign->campaign_id)->update(['status' => 'completed']);
        TprmCampaignReport::where('campaign_id', $campaign->campaign_id)->update(['status' => 'completed']);

        echo 'Campaign completed';
      }
    }

    $recurrCampaigns = TprmCampaign::where('status', 'running')->where('email_freq', '!=', 'one')->get();

    if ($recurrCampaigns) {

      foreach ($recurrCampaigns as $recurrCampaign) {

        $checkSent = TprmCampaignLive::where('sent', 0)->where('campaign_id', $recurrCampaign->campaign_id)->count();

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

              TprmCampaignReport::where('campaign_id', $recurrCampaign->campaign_id)->update([
                'status' => 'pending',
                'scheduled_date' => $launchTime->format("m/d/Y g:i A")
              ]);
            } else {
              $recurrCampaign->update(['status' => 'completed']);

              TprmCampaignReport::where('campaign_id', $recurrCampaign->campaign_id)->update([
                'status' => 'completed'
              ]);
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

            TprmCampaignReport::where('campaign_id', $recurrCampaign->campaign_id)->update([
              'status' => 'pending',
              'scheduled_date' => $launchTime->format("m/d/Y g:i A")
            ]);
          }
        }
      }
    }
  }

  private function sendMail($mailData)
  {

    // Set mail configuration dynamically
    config([
      'mail.mailers.smtp.host' => $mailData['sendMailHost'],
      'mail.mailers.smtp.username' => $mailData['sendMailUserName'],
      'mail.mailers.smtp.password' => $mailData['sendMailPassword'],
    ]);

    // Mail::to($mailData['email'])->send(new CampaignMail($mailData));

    // if (Mail::failures()) {
    //     return response()->Fail('Sorry! Please try again later');
    // } else {
    //     return response()->success('Great! Successfully send in your mail');
    // }
    try {
      Mail::to($mailData['email'])->send(new CampaignMail($mailData));
      return response()->json(['success' => 'Great! Successfully sent your mail'], 200);
    } catch (\Exception $e) {
      Log::error('Mail sending failed: ' . $e->getMessage());
      return response()->json(['error' => 'Sorry! Please try again later'], 500);
    }
  }


  private function updateCampaignReports($campaign_id, $field)
  {
    $report = CampaignReport::where('campaign_id', $campaign_id)->first();

    if ($report) {
      if (is_array($field)) {
        foreach ($field as $f) {
          $report->$f += 1;
        }
      } else {
        $report->$field += 1;
      }

      $report->save();
    }
  }

  private function changeEmailLang($tempBodyFile, $email_lang)
  {
    // API endpoint
    $apiEndpoint = "http://65.21.191.199/translate_file";

    // Create a CURLFile object with the public path
    $file = new \CURLFile($tempBodyFile);

    // Request body
    $requestBody = [
      "source" => "en",
      "target" => $email_lang,
      "file" => $file
    ];

    // Initialize cURL session
    $curl = curl_init();

    // Set cURL options
    curl_setopt($curl, CURLOPT_URL, $apiEndpoint);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $requestBody); // Use CURLOPT_POSTFIELDS directly
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    // Execute cURL request
    $response = curl_exec($curl);

    // Check for errors
    if (curl_errno($curl)) {
      // echo 'cURL error: ' . curl_error($curl);
      // exit;
      return null; // or handle error as needed
    }

    // Close cURL session
    curl_close($curl);

    // Decode the JSON response
    $responseData = json_decode($response, true);

    // Retrieve the translated mail body content
    $translatedMailBody = file_get_contents($responseData['translatedFileUrl']);

    return $translatedMailBody;
  }

  private function processAiCalls()
  {
    $pendingCalls = AiCallCampLive::where('status', 'pending')->get();

    $url = 'https://api.retellai.com/v2/create-phone-call';

    foreach ($pendingCalls as $pendingCall) {
      // Make the HTTP request

      $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . env('RETELL_API_KEY'),
      ])
        ->withOptions(['verify' => false])
        ->post($url, [
          'from_number' => $pendingCall->from_mobile,
          'to_number' => $pendingCall->to_mobile,
          'override_agent_id' => $pendingCall->agent_id
        ]);

      // Check for a successful response
      if ($response->successful()) {
        // Return the response data
        // print_r($response->json()) ;
        $pendingCall->call_id = $response['call_id'];
        $pendingCall->call_send_response = $response->json();
        $pendingCall->status = 'waiting';
        $pendingCall->save();
      } else {
        // Handle the error, e.g., log the error or throw an exception
        echo [
          'error' => 'Unable to fetch agents',
          'status' => $response->status(),
          'message' => $response->body()
        ];
      }
    }
  }

  private function analyseAicallReports()
  {
    // Retrieve the log JSON data
    $logData = DB::table('ai_call_all_logs')->where('locally_handled', 0)->get();

    if ($logData) {

      foreach ($logData as $singleLog) {

        $logJson = json_decode($singleLog->log_json, true);

        // Ensure call_id exists in the JSON
        if (isset($logJson['call']['call_id'])) {
          $callId = $logJson['call']['call_id'];

          // Check if call_id exists in the other table (e.g., 'other_table_name')
          $existingRow = DB::table('ai_call_camp_live')->where('call_id', $callId)->first();

          if ($existingRow) {

            if ($logJson['args']['fell_for_simulation'] == true) {

              if ($existingRow->training !== null) {

                $this->sendTrainingAi($existingRow);
              }
            }

            // If exists, update the JSON column of the found row
            $updatedJson = json_encode($logJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            DB::table('ai_call_camp_live')->where('id', $existingRow->id)->update([
              // 'call_time' => $logJson['start_timestamp'] ?? null,
              'training_assigned' => $logJson['args']['fell_for_simulation'] == true && $existingRow->training !== null ? 1 : 0,
              'status' => 'completed',
              'call_end_response' => $updatedJson
            ]);


            DB::table('ai_call_all_logs')->where('id', $singleLog->id)->update([
              'locally_handled' => 1
            ]);
          }
        }
      }
    }
  }

  private function checkAllAiCallsHandled()
  {
    $campaigns = AiCallCampaign::where('status', 'pending')->get();

    if ($campaigns->isNotEmpty()) {

      foreach ($campaigns as $campaign) {
        $liveCampaigns = AiCallCampLive::where(['campaign_id' => $campaign->campaign_id, 'status' => 'pending'])->get();

        if ($liveCampaigns->isEmpty()) {

          AiCallCampaign::where('campaign_id', $campaign->campaign_id)->update(['status' => 'completed']);
        }
      }
    }
  }
}
