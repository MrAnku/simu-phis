<?php

namespace App\Services\InteractionHandlers;

use App\Models\CampaignLive;
use App\Models\QuishingActivity;
use App\Models\QuishingLiveCamp;
use App\Models\EmailCampActivity;
use App\Models\SmishingLiveCampaign;

class SmishingInteractionHandler
{
    protected $campLiveId;
    protected $companyId;

    public function __construct($campLiveId, $companyId)
    {
        $this->campLiveId = $campLiveId;
        $this->companyId = $companyId;
    }

    public function updatePayloadClick()
    {
        // if (clickedByBot($this->companyId, $this->campLiveId, 'quishing')) {
        //     return;
        // }
        $campaignLive = SmishingLiveCampaign::where('id', $this->campLiveId)
            ->where('payload_clicked', 0)->first();
        if ($campaignLive) {
            $campaignLive->payload_clicked = 1;
            $campaignLive->save();

            log_action("Clicked on phishing link by {$campaignLive->user_name} in smishing simulation.", 'company', $this->companyId);
        }
    }

    public function handleCompromisedMsg()
    {
        SmishingLiveCampaign::where('id', $this->campLiveId)->where('compromised', 0)->update(['compromised' => 1]);
        log_action("Phishing message marked as compromised in smishing simulation.", 'company', $this->companyId);
        return response()->json(['status' => 'success', 'message' => 'Message marked as compromised.']);
    }
}
