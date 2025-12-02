<?php

namespace App\Services\InteractionHandlers;

use App\Models\Company;
use App\Models\WaCampaign;
use Jenssegers\Agent\Agent;
use App\Models\WaLiveCampaign;
use App\Models\WhatsappActivity;
use App\Services\PolicyAssignedService;
use App\Services\CampaignTrainingService;
use App\Services\BlueCollarCampTrainingService;
use App\Services\CompanyReport;

class WaInteractionHandler
{
    protected $campLiveId;

    public function __construct($campLiveId)
    {
        $this->campLiveId = $campLiveId;
    }

    public function updatePayloadClick($companyId)
    {

        $campaignLive = WaLiveCampaign::where('id', $this->campLiveId)
            ->where('payload_clicked', 0)->first();
        if ($campaignLive) {
            $campaignLive->payload_clicked = 1;
            $campaignLive->save();

            $company = Company::where('company_id', $campaignLive->company_id)->first();

            $companyReport = new CompanyReport($company->company_id);
            // Notify admin when  click rate reach 50 % and 100 %
            $companyReport->notifyClickRateThreshold();

            // Set process timezone to campaign timezone so Carbon::now() returns campaign-local time
            $companyTimezone = $company->company_settings->time_zone ?: config('app.timezone');

            $camp = WaCampaign::where('campaign_id', $campaignLive->campaign_id)->first();
            $campaignTimezone = $camp->time_zone ?: $companyTimezone;

            date_default_timezone_set($campaignTimezone);
            config(['app.timezone' => $campaignTimezone]);

            WhatsappActivity::where('campaign_live_id', $this->campLiveId)->update(['payload_clicked_at' => now()]);
            log_action("Visited to phishing website by {$campaignLive->user_name} in WhatsApp simulation.", 'company', $companyId);

            if ($this->trainingOnClick()) {
                $this->assignTraining();
            }
        }
    }
    public function trainingOnClick(): bool
    {
        $campaignLive = WaLiveCampaign::where('id', $this->campLiveId)->first();
        if (!$campaignLive) {
            return false;
        }
        $trainingOnClick = WaCampaign::where('campaign_id', $campaignLive->campaign_id)->value('training_on_click');
        return (bool)$trainingOnClick;
    }

    public function compromiseOnClick(): bool
    {
        $campaignLive = WaLiveCampaign::where('id', $this->campLiveId)->first();
        if (!$campaignLive) {
            return false;
        }
        $compromiseOnClick = WaCampaign::where('campaign_id', $campaignLive->campaign_id)->value('compromise_on_click');
        if ($compromiseOnClick == 0) {
            return false;
        }
        if ($campaignLive->payload_clicked == 0) {
            $this->updatePayloadClick($campaignLive->company_id);
        }
        if ($campaignLive->compromised == 0) {
            $this->handleCompromisedMsg($campaignLive->company_id);
        }
        if ($campaignLive->training_assigned == 0 && ($campaignLive->training_module != null || $campaignLive->scorm_training != null)) {
            $this->assignTraining();
        }

        return true;
    }

    public function handleCompromisedMsg($companyId)
    {
        $campaignLive = WaLiveCampaign::where('id', $this->campLiveId)
            ->where('compromised', 0)
            ->first();
        if (!$campaignLive) {
            return;
        }

        $campaignLive->compromised = 1;
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

        // Set process timezone to campaign timezone so Carbon::now() returns campaign-local time
        $company = Company::where('company_id', $campaignLive->company_id)->first();
        $companyTimezone = $company->company_settings->time_zone ?: config('app.timezone');

        $camp = WaCampaign::where('campaign_id', $campaignLive->campaign_id)->first();
        $campaignTimezone = $camp->time_zone ?: $companyTimezone;

        date_default_timezone_set($campaignTimezone);
        config(['app.timezone' => $campaignTimezone]);

        WhatsappActivity::where('campaign_live_id', $this->campLiveId)
            ->update([
                'compromised_at' => now(),
                'client_details' => json_encode($clientData)
            ]);
        log_action("Employee compromised {$campaignLive->user_email} in whatsapp simulation.", 'company', $companyId);

        // Audit log
        audit_log(
            $companyId,
            null,
            $campaignLive->user_phone,
            'EMPLOYEE_COMPROMISED',
            "{$campaignLive->user_phone} compromised in Whatsapp campaign '{$campaignLive->campaign_name}'",
            $campaignLive->employee_type
        );
        return response()->json(['status' => 'success', 'message' => 'Message marked as compromised.']);
    }

    public function assignTraining()
    {
        $campaign = WaLiveCampaign::where('id', $this->campLiveId)->first();
        if (!$campaign) {
            return response()->json(['error' => 'Invalid campaign or user']);
        }
        if ($campaign->training_module == null && $campaign->scorm_training == null) {
            return response()->json(['error' => 'No training module nor scorm assigned']);
        }

        setCompanyTimezone($campaign->company_id);

        //checking assignment
        $all_camp = WaCampaign::where('campaign_id', $campaign->campaign_id)->first();

        $trainingModules = [];
        $scormTrainings = [];

        if ($all_camp->training_module !== null) {
            $trainingModules = json_decode($all_camp->training_module, true);
        }

        if ($all_camp->scorm_training !== null) {
            $scormTrainings = json_decode($all_camp->scorm_training, true);
        }

        if ($campaign->employee_type == 'normal') {
            if ($all_camp->training_assignment == 'all') {

                CampaignTrainingService::assignTraining($campaign, $trainingModules, false, $scormTrainings);
            } else {
                CampaignTrainingService::assignTraining($campaign);
            }
            if ($campaign->camp?->policies != null) {
                try {
                    $policyService = new PolicyAssignedService(
                        $campaign->campaign_id,
                        $campaign->user_name,
                        $campaign->user_email,
                        $campaign->company_id
                    );

                    $policyService->assignPolicies($campaign->camp?->policies);
                } catch (\Exception $e) {
                }
            }

            $campaign->update(['sent' => 1, 'training_assigned' => 1]);
        } else {
            if ($all_camp->training_assignment == 'all') {

                BlueCollarCampTrainingService::assignBlueCollarTraining($campaign, $trainingModules, $scormTrainings);
            } else {
                BlueCollarCampTrainingService::assignBlueCollarTraining($campaign);
            }

            $campaign->update(['sent' => 1, 'training_assigned' => 1]);
        }
    }
}
