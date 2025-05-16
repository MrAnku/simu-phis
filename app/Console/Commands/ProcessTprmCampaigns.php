<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\TprmUsers;
use App\Mail\CampaignMail;
use Illuminate\Support\Str;
use App\Models\TprmCampaign;
use App\Models\SenderProfile;
use Illuminate\Console\Command;
use App\Models\TprmCampaignLive;
use App\Models\TprmCampaignReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

class ProcessTprmCampaigns extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'app:process-tprm-campaigns';

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
    $this->tprocessScheduledCampaigns();
    $this->tsendCampaignEmails();
    $this->tupdateRunningCampaigns();
  }

  private function tprocessScheduledCampaigns()
  {
    $companies = DB::table('company')->where('approved', true)->where('service_status', true)->get();

    if (!$companies) {
      return;
    }
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

  private function tsendCampaignEmails()
  {
    $companies = DB::table('company')->where('approved', true)->where('service_status', true)->get();
    if (!$companies) {
      return;
    }
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

              if ($campaign->email_lang !== 'en') {

                $mailBody = $this->changeEmailLang($mailBody, $campaign->email_lang);
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

  public function changeEmailLang($emailBody, $email_lang)
  {
    $apiKey = env('OPENAI_API_KEY');
    $apiEndpoint = "https://api.openai.com/v1/chat/completions";
    // $fileContent = file_get_contents($tempBodyFile);

    // Optional: Trim content if it’s too long to prevent token limit issues
    // if (strlen($fileContent) > 10000) {
    //     $fileContent = substr($fileContent, 0, 10000);
    // }

    $messages = [
      [
        "role" => "system",
        "content" => "You are a professional email translator."
      ],
      [
        "role" => "user",
        "content" => "Translate the following email content to " . langName($email_lang) . " language:\n\n{$emailBody}"
      ]
    ];

    $requestBody = [
      'model' => 'gpt-3.5-turbo',
      'messages' => $messages,
      'max_tokens' => 1500,
      'temperature' => 0.7,
    ];

    $response = Http::withHeaders([
      'Content-Type' => 'application/json',
      'Authorization' => 'Bearer ' . $apiKey,
    ])->timeout(60) // Avoid curl timeout error
      ->post($apiEndpoint, $requestBody);

    if ($response->failed()) {
      echo 'Failed to fetch translation: ' . $response->body();
      return $emailBody;
    }

    $responseData = $response->json();
    $translatedMailBody = $responseData['choices'][0]['message']['content'] ?? null;

    return $translatedMailBody;
  }
}
