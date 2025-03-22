<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Users;
use App\Models\Company;
use App\Models\Campaign;
use App\Mail\CampaignMail;
use Illuminate\Support\Str;
use App\Models\CampaignLive;
use App\Models\SenderProfile;
use App\Models\CampaignReport;
use App\Models\TrainingModule;
use Illuminate\Console\Command;
use App\Models\EmailCampActivity;
use App\Models\NewLearnerPassword;
use Illuminate\Support\Facades\DB;
use App\Mail\TrainingAssignedEmail;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Email;
use App\Mail\AssignTrainingWithPassResetLink;
use App\Models\CompanySettings;
use App\Models\TrainingAssignedUser;

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
    $this->sendReminderMail();
  }

  private function processScheduledCampaigns()
  {
    $companies = DB::table('company')->get();

    foreach ($companies as $company) {

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
    $users = Users::where('group_id', $campaign->users_group)->get();

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

      // Update the status in CampaignReport
      CampaignReport::where('campaign_id', $campaignid)->update(['status' => 'running']);

      echo 'Campaign is live';
    }
  }

  private function sendCampaignLiveEmails()
  {
    $companies = DB::table('company')->get();

    foreach ($companies as $company) {
      $company_id = $company->company_id;

      $campaigns = CampaignLive::where('sent', 0)
        ->where('company_id', $company_id)
        ->take(5)
        ->get();

      foreach ($campaigns as $campaign) {

        if (!$campaign->phishing_material) {

          $all_camp = Campaign::where('campaign_id', $campaign->campaign_id)->first();

          if ($all_camp->training_assignment == 'all') {

            $trainings = json_decode($all_camp->training_module, true);
            $this->sendTraining($campaign, $trainings);
          }

          $this->sendTraining($campaign);
        }

        if ($campaign->phishing_material) {
          $phishingMaterial = DB::table('phishing_emails')->find($campaign->phishing_material);

          if ($phishingMaterial) {
            $senderProfile = SenderProfile::find($phishingMaterial->senderProfile);
            $websiteColumns = DB::table('phishing_websites')->find($phishingMaterial->website);

            if ($senderProfile && $websiteColumns) {

              $websiteUrl =  $this->generateWebsiteUrl($websiteColumns, $campaign);

              $mailBody = public_path('storage/' . $phishingMaterial->mailBodyFilePath);

              $mailBody = file_get_contents($mailBody);

              $mailBody = str_replace('{{website_url}}', $websiteUrl, $mailBody);
              $mailBody = str_replace('{{user_name}}', $campaign->user_name, $mailBody);
              $mailBody = str_replace('{{tracker_img}}', '<img src="' . env('APP_URL') . '/trackEmailView/' . $campaign->id . '" alt="" width="1" height="1" style="display:none;">', $mailBody);



              if ($campaign->email_lang !== 'en') {
                $templateBodyPath = public_path('translated_temp/translated_file.html');
                // Ensure the directory exists
                if (!File::exists(dirname($templateBodyPath))) {
                  File::makeDirectory(dirname($templateBodyPath), 0755, true);
                }
                // Put the file in the public directory
                File::put($templateBodyPath, $mailBody);
                $mailBody = $this->changeEmailLang($templateBodyPath, $campaign->email_lang);
              }

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

              if ($this->sendMail($mailData)) {

                $activity = EmailCampActivity::where('campaign_live_id', $campaign->id)->update(['email_sent_at' => now()]);

                echo "Email sent to: " . $campaign->user_email . "\n";
              } else {
                echo "Email not sent to: " . $campaign->user_email . "\n";
              }

              $campaign->update(['sent' => 1]);

              CampaignReport::where('campaign_id', $campaign->campaign_id)->increment('emails_delivered');
            } else {
              echo "sender profile or website is not associated";
            }
          }
        }
      }
    }
  }

  private function generateWebsiteUrl($websiteColumns, $campaign)
  {
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
      'usrid' => $campaign->user_id
    ];

    // Build query string and final URL
    $queryString = http_build_query($params);
    $websiteFilePath = $baseUrl . '?' . $queryString;

    return $websiteFilePath;
  }

  private function sendTraining($campaign, $trainings = null)
  {
    if ($trainings !== null) {
      foreach ($trainings as $training) {
        // Check if training is already assigned to the user
        $checkAssignedUser = DB::table('training_assigned_users')
          ->where('user_email', $campaign->user_email)
          ->where('training', $training)
          ->first();

        if ($checkAssignedUser) {
          $this->sendCredentials($campaign);
        } else {
          // Check if user login already exists
          $checkLoginExist = DB::table('user_login')
            ->where('login_username', $campaign->user_email)
            ->first();

          if ($checkLoginExist) {
            $this->assignAnotherTraining($checkLoginExist, $campaign, $training);
          } else {
            $this->assignNewTraining($campaign, $training);
          }
        }
      }

      return;
    }
    // Check if training is already assigned to the user
    $checkAssignedUser = DB::table('training_assigned_users')
      ->where('user_email', $campaign->user_email)
      ->where('training', $campaign->training_module)
      ->first();

    if ($checkAssignedUser) {
      $this->sendCredentials($campaign);
    } else {
      // Check if user login already exists
      $checkLoginExist = DB::table('user_login')
        ->where('login_username', $campaign->user_email)
        ->first();

      if ($checkLoginExist) {
        $this->assignAnotherTraining($checkLoginExist, $campaign);
      } else {
        $this->assignNewTraining($campaign);
      }
    }
  }

  private function assignNewTraining($campaign, $training = null)
  {
    DB::table('training_assigned_users')
      ->insert([
        'campaign_id' => $campaign->campaign_id,
        'user_id' => $campaign->user_id,
        'user_name' => $campaign->user_name,
        'user_email' => $campaign->user_email,
        'training' => $training ?? $campaign->training_module,
        'training_lang' => $campaign->training_lang,
        'training_type' => $campaign->training_type,
        'assigned_date' => now()->toDateString(),
        'training_due_date' => now()->addDays($campaign->days_until_due)->toDateString(),
        'company_id' => $campaign->company_id
      ]);

    echo "New training assigned successfully \n";
    $learnSiteAndLogo = $this->checkWhitelabeled($campaign->company_id);

    $token = encrypt($campaign->user_email);

    $passwordGenLink = env('APP_URL') . '/learner/create-password/' . $token;

    $mailData = [
      'user_name' => $campaign->user_name,
      'training_name' => $this->trainingModuleName($training ?? $campaign->training_module),
      'password_create_link' => $passwordGenLink,
      'company_name' => $learnSiteAndLogo['company_name'],
      'company_email' => $learnSiteAndLogo['company_email'],
      'learning_site' => $learnSiteAndLogo['learn_domain'],
      'logo' => $learnSiteAndLogo['logo']
    ];

    Mail::to($campaign->user_email)->send(new AssignTrainingWithPassResetLink($mailData));

    NewLearnerPassword::create([
      'email' => $campaign->user_email,
      'token' => $token
    ]);

    $campaign->update(['sent' => 1, 'training_assigned' => 1]);

    CampaignReport::where('campaign_id', $campaign->campaign_id)
      ->update([
        'training_assigned' => DB::raw('training_assigned + 1'),
        'emails_delivered' => DB::raw('emails_delivered + 1'),
      ]);

    echo "Training assigned and report updated \n";
  }

  private function assignAnotherTraining($userLogin, $campaign, $training = null)
  {

    // Insert into training_assigned_users table
    $current_date = now()->toDateString();
    $days_until_due = now()->addDays($campaign->days_until_due)->toDateString();
    $res2 = DB::table('training_assigned_users')
      ->insert([
        'campaign_id' => $campaign->campaign_id,
        'user_id' => $campaign->user_id,
        'user_name' => $campaign->user_name,
        'user_email' => $campaign->user_email,
        'training' => $training ?? $campaign->training_module,
        'training_lang' => $campaign->training_lang,
        'training_type' => $campaign->training_type,
        'assigned_date' => $current_date,
        'training_due_date' => $days_until_due,
        'company_id' => $campaign->company_id
      ]);

    if ($res2) {
      // echo "user created successfully";

      $learnSiteAndLogo = $this->checkWhitelabeled($campaign->company_id);

      $mailData = [
        'user_name' => $campaign->user_name,
        'training_name' => $this->trainingModuleName($training ?? $campaign->training_module),
        // 'login_email' => $userLogin->login_username,
        // 'login_pass' => $userLogin->login_password,
        'company_name' => $learnSiteAndLogo['company_name'],
        'company_email' => $learnSiteAndLogo['company_email'],
        'learning_site' => $learnSiteAndLogo['learn_domain'],
        'logo' => $learnSiteAndLogo['logo']
      ];

      $isMailSent = Mail::to($userLogin->login_username)->send(new TrainingAssignedEmail($mailData));

      if ($isMailSent) {
        // Update campaign_live table
        $campaign->update(['sent' => 1, 'training_assigned' => 1]);

        $report_updated = CampaignReport::where('campaign_id', $campaign->campaign_id)
          ->update([
            'training_assigned' => DB::raw('training_assigned + 1'),
            'emails_delivered' => DB::raw('emails_delivered + 1'),
          ]);

        if ($report_updated) {
          echo "Training assigned and report updated";
        } else {
          echo "Training assigned but report not updated";
        }
      } else {
        echo "Training not sent";
      }
    } else {
      echo "Failed to create user";
    }
  }

  private function sendCredentials($campaign)
  {
    // Fetch user credentials
    $userCredentials = DB::table('user_login')
      ->where('login_username', $campaign->user_email)
      ->first();

    $learnSiteAndLogo = $this->checkWhitelabeled($campaign->company_id);

    $mailData = [
      'user_name' => $campaign->user_name,
      'training_name' => $this->trainingModuleName($campaign->training_module),
      // 'login_email' => $userCredentials->login_username,
      // 'login_pass' => $userCredentials->login_password,
      'company_name' => $learnSiteAndLogo['company_name'],
      'company_email' => $learnSiteAndLogo['company_email'],
      'learning_site' => $learnSiteAndLogo['learn_domain'],
      'logo' => $learnSiteAndLogo['logo']
    ];

    $isMailSent = Mail::to($campaign->user_email)->send(new TrainingAssignedEmail($mailData));

    if ($isMailSent) {
      $campaign->update(['sent' => 1, 'training_assigned' => 1]);
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

  private function sendMail($mailData)
  {

    // Set mail configuration dynamically
    config([
      'mail.mailers.smtp.host' => $mailData['sendMailHost'],
      'mail.mailers.smtp.username' => $mailData['sendMailUserName'],
      'mail.mailers.smtp.password' => $mailData['sendMailPassword'],
    ]);


    try {
      Mail::to($mailData['email'])->send(new CampaignMail($mailData));
      return true;
    } catch (\Exception $e) {

      return false;
    }
  }

  // private function changeEmailLang($tempBodyFile, $email_lang)
  // {
  //   $apiKey = env('OPENAI_API_KEY');
  //   $apiEndpoint = "https://api.openai.com/v1/engines/davinci-codex/completions";

  //   $fileContent = file_get_contents($tempBodyFile);

  //   $prompt = "Translate the following email content to {$email_lang}:\n\n{$fileContent}";

  //   $requestBody = [
  //     "prompt" => $prompt,
  //     "max_tokens" => 1000,
  //     "temperature" => 0.7,
  //   ];

  //   $headers = [
  //     "Content-Type: application/json",
  //     "Authorization: Bearer {$apiKey}",
  //   ];

  //   $curl = curl_init();

  //   curl_setopt($curl, CURLOPT_URL, $apiEndpoint);
  //   curl_setopt($curl, CURLOPT_POST, true);
  //   curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($requestBody));
  //   curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  //   curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

  //   $response = curl_exec($curl);

  //   if (curl_errno($curl)) {
  //     return null;
  //   }

  //   curl_close($curl);

  //   $responseData = json_decode($response, true);

  //   $translatedMailBody = $responseData['choices'][0]['text'] ?? null;

  //   return $translatedMailBody;
  // }

  public function changeEmailLang($tempBodyFile, $email_lang)
  {
    $apiKey = env('OPENAI_API_KEY');
    $apiEndpoint = "https://api.openai.com/v1/completions";
    // $file = public_path($tempBodyFile);
    $fileContent = file_get_contents($tempBodyFile);

    // return response($fileContent, 200)->header('Content-Type', 'text/html');

    $prompt = "Translate the following email content to {$email_lang}:\n\n{$fileContent}";

    $requestBody = [
      'model' => 'gpt-3.5-turbo-instruct',
      'prompt' => $prompt,
      'max_tokens' => 1500,
      'temperature' => 0.7,
    ];

    $response = Http::withHeaders([
      'Content-Type' => 'application/json',
      'Authorization' => 'Bearer ' . $apiKey,
    ])->post($apiEndpoint, $requestBody);

    if ($response->failed()) {
      echo 'Failed to fetch translation' . json_encode($response->body());
      return $fileContent;
    }
    $responseData = $response->json();
    $translatedMailBody = $responseData['choices'][0]['text'] ?? null;

    return $translatedMailBody;
  }

  private function sendReminderMail()
  {
    $companies = Company::all();

    foreach ($companies as $company) {


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
            $this->freqTrainingReminder($assignedUser);

            // Update the last reminder date and save it
            $assignedUser->last_reminder_date = $currentDate;
            $assignedUser->save();
          }
        } else {
          if ($assignedUser->personal_best == 0) {
            $lastReminderDate = Carbon::parse($assignedUser->last_reminder_date);
            $nextReminderDate = $lastReminderDate->addDays($remindFreqDays);
            if ($nextReminderDate->isBefore($currentDate)) {

              $this->freqTrainingReminder($assignedUser);
              $assignedUser->last_reminder_date = $currentDate;
              $assignedUser->save();
            }
          }
        }
      }
    }
  }

  private function freqTrainingReminder($assignedUser){
    $learnSiteAndLogo = $this->checkWhitelabeled($assignedUser->company_id);

    $mailData = [
      'user_name' => $assignedUser->user_name,
      'training_name' => $assignedUser->trainingData->name,
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
