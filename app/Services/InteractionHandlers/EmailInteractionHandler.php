<?php

namespace App\Services\InteractionHandlers;

use App\Models\Campaign;
use App\Models\CampaignLive;
use App\Models\EmailCampActivity;
use Jenssegers\Agent\Agent;

class EmailInteractionHandler
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
        if (clickedByBot($this->companyId, $this->campLiveId, 'email')) {
            return;
        }
        $campaignLive = CampaignLive::where('id', $this->campLiveId)
            ->where('payload_clicked', 0)->first();
        if ($campaignLive) {
            $campaignLive->payload_clicked = 1;
            $campaignLive->mail_open = 1;
            $campaignLive->save();

            EmailCampActivity::where('campaign_live_id', $this->campLiveId)->update(['payload_clicked_at' => now()]);
            log_action("Phishing email payload clicked by {$campaignLive->user_email} in email simulation.", 'company', $this->companyId);
        }
    }

    public function handleCompromisedEmail()
    {
        $campaignLive = CampaignLive::where('id', $this->campLiveId)
            ->where('emp_compromised', 0)
            ->first();
        if (!$campaignLive) {
            return;
        }

        $campaignLive->update(['emp_compromised' => 1, 'mail_open' => 1, 'payload_clicked' => 1]);

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
        EmailCampActivity::where('campaign_live_id', $this->campLiveId)
            ->update([
                'compromised_at' => now(),
                'client_details' => json_encode($clientData)
            ]);
        log_action("Phishing email marked as compromised by {$campaignLive->user_email} in email simulation.", 'company', $this->companyId);

        audit_log(
            $this->companyId,
            $campaignLive->user_email,
            null,
            'EMPLOYEE_COMPROMISED',
            "{$campaignLive->user_email} compromised in Email campaign '{$campaignLive->campaign_name}'",
            'normal'
        );

        return response()->json(['status' => 'success', 'message' => 'Email marked as compromised.']);
    }
}
