<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Campaign;
use App\Models\Users;
use App\Models\TrainingAssignedUser;
use App\Models\SenderProfile;
use App\Models\CampaignLive;
use App\Models\CampaignReport;
use App\Mail\CampaignMail;
use App\Models\TrainingModule;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
    $this->sendCampaignEmails();
    // $this->updateRunningCampaigns();
  }

  private function processScheduledCampaigns()
  {
    $companies = DB::table('company')->get();

    foreach ($companies as $company) {
      $company_id = $company->company_id;

      $campaigns = Campaign::where('status', 'pending')
        ->where('launch_type', 'scheduled')
        ->where('company_id', $company_id)
        ->get();

      foreach ($campaigns as $campaign) {
        $email_freq = $campaign->email_freq;
        $launchTime = Carbon::createFromFormat('m/d/Y g:i A', $campaign->launch_time);
        $expire_after = $campaign->expire_after ? Carbon::createFromFormat('Y-m-d', $campaign->expire_after) : null;
        $currentDateTime = Carbon::now();

        if ($launchTime->lessThan($currentDateTime)) {

          $this->makeCampaignLive($campaign->campaign_id);

          if ($email_freq !== 'one') {
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

            if ($expire_after !== null) {
              if($launchTime->lessThan($expire_after)){
                echo "launch time updated but not pending";
                $campaign->update(['launch_time' => $launchTime->format('m/d/Y g:i A'), 'status' => 'pending']);
              }else{
                echo "status completed by expire after condition check";
                $campaign->update(['status' => 'completed']);
              }
            } else {
              $campaign->update(['launch_time' => $launchTime->format('m/d/Y g:i A'), 'status' => 'pending']);
            }
          }
        }
      }
    }
  }

  private function makeCampaignLive($campaignid)
  {
      // Retrieve the campaign instance
      $campaign = Campaign::where('campaign_id', $campaignid)->first();
      
      // Check if the campaign exists
      if (!$campaign) {
          return response()->json(['message' => 'Campaign not found'], 404);
      }
  
      // Retrieve the users in the specified group
      $users = User::where('group_id', $campaign->users_group)->get();
  
      // Check if users exist in the group
      if ($users->isEmpty()) {
          return response()->json(['message' => 'No employees available in this group'], 400);
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

          echo 'made campaign running from makeCampaignLive Method';
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
              $websiteFilePath = $websiteColumns->domain . '/' . $websiteColumns->file;
              $websiteFilePath .= '?sessionid=' . generateRandom(32) . '&token=' . $campaign->id . '&usrid=' . $usrId;

              // $mailBody = file_get_contents($phishingMaterial->mailBodyFilePath);
              $mailBody = public_path('storage/' . $phishingMaterial->mailBodyFilePath);

              $mailBody = file_get_contents($mailBody);

              $mailBody = str_replace('{{website_url}}', $websiteFilePath, $mailBody);
              $mailBody = str_replace('{{user_name}}', $user_Name, $mailBody);
              $mailBody = str_replace('{{tracker_img}}', '<img src="' . env('APP_URL') . '/trackEmailView/' . $campaign->id . '" alt="" width="1" height="1" style="display:none;">', $mailBody);



              if ($email_lang !== 'en') {
                $templateBodyPath = 'translated_temp/translated_file.html';
                Storage::disk('public')->put($templateBodyPath, $mailBody);
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
            }
          }
        }

        if (!$phishingMaterialId) {
          $checkAssignedUser = TrainingAssignedUser::where('user_id', $usrId)
            ->where('training', $training_module)
            ->first();

          if ($checkAssignedUser) {
            $userCredentials = DB::table('user_login')->where('login_username', $usrId)->first();

            $mailBody = $this->generateAlertMailBody(
              $this->trainingModuleName($checkAssignedUser->training),
              $userCredentials->login_username,
              $userCredentials->login_password,
              env('SIMUPHISH_LEARNING_URL'),
              public_path('assets/images/simu-logo-dark.png')
            );

            $this->assignTrainingMail($checkAssignedUser->user_email, $mailBody);

            $campaign->update(['training_assigned' => 1, 'sent' => 1]);

            $this->updateCampaignReports($campaign_id, 'training_assigned');
          } else {
            $userLoginEmail = $email;
            $userLoginPass = generateRandom(16);

            $current_date = now();
            $date_after_14_days = now()->addDays(14);

            $newAssignedUser = new TrainingAssignedUser();
            $newAssignedUser->campaign_id = $campaign_id;
            $newAssignedUser->user_id = $usrId;
            $newAssignedUser->user_name = $user_Name;
            $newAssignedUser->user_email = $userLoginEmail;
            $newAssignedUser->training = $training_module;
            $newAssignedUser->training_lang = $training_lang;
            $newAssignedUser->assigned_date = $current_date;
            $newAssignedUser->training_due_date = $date_after_14_days;
            $newAssignedUser->company_id = $company_id;
            $newAssignedUser->save();

            $res3 = DB::table('user_login')->updateOrInsert(
              ['user_id' => $usrId],
              ['login_username' => $userLoginEmail, 'login_password' => $userLoginPass]
            );

            if ($res3) {
              $mailBody = $this->generateAlertMailBody($this->trainingModuleName($training_module), $userLoginEmail, $userLoginPass, env('SIMUPHISH_LEARNING_URL'), public_path('assets/images/simu-logo-dark.png'));

              $this->assignTrainingMail($userLoginEmail, $mailBody);

              $campaign->update(['training_assigned' => 1, 'sent' => 1]);

              $this->updateCampaignReports($campaign_id, ['training_assigned', 'emails_delivered']);
            }
          }
        }
      }
    }
  }

  private function trainingModuleName($moduleid)
  {
    $training = TrainingModule::find($moduleid);
    return $training->name;
  }



  private function updateRunningCampaigns()
  {
    $runningCampaigns = Campaign::where('status', 'running')->get();

    foreach ($runningCampaigns as $campaign) {
      $campaign_id = $campaign->campaign_id;

      $checkSent = CampaignLive::where('sent', 0)->where('campaign_id', $campaign_id)->count();

      if ($checkSent == 0) {
        Campaign::where('campaign_id', $campaign_id)->update(['status' => 'completed']);
        CampaignReport::where('campaign_id', $campaign_id)->update(['status' => 'completed']);

        echo 'status updated by updateRunningCampaign method';
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


    // Make the API request
    $response = Http::attach(
      'file',
      public_path($tempBodyFile),
      'translated_file.html'
    )->post($apiEndpoint, [
      'source' => 'en',
      'target' => $email_lang,
    ]);

    // Check for successful response
    if ($response->successful()) {
      $responseData = $response->json();
      $translatedMailBody = file_get_contents($responseData['translatedFileUrl']);

      return $translatedMailBody;

      // Optionally, save the translated file locally
      // $newFilePath = "translated_temps/translated_file.html";
      // Storage::disk('public')->put($newFilePath, $translatedMailBody);

      // return response()->json(['translatedMailBody' => $translatedMailBody]);
    } else {
      return false;
    }
  }

  private function assignTrainingMail($userLoginEmail, $mailBody)
  {
    try {
      Mail::html($mailBody, function ($message) use ($userLoginEmail) {
        $message->to($userLoginEmail)
          ->subject('simUphish Training');
      });
    } catch (\Exception $e) {
      // Handle the error (e.g., log it or notify an admin)
      Log::error('Failed to send email: ' . $e->getMessage());
    }
  }

  private function generateAlertMailBody($training_name, $username, $password, $website, $logo)
  {
    return '<!DOCTYPE html>
      
          <html
            lang="en"
            xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:v="urn:schemas-microsoft-com:vml"
          >
            <head>
              <title></title>
              <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
              <meta content="width=device-width, initial-scale=1.0" name="viewport" />
              <!--[if mso
                ]><xml
                  ><o:OfficeDocumentSettings
                    ><o:PixelsPerInch>96</o:PixelsPerInch
                    ><o:AllowPNG /></o:OfficeDocumentSettings></xml
              ><![endif]-->
              <!--[if !mso]><!-->
              <!--<![endif]-->
              <style>
                * {
                  box-sizing: border-box;
                }
          
                body {
                  margin: 0;
                  padding: 0;
                }
          
                a[x-apple-data-detectors] {
                  color: inherit !important;
                  text-decoration: inherit !important;
                }
          
                #MessageViewBody a {
                  color: inherit;
                  text-decoration: none;
                }
          
                p {
                  line-height: inherit;
                }
          
                .desktop_hide,
                .desktop_hide table {
                  mso-hide: all;
                  display: none;
                  max-height: 0px;
                  overflow: hidden;
                }
          
                .image_block img + div {
                  display: none;
                }
          
                @media (max-width: 520px) {
                  .desktop_hide table.icons-inner {
                    display: inline-block !important;
                  }
          
                  .icons-inner {
                    text-align: center;
                  }
          
                  .icons-inner td {
                    margin: 0 auto;
                  }
          
                  .mobile_hide {
                    display: none;
                  }
          
                  .row-content {
                    width: 100% !important;
                  }
          
                  .stack .column {
                    width: 100%;
                    display: block;
                  }
          
                  .mobile_hide {
                    min-height: 0;
                    max-height: 0;
                    max-width: 0;
                    overflow: hidden;
                    font-size: 0px;
                  }
          
                  .desktop_hide,
                  .desktop_hide table {
                    display: table !important;
                    max-height: none !important;
                  }
                }
              </style>
            </head>
            <body
              style="
                background-color: #ffffff;
                margin: 0;
                padding: 0;
                -webkit-text-size-adjust: none;
                text-size-adjust: none;
              "
            >
              <table
                border="0"
                cellpadding="0"
                cellspacing="0"
                class="nl-container"
                role="presentation"
                style="
                  mso-table-lspace: 0pt;
                  mso-table-rspace: 0pt;
                  background-color: #ffffff;
                "
                width="100%"
              >
                <tbody>
                  <tr>
                    <td>
                      <table
                        align="center"
                        border="0"
                        cellpadding="0"
                        cellspacing="0"
                        class="row row-1"
                        role="presentation"
                        style="mso-table-lspace: 0pt; mso-table-rspace: 0pt"
                        width="100%"
                      >
                        <tbody>
                          <tr>
                            <td>
                              <table
                                align="center"
                                border="0"
                                cellpadding="0"
                                cellspacing="0"
                                class="row-content stack"
                                role="presentation"
                                style="
                                  mso-table-lspace: 0pt;
                                  mso-table-rspace: 0pt;
                                  color: #000000;
                                  width: 500px;
                                  margin: 0 auto;
                                "
                                width="500"
                              >
                                <tbody>
                                  <tr>
                                    <td
                                      class="column column-1"
                                      style="
                                        mso-table-lspace: 0pt;
                                        mso-table-rspace: 0pt;
                                        font-weight: 400;
                                        text-align: left;
                                        padding-bottom: 5px;
                                        padding-top: 5px;
                                        vertical-align: top;
                                        border-top: 0px;
                                        border-right: 0px;
                                        border-bottom: 0px;
                                        border-left: 0px;
                                      "
                                      width="100%"
                                    >
                                      <table
                                        border="0"
                                        cellpadding="0"
                                        cellspacing="0"
                                        class="image_block block-1"
                                        role="presentation"
                                        style="
                                          mso-table-lspace: 0pt;
                                          mso-table-rspace: 0pt;
                                        "
                                        width="100%"
                                      >
                                        <tr>
                                          <td
                                            class="pad"
                                            style="
                                              width: 100%;
                                              padding-right: 0px;
                                              padding-left: 0px;
                                            "
                                          >
                                            <div
                                              align="center"
                                              class="alignment"
                                              style="line-height: 10px"
                                            >
                                              <div style="max-width: 250px">
                                                <img
                                                  height="auto"
                                                  src="' . $logo . '"
                                                  style="
                                                    display: block;
                                                    height: auto;
                                                    border: 0;
                                                    width: 100%;
                                                  "
                                                  width="250"
                                                />
                                              </div>
                                            </div>
                                          </td>
                                        </tr>
                                      </table>
                                      <div
                                        class="spacer_block block-2"
                                        style="
                                          height: 30px;
                                          line-height: 30px;
                                          font-size: 1px;
                                        "
                                      >
                                         
                                      </div>
                                      <table
                                        border="0"
                                        cellpadding="10"
                                        cellspacing="0"
                                        class="heading_block block-3"
                                        role="presentation"
                                        style="
                                          mso-table-lspace: 0pt;
                                          mso-table-rspace: 0pt;
                                        "
                                        width="100%"
                                      >
                                        <tr>
                                          <td class="pad">
                                            <h1
                                              style="
                                                margin: 0;
                                                color: #1e0e4b;
                                                direction: ltr;
                                                font-family: Arial, "Helvetica Neue",
                                                  Helvetica, sans-serif;
                                                font-size: 27px;
                                                font-weight: 700;
                                                letter-spacing: normal;
                                                line-height: 120%;
                                                text-align: center;
                                                margin-top: 0;
                                                margin-bottom: 0;
                                                mso-line-height-alt: 32.4px;
                                              "
                                            >
                                              <span class="tinyMce-placeholder"
                                                >Hey there, You were in attack</span
                                              >
                                            </h1>
                                          </td>
                                        </tr>
                                      </table>
                                      <table
                                        border="0"
                                        cellpadding="10"
                                        cellspacing="0"
                                        class="paragraph_block block-4"
                                        role="presentation"
                                        style="
                                          mso-table-lspace: 0pt;
                                          mso-table-rspace: 0pt;
                                          word-break: break-word;
                                        "
                                        width="100%"
                                      >
                                        <tr>
                                          <td class="pad">
                                            <div
                                              style="
                                                color: #444a5b;
                                                direction: ltr;
                                                font-family: Arial, "Helvetica Neue",
                                                  Helvetica, sans-serif;
                                                font-size: 15px;
                                                font-weight: 400;
                                                letter-spacing: 0px;
                                                line-height: 120%;
                                                text-align: left;
                                                mso-line-height-alt: 18px;
                                              "
                                            >
                                              <p style="margin: 0">
                                                Training
                                                <em><strong>' . $training_name . ' </strong></em>is
                                                assigned to you. Kindly login to our
                                                Learning Portal to complete you training.
                                              </p>
                                            </div>
                                          </td>
                                        </tr>
                                      </table>
                                      <table
                                        border="0"
                                        cellpadding="10"
                                        cellspacing="0"
                                        class="divider_block block-5"
                                        role="presentation"
                                        style="
                                          mso-table-lspace: 0pt;
                                          mso-table-rspace: 0pt;
                                        "
                                        width="100%"
                                      >
                                        <tr>
                                          <td class="pad">
                                            <div align="center" class="alignment">
                                              <table
                                                border="0"
                                                cellpadding="0"
                                                cellspacing="0"
                                                role="presentation"
                                                style="
                                                  mso-table-lspace: 0pt;
                                                  mso-table-rspace: 0pt;
                                                "
                                                width="100%"
                                              >
                                                <tr>
                                                  <td
                                                    class="divider_inner"
                                                    style="
                                                      font-size: 1px;
                                                      line-height: 1px;
                                                      border-top: 1px solid #dddddd;
                                                    "
                                                  >
                                                    <span> </span>
                                                  </td>
                                                </tr>
                                              </table>
                                            </div>
                                          </td>
                                        </tr>
                                      </table>
                                      <table
                                        border="0"
                                        cellpadding="10"
                                        cellspacing="0"
                                        class="paragraph_block block-6"
                                        role="presentation"
                                        style="
                                          mso-table-lspace: 0pt;
                                          mso-table-rspace: 0pt;
                                          word-break: break-word;
                                        "
                                        width="100%"
                                      >
                                        <tr>
                                          <td class="pad">
                                            <div
                                              style="
                                                color: #444a5b;
                                                direction: ltr;
                                                font-family: Arial, "Helvetica Neue",
                                                  Helvetica, sans-serif;
                                                font-size: 15px;
                                                font-weight: 400;
                                                letter-spacing: 0px;
                                                line-height: 120%;
                                                text-align: left;
                                                mso-line-height-alt: 18px;
                                              "
                                            >
                                              <p style="margin: 0">
                                                Username:
                                                <strong>' . $username . '</strong>
                                              </p>
                                            </div>
                                          </td>
                                        </tr>
                                      </table>
                                      <table
                                        border="0"
                                        cellpadding="10"
                                        cellspacing="0"
                                        class="paragraph_block block-7"
                                        role="presentation"
                                        style="
                                          mso-table-lspace: 0pt;
                                          mso-table-rspace: 0pt;
                                          word-break: break-word;
                                        "
                                        width="100%"
                                      >
                                        <tr>
                                          <td class="pad">
                                            <div
                                              style="
                                                color: #444a5b;
                                                direction: ltr;
                                                font-family: Arial, "Helvetica Neue",
                                                  Helvetica, sans-serif;
                                                font-size: 15px;
                                                font-weight: 400;
                                                letter-spacing: 0px;
                                                line-height: 120%;
                                                text-align: left;
                                                mso-line-height-alt: 18px;
                                              "
                                            >
                                              <p style="margin: 0">
                                                Password: <strong>' . $password . '</strong>
                                              </p>
                                            </div>
                                          </td>
                                        </tr>
                                      </table>
                                      <table
                                        border="0"
                                        cellpadding="10"
                                        cellspacing="0"
                                        class="divider_block block-8"
                                        role="presentation"
                                        style="
                                          mso-table-lspace: 0pt;
                                          mso-table-rspace: 0pt;
                                        "
                                        width="100%"
                                      >
                                        <tr>
                                          <td class="pad">
                                            <div align="center" class="alignment">
                                              <table
                                                border="0"
                                                cellpadding="0"
                                                cellspacing="0"
                                                role="presentation"
                                                style="
                                                  mso-table-lspace: 0pt;
                                                  mso-table-rspace: 0pt;
                                                "
                                                width="100%"
                                              >
                                                <tr>
                                                  <td
                                                    class="divider_inner"
                                                    style="
                                                      font-size: 1px;
                                                      line-height: 1px;
                                                      border-top: 1px solid #dddddd;
                                                    "
                                                  >
                                                    <span> </span>
                                                  </td>
                                                </tr>
                                              </table>
                                            </div>
                                          </td>
                                        </tr>
                                      </table>
                                      <table
                                        border="0"
                                        cellpadding="10"
                                        cellspacing="0"
                                        class="button_block block-9"
                                        role="presentation"
                                        style="
                                          mso-table-lspace: 0pt;
                                          mso-table-rspace: 0pt;
                                        "
                                        width="100%"
                                      >
                                        <tr>
                                          <td class="pad">
                                            <div align="center" class="alignment">
                                              <!--[if mso]>
          <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="google.com" style="height:42px;width:135px;v-text-anchor:middle;" arcsize="10%" stroke="false" fillcolor="#7747ff">
          <w:anchorlock/>
          <v:textbox inset="0px,0px,0px,0px">
          <center style="color:#ffffff; font-family:Arial, sans-serif; font-size:16px">
          <!
                                              [endif]--><a
                                                href="' . $website . '"
                                                style="
                                                  text-decoration: none;
                                                  display: inline-block;
                                                  color: #ffffff;
                                                  background-color: #7747ff;
                                                  border-radius: 4px;
                                                  width: auto;
                                                  border-top: 0px solid transparent;
                                                  font-weight: 400;
                                                  border-right: 0px solid transparent;
                                                  border-bottom: 0px solid transparent;
                                                  border-left: 0px solid transparent;
                                                  padding-top: 5px;
                                                  padding-bottom: 5px;
                                                  font-family: Arial, "Helvetica Neue",
                                                    Helvetica, sans-serif;
                                                  font-size: 16px;
                                                  text-align: center;
                                                  mso-border-alt: none;
                                                  word-break: keep-all;
                                                "
                                                target="_blank"
                                                ><span
                                                  style="
                                                    padding-left: 20px;
                                                    padding-right: 20px;
                                                    font-size: 16px;
                                                    display: inline-block;
                                                    letter-spacing: normal;
                                                  "
                                                  ><span
                                                    style="
                                                      word-break: break-word;
                                                      line-height: 32px;
                                                    "
                                                    >Start Training Now</span
                                                  ></span
                                                ></a
                                              >><!--[if mso]></center></v:textbox></v:roundrect><![endif]-->
                                            </div>
                                          </td>
                                        </tr>
                                      </table>
                                    </td>
                                  </tr>
                                </tbody>
                              </table>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                      <table
                        align="center"
                        border="0"
                        cellpadding="0"
                        cellspacing="0"
                        class="row row-2"
                        role="presentation"
                        style="
                          mso-table-lspace: 0pt;
                          mso-table-rspace: 0pt;
                          background-color: #ffffff;
                        "
                        width="100%"
                      >
                        <tbody>
                          <tr>
                            <td>
                              <table
                                align="center"
                                border="0"
                                cellpadding="0"
                                cellspacing="0"
                                class="row-content stack"
                                role="presentation"
                                style="
                                  mso-table-lspace: 0pt;
                                  mso-table-rspace: 0pt;
                                  color: #000000;
                                  background-color: #ffffff;
                                  width: 500px;
                                  margin: 0 auto;
                                "
                                width="500"
                              >
                                <tbody>
                                  <tr>
                                    <td
                                      class="column column-1"
                                      style="
                                        mso-table-lspace: 0pt;
                                        mso-table-rspace: 0pt;
                                        font-weight: 400;
                                        text-align: left;
                                        padding-bottom: 5px;
                                        padding-top: 5px;
                                        vertical-align: top;
                                        border-top: 0px;
                                        border-right: 0px;
                                        border-bottom: 0px;
                                        border-left: 0px;
                                      "
                                      width="100%"
                                    >
                                      <table
                                        border="0"
                                        cellpadding="0"
                                        cellspacing="0"
                                        class="icons_block block-1"
                                        role="presentation"
                                        style="
                                          mso-table-lspace: 0pt;
                                          mso-table-rspace: 0pt;
                                          text-align: center;
                                        "
                                        width="100%"
                                      >
                                        <tr>
                                          <td
                                            class="pad"
                                            style="
                                              vertical-align: middle;
                                              color: #1e0e4b;
                                              font-family: "Inter", sans-serif;
                                              font-size: 15px;
                                              padding-bottom: 5px;
                                              padding-top: 5px;
                                              text-align: center;
                                            "
                                          >
                                            <table
                                              cellpadding="0"
                                              cellspacing="0"
                                              role="presentation"
                                              style="
                                                mso-table-lspace: 0pt;
                                                mso-table-rspace: 0pt;
                                              "
                                              width="100%"
                                            >
                                              <tr>
                                                <td
                                                  class="alignment"
                                                  style="
                                                    vertical-align: middle;
                                                    text-align: center;
                                                  "
                                                >
                                                  <!--[if vml]><table align="center" cellpadding="0" cellspacing="0" role="presentation" style="display:inline-block;padding-left:0px;padding-right:0px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;"><![endif]-->
                                                  <!--[if !vml]><!-->
                                                  <table
                                                    cellpadding="0"
                                                    cellspacing="0"
                                                    class="icons-inner"
                                                    role="presentation"
                                                    style="
                                                      mso-table-lspace: 0pt;
                                                      mso-table-rspace: 0pt;
                                                      display: inline-block;
                                                      margin-right: -4px;
                                                      padding-left: 0px;
                                                      padding-right: 0px;
                                                    "
                                                  >
                                                    <!--<![endif]-->
                                                  </table>
                                                </td>
                                              </tr>
                                            </table>
                                          </td>
                                        </tr>
                                      </table>
                                    </td>
                                  </tr>
                                </tbody>
                              </table>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </td>
                  </tr>
                </tbody>
              </table>
              <!-- End -->
            </body>
          </html>
          ';
  }
}
