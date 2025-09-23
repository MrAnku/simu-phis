<?php

namespace App\Services\InteractionHandlers;

use Jenssegers\Agent\Agent;
use App\Models\QuishingCamp;
use App\Models\QuishingActivity;
use App\Models\QuishingLiveCamp;
use App\Services\CampaignTrainingService;

class QuishingInteractionHandler
{
    protected $campLiveId;

    public function __construct($campLiveId)
    {
        $this->campLiveId = $campLiveId;
    }

    public function updatePayloadClick($companyId)
    {
        if (clickedByBot($companyId, $this->campLiveId, 'quishing')) {
            return;
        }
        $campaignLive = QuishingLiveCamp::where('id', $this->campLiveId)
            ->where('qr_scanned', '0')->first();
        if ($campaignLive) {
            $campaignLive->qr_scanned = '1';
            $campaignLive->mail_open = '1';
            $campaignLive->save();

            QuishingActivity::where('campaign_live_id', $this->campLiveId)->update(['payload_clicked_at' => now()]);
            log_action("QR Scanned and visited to phishing link by {$campaignLive->user_email} in QR simulation.", 'company', $companyId);

            if ($this->trainingOnClick()) {
                $this->assignTraining();
            }
        }
    }
    public function trainingOnClick(): bool
    {
        $campaignLive = QuishingLiveCamp::where('id', $this->campLiveId)->first();
        if (!$campaignLive) {
            return false;
        }
        $trainingOnClick = QuishingCamp::where('campaign_id', $campaignLive->campaign_id)->value('training_on_click');
        return (bool)$trainingOnClick;
    }


    public function handleCompromisedEmail($companyId)
    {
        if (clickedByBot($companyId, $this->campLiveId, 'quishing')) {
            return;
        }
        $campaignLive = QuishingLiveCamp::where('id', $this->campLiveId)
            ->where('compromised', '0')->first();
        if ($campaignLive) {
            $campaignLive->compromised = '1';
            $campaignLive->save();

            $agent = new Agent();

            $clientData = [
                'platform' => $agent->platform(), // Extract OS
                'browser' => $agent->browser(), // Extract Browser
                'os' => $agent->platform() . ' ' . $agent->version($agent->platform()), // OS + Version
                'ip' => request()->ip(), // Client IP Address
                'source' => request()->header('User-Agent'), // Full User-Agent string
                'browserVersion' => $agent->version($agent->browser()),
                'device' => $agent->device(),
                'isMobile' => $agent->isMobile(),
                'isDesktop' => $agent->isDesktop(),

            ];

            QuishingActivity::where('campaign_live_id', $this->campLiveId)->update([
                'compromised_at' => now(),
                'client_details' => json_encode($clientData)
            ]);
            log_action("Email marked as compromised by {$campaignLive->user_email} in QR simulation.", 'company', $companyId);

            // Audit log
            audit_log(
                $companyId,
                $campaignLive->user_email,
                null,
                'EMPLOYEE_COMPROMISED',
                "{$campaignLive->user_email} compromised in Quishing campaign '{$campaignLive->campaign_name}'",
                'normal'
            );
            return response()->json(['status' => 'success', 'message' => 'Email marked as compromised.']);
        }
    }

    public function assignTraining()
    {
        $campaign = QuishingLiveCamp::where('id', $this->campLiveId)->first();

        if (!$campaign || ($campaign->training_module == null && $campaign->scorm_training == null)) {
            return response()->json(['error' => 'Invalid campaign or user']);
        }

        setCompanyTimezone($campaign->company_id);

        $allCamp = QuishingCamp::where('campaign_id', $campaign->campaign_id)->first();

        $trainingModules = $allCamp->training_module ? json_decode($allCamp->training_module, true) : [];
        $scormTrainings = $allCamp->scorm_training ? json_decode($allCamp->scorm_training, true) : [];

        if ($allCamp->training_assignment == 'all') {
            CampaignTrainingService::assignTraining($campaign, $trainingModules, false, $scormTrainings);
        } else {
            CampaignTrainingService::assignTraining($campaign);
        }

        $campaign->update(['sent' => '1', 'training_assigned' => '1']);
    }
}
