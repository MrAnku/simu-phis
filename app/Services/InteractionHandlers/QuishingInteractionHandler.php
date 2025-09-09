<?php

namespace App\Services\InteractionHandlers;

use Jenssegers\Agent\Agent;
use App\Models\CampaignLive;
use App\Models\QuishingActivity;
use App\Models\QuishingLiveCamp;
use App\Models\EmailCampActivity;

class QuishingInteractionHandler
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
        if (clickedByBot($this->companyId, $this->campLiveId, 'quishing')) {
            return;
        }
        $campaignLive = QuishingLiveCamp::where('id', $this->campLiveId)
            ->where('qr_scanned', '0')->first();
        if ($campaignLive) {
            $campaignLive->qr_scanned = '1';
            $campaignLive->mail_open = '1';
            $campaignLive->save();

            QuishingActivity::where('campaign_live_id', $this->campLiveId)->update(['payload_clicked_at' => now()]);
            log_action("QR Scanned and visited to phishing link by {$campaignLive->user_email} in QR simulation.", 'company', $this->companyId);
        }
    }

    public function handleCompromisedEmail()
    {
        if (clickedByBot($this->companyId, $this->campLiveId, 'quishing')) {
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
            log_action("Email marked as compromised by {$campaignLive->user_email} in QR simulation.", 'company', $this->companyId);

            // Audit log
            audit_log(
                $this->companyId,
                $campaignLive->user_email,
                null,
                'EMPLOYEE_COMPROMISED',
                "{$campaignLive->user_email} compromised in Quishing campaign '{$campaignLive->campaign_name}'",
                'normal'
            );
            return response()->json(['status' => 'success', 'message' => 'Email marked as compromised.']);
        }
    }
}
