<?php

namespace App\Services\InteractionHandlers;

use App\Models\CampaignLive;
use App\Models\WaLiveCampaign;
use App\Models\QuishingActivity;
use App\Models\QuishingLiveCamp;
use App\Models\WhatsappActivity;
use App\Models\EmailCampActivity;
use Jenssegers\Agent\Agent;

class WaInteractionHandler
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

        $campaignLive = WaLiveCampaign::where('id', $this->campLiveId)
            ->where('payload_clicked', 0)->first();
        if ($campaignLive) {
            $campaignLive->payload_clicked = 1;
            $campaignLive->save();

            WhatsappActivity::where('campaign_live_id', $this->campLiveId)->update(['payload_clicked_at' => now()]);
            log_action("Visited to phishing website by {$campaignLive->user_name} in WhatsApp simulation.", 'company', $this->companyId);
        }
    }

    public function handleCompromisedMsg()
    {
        $campaignLive = WaLiveCampaign::where('id', $this->campLiveId)
            ->where('compromised', 0)
            ->first();
        if (!$campaignLive) {
            return;
        }

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
        WhatsappActivity::where('campaign_live_id', $this->campLiveId)
            ->update([
                'compromised_at' => now(),
                'client_details' => json_encode($clientData)
            ]);
        log_action("Employee compromised {$campaignLive->user_email} in whatsapp simulation.", 'company', $this->companyId);

        // Audit log
        audit_log(
            $this->companyId,
            null,
            $campaignLive->user_phone,
            'EMPLOYEE_COMPROMISED',
            "{$campaignLive->user_phone} compromised in Whatsapp campaign '{$campaignLive->campaign_name}'",
            $campaignLive->employee_type
        );
        return response()->json(['status' => 'success', 'message' => 'Message marked as compromised.']);
    }
}
