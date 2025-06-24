<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\TprmUsers;
use App\Mail\CampaignMail;
use Illuminate\Support\Str;
use App\Models\TprmActivity;
use App\Models\TprmCampaign;
use App\Models\SenderProfile;
use App\Models\CompanySettings;
use App\Models\OutlookDmiToken;
use Illuminate\Console\Command;
use App\Models\TprmCampaignLive;
use App\Models\TprmCampaignReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
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
    $companies = DB::table('company')
      ->where('approved', 1)
      ->where('service_status', 1)
      ->get();

    if ($companies->isEmpty()) {
      return;
    }
    foreach ($companies as $company) {
      
      setCompanyTimezone($company->company_id);

      $company_id = $company->company_id;

      //get timezone from company settings
      $companySettings = CompanySettings::where('company_id', $company->company_id)->first();

      if ($companySettings) {
        date_default_timezone_set($companySettings->time_zone);
        config(['app.timezone' => $companySettings->time_zone]);
      }

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

      setCompanyTimezone($company->company_id);
      
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

              if ($campaign->email_lang !== 'en' && $campaign->email_lang !== 'am') {

                $mailBody = $this->changeEmailLang($mailBody, $campaign->email_lang);
              }

              if ($campaign->email_lang == 'am') {

                $mailBody = $this->translateHtmlToAmharic($mailBody);
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
              // $this->sendMailConditionally($mailData, $campaign, $campaign->company_id);

              TprmActivity::where('campaign_live_id', $campaign->id)->update(['email_sent_at' => now()]);

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

  private function translateHtmlToAmharic(string $htmlContent): ?string
  {
    $apiKey = env('OPENAI_API_KEY');
    $endpoint = 'https://api.openai.com/v1/chat/completions';

    // Step 1: Split HTML into chunks (e.g., by <div> or <p>)
    $chunks = preg_split('/(?=<div|<p|<section|<article|<table|<ul|<ol|<h[1-6])/i', $htmlContent, -1, PREG_SPLIT_NO_EMPTY);

    $translatedChunks = [];

    foreach ($chunks as $index => $chunk) {
      $messages = [
        [
          "role" => "system",
          "content" => "You are a professional translator. Translate only the visible text in the HTML into Amharic. Do not alter the structure, tags, attributes, or inline styles."
        ],
        [
          "role" => "user",
          "content" => "Translate this HTML into Amharic, keeping the HTML unchanged:\n\n$chunk"
        ]
      ];

      try {
        $response = Http::timeout(60)
          ->retry(3, 5000)
          ->withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type'  => 'application/json',
          ])->post($endpoint, [
            'model' => 'gpt-4o',
            'messages' => $messages,
            'temperature' => 0.2,
            'max_tokens' => 2048,
          ]);

        if ($response->successful()) {
          $translatedChunk = $response->json()['choices'][0]['message']['content'] ?? '';
          $translatedChunks[] = $translatedChunk;
        } else {
          \Log::error("Chunk $index failed", ['status' => $response->status(), 'body' => $response->body()]);
          $translatedChunks[] = $chunk; // fallback to original
        }

        // Sleep to avoid hitting rate limits
        sleep(1);
      } catch (\Exception $e) {
        \Log::error("Chunk $index exception", ['error' => $e->getMessage()]);
        $translatedChunks[] = $chunk; // fallback to original
      }
    }

    // Step 3: Combine all translated chunks
    return implode('', $translatedChunks);
  }

  private function sendMailConditionally($mailData, $campaign, $company_id)
  {
    // check user email domain is outlook email
    $isOutlookEmail = checkIfOutlookDomain($campaign->user_email);
    if ($isOutlookEmail) {
      echo "Outlook email detected: " . $campaign->user_email . "\n";
      $accessToken = OutlookDmiToken::where('company_id', $company_id)->first();
      if ($accessToken) {
        echo "Access token found for company ID: " . $company_id . "\n";

        $sent = sendMailUsingDmi($accessToken->access_token, $mailData);
        if ($sent['success'] == true) {
          $activity = TprmActivity::where('campaign_live_id', $campaign->id)->update(['email_sent_at' => now()]);

          echo "Email sent to: " . $campaign->user_email . "\n";
        } else {
          echo "Email not sent to: " . $campaign->user_email . "\n";
        }
      } else {
        echo "No access token found for company ID: " . $company_id . "\n";
        if ($this->sendMail($mailData)) {

          $activity = TprmActivity::where('campaign_live_id', $campaign->id)->update(['email_sent_at' => now()]);

          echo "Email sent to: " . $campaign->user_email . "\n";
        } else {
          echo "Email not sent to: " . $campaign->user_email . "\n";
        }
      }
    } else {
      echo "Non-Outlook email detected: " . $campaign->user_email . "\n";
      if ($this->sendMail($mailData)) {

        $activity = TprmActivity::where('campaign_live_id', $campaign->id)->update(['email_sent_at' => now()]);

        echo "Email sent to: " . $campaign->user_email . "\n";
      } else {
        echo "Email not sent to: " . $campaign->user_email . "\n";
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
    $tempFile = tmpfile();
    fwrite($tempFile, $emailBody);
    $meta = stream_get_meta_data($tempFile);
    $tempFilePath = $meta['uri'];

    $response = Http::withoutVerifying()
      ->timeout(60)
      ->attach('file', file_get_contents($tempFilePath), 'email.html')
      ->post('https://translate.sparrow.host/translate_file', [
        'source' => 'en',
        'target' => $email_lang,
      ]);

    fclose($tempFile);

    if ($response->failed()) {
      echo 'Failed to fetch translation: ' . $response->body();
      return $emailBody;
    }

    $responseData = $response->json();
    $translatedUrl = $responseData['translatedFileUrl'] ?? null;

    if (!$translatedUrl) {
      echo 'No translated URL found in response.';
      return $emailBody;
    }

    $translatedUrl = str_replace('http://', 'https://', $translatedUrl);

    $translatedContent = file_get_contents($translatedUrl);


    return $translatedContent;
  }
}
