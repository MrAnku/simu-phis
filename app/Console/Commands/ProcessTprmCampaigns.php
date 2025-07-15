<?php

namespace App\Console\Commands;

use App\Models\Company;
use Carbon\Carbon;
use App\Models\TprmActivity;
use App\Models\TprmCampaign;
use App\Models\PhishingEmail;
use App\Models\SenderProfile;
use App\Models\OutlookDmiToken;
use App\Models\PhishingWebsite;
use Illuminate\Console\Command;
use App\Models\TprmCampaignLive;
use App\Models\TprmCampaignReport;

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
    $this->tsendCampaignEmails();
    $this->tupdateRunningCampaigns();
  }


  private function tsendCampaignEmails()
  {
    $companies = Company::where('approved', 1)
      ->where('service_status', 1)
      ->get();

    if ($companies->isEmpty()) {
      return;
    }

    foreach ($companies as $company) {

      setCompanyTimezone($company->company_id);

      $company_id = $company->company_id;

      $campaigns = TprmCampaignLive::where('sent', 0)
        ->where('company_id', $company_id)
        ->take(5)
        ->get();


      foreach ($campaigns as $campaign) {


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

        $activity = TprmActivity::where('campaign_live_id', $campaign->id)->update(['email_sent_at' => now()]);

        echo "Email sent to: " . $campaign->user_email . "\n";
      } else {
        echo "Email not sent to: " . $campaign->user_email . "\n";
      }

      $campaign->update(['sent' => 1]);
    }
  }

  private function prepareMailBody($website, $phishingMaterial, $campaign)
  {
    $websiteUrl =  getWebsiteUrl($website, $campaign, 'tprm');

    try {
      $mailBody = file_get_contents(env('CLOUDFRONT_URL') . $phishingMaterial->mailBodyFilePath);
    } catch (\Exception $e) {
      echo "Error fetching mail body: " . $e->getMessage() . "\n";
    }


    $mailBody = str_replace('{{website_url}}', $websiteUrl, $mailBody);
    $mailBody = str_replace('{{user_name}}', $campaign->user_name, $mailBody);
    $mailBody = str_replace('{{tracker_img}}', '<img src="' . env('APP_URL') . '/ttrackEmailView/' . $campaign->id . '" alt="" width="1" height="1" style="display:none;">', $mailBody);

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
        if (sendPhishingMail($mailData)) {

          $activity = TprmActivity::where('campaign_live_id', $campaign->id)->update(['email_sent_at' => now()]);

          echo "Email sent to: " . $campaign->user_email . "\n";
        } else {
          echo "Email not sent to: " . $campaign->user_email . "\n";
        }
      }
    } else {
      echo "Non-Outlook email detected: " . $campaign->user_email . "\n";
      if (sendPhishingMail($mailData)) {

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
}
