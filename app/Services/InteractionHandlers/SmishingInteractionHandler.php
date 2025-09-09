<?php

namespace App\Services\InteractionHandlers;

use Plivo\RestClient;
use App\Models\SmishingCampaign;
use Illuminate\Support\Facades\Log;
use App\Models\SmishingLiveCampaign;
use App\Services\CampaignTrainingService;

class SmishingInteractionHandler
{
    protected $campLiveId;

    public function __construct($campLiveId)
    {
        $this->campLiveId = $campLiveId;
    }

    public function updatePayloadClick($companyId)
    {
        $campaignLive = SmishingLiveCampaign::where('id', $this->campLiveId)
            ->where('payload_clicked', 0)->first();
        if ($campaignLive) {
            $campaignLive->payload_clicked = 1;
            $campaignLive->save();

            log_action("Clicked on phishing link by {$campaignLive->user_name} in smishing simulation.", 'company', $companyId);
        }
    }

    public function handleCompromisedMsg($companyId)
    {
        $campaignLive = SmishingLiveCampaign::where('id', $this->campLiveId)
            ->where('compromised', 0)
            ->first();
        if ($campaignLive) {
            $hasTraining = $campaignLive->training_module != null ? true : false;

            SmishingLiveCampaign::where('id', $this->campLiveId)->where('compromised', 0)->update(['compromised' => 1]);

            log_action("Phishing message marked as compromised in smishing simulation.", 'company', $companyId);

            $this->sendAlertSms($campaignLive->user_phone, $hasTraining);

            return response()->json(['status' => 'success', 'message' => 'Message marked as compromised.']);
        }
    }
    public function assignTraining()
    {

        $campaign = SmishingLiveCampaign::where('id', $this->campLiveId)->first();
        if (!$campaign) {
            return response()->json(['error' => 'Invalid campaign or user']);
        }
        if ($campaign->training_module == null && $campaign->scorm_training == null) {
            return response()->json(['error' => 'No training assigned']);
        }

        setCompanyTimezone($campaign->company_id);


        //checking assignment
        $all_camp = SmishingCampaign::where('campaign_id', $campaign->campaign_id)->first();

        if ($all_camp->training_assignment == 'all') {
            $trainings = json_decode($all_camp->training_module, true);
            $sent = CampaignTrainingService::assignTraining($campaign, $trainings, true);

            // Update campaign_live table
            $campaign->update(['training_assigned' => 1]);
            $this->sendTrainingSms();
        } else {
            $sent = CampaignTrainingService::assignTraining($campaign, null, true);

            // Update campaign_live table
            $campaign->update(['training_assigned' => 1]);
            $this->sendTrainingSms();
        }
    }

    private function sendAlertSms($userPhone, $hasTraining)
    {

        try {
            $client = new RestClient(
                env('PLIVO_AUTH_ID'),
                env('PLIVO_AUTH_TOKEN')
            );
            if ($hasTraining == null) {
                $msgBody = "Oops! You were in attack! Don't worry this is just for test. This simulation is part of our ongoing efforts to improve cybersecurity awareness. Thank you for your cooperation.";
            } else {
                $msgBody = "Oops! You were in attack! This simulation is part of our ongoing efforts to improve cybersecurity awareness. Please complete the training sent to your email to enhance your awareness and security. Thank you for your cooperation.";
            }


            $response = $client->messages->create(
                [
                    "src" => env('PLIVO_MOBILE_NUMBER'),
                    "dst" => $userPhone,
                    "text"  => $msgBody
                ]
            );
           
        } catch (\Plivo\Exceptions\PlivoRestException $e) {
            // Handle the Plivo exception
            Log::error('Plivo error: ' . $e->getMessage());
        } catch (\Exception $e) {
            // Handle the exception
            Log::error('Error: ' . $e->getMessage());
        }
    }

    private function sendTrainingSms()
    {
        $campaign = SmishingLiveCampaign::where('id', $this->campLiveId)->first();
        if (!$campaign) {
            return;
        }

        if ($campaign->training_module == null && $campaign->scorm_training == null) {
            return;
        }

        setCompanyTimezone($campaign->company_id);

        try {
            $client = new RestClient(
                env('PLIVO_AUTH_ID'),
                env('PLIVO_AUTH_TOKEN')
            );

            $msgBody = "Training assigned! Please check your email for the training. This simulation is part of our ongoing efforts to improve cybersecurity awareness. Thank you for your cooperation.";

            $response = $client->messages->create(
                [
                    "src" => env('PLIVO_MOBILE_NUMBER'),
                    "dst" => $campaign->user_phone,
                    "text"  => $msgBody
                ]
            );
            return;
        } catch (\Plivo\Exceptions\PlivoRestException $e) {
            // Handle the Plivo exception
            Log::error('Plivo error: ' . $e->getMessage());
            return;
        } catch (\Exception $e) {
            // Handle the exception
            Log::error('Error: ' . $e->getMessage());
            return;
        }
    }
}
