<?php

namespace App\Services\InteractionHandlers;

use App\Models\Campaign;
use Jenssegers\Agent\Agent;
use App\Models\CampaignLive;
use App\Models\Company;
use App\Models\EmailCampActivity;
use App\Services\PolicyAssignedService;
use App\Services\CampaignTrainingService;
use App\Services\CompanyReport;

class EmailInteractionHandler
{
    protected $campLiveId;

    public function __construct($campLiveId)
    {
        $this->campLiveId = $campLiveId;
    }

    public function updatePayloadClick($companyId)
    {
        if (clickedByBot($companyId, $this->campLiveId, 'email')) {
            return;
        }
        $campaignLive = CampaignLive::where('id', $this->campLiveId)
            ->where('payload_clicked', 0)->first();
        if ($campaignLive) {
            $campaignLive->payload_clicked = 1;
            $campaignLive->mail_open = 1;
            $campaignLive->save();

            $company = Company::where('company_id', $campaignLive->company_id)->first();

            $companyReport = new CompanyReport($company->company_id);

            // Notify admin when  click rate reach 50 % and 100 %
            $companyReport->notifyClickRateThreshold();

            // Set process timezone to campaign timezone so Carbon::now() returns campaign-local time
            $companyTimezone = $company->company_settings->time_zone ?: config('app.timezone');

            $camp = Campaign::where('campaign_id', $campaignLive->campaign_id)->first();

            $campaignTimezone = $camp->timeZone ?: $companyTimezone;

            date_default_timezone_set($campaignTimezone);
            config(['app.timezone' => $campaignTimezone]);

            EmailCampActivity::where('campaign_live_id', $this->campLiveId)->update(['payload_clicked_at' => now()]);
            log_action("Phishing email payload clicked by {$campaignLive->user_email} in email simulation.", 'company', $companyId);

            if ($this->trainingOnClick()) {
                $this->assignTraining();
            }
        }
    }

    public function trainingOnClick(): bool
    {
        $campaignLive = CampaignLive::where('id', $this->campLiveId)->first();
        if (!$campaignLive) {
            return false;
        }
        $trainingOnClick = Campaign::where('campaign_id', $campaignLive->campaign_id)->value('training_on_click');
        return (bool)$trainingOnClick;
    }

    public function compromiseOnClick(): bool
    {
        $campaignLive = CampaignLive::where('id', $this->campLiveId)->first();
        if (!$campaignLive) {
            return false;
        }
        $compromiseOnClick = Campaign::where('campaign_id', $campaignLive->campaign_id)->value('compromise_on_click');
        if ($compromiseOnClick == 0) {
            return false;
        }
        if ($campaignLive->payload_clicked == 0) {
            $this->updatePayloadClick($campaignLive->company_id);
        }
        if ($campaignLive->emp_compromised == 0) {
            $this->handleCompromisedEmail($campaignLive->company_id);
        }
        if ($campaignLive->training_assigned == 0 && ($campaignLive->training_module != null || $campaignLive->scorm_training != null)) {
            $this->assignTraining();
        }

        return true;
    }

    public function handleCompromisedEmail($companyId)
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

        // Set process timezone to campaign timezone so Carbon::now() returns campaign-local time
        $company = Company::where('company_id', $campaignLive->company_id)->first();
        $companyTimezone = $company->company_settings->time_zone ?: config('app.timezone');

        $camp = Campaign::where('campaign_id', $campaignLive->campaign_id)->first();

        $campaignTimezone = $camp->timeZone ?: $companyTimezone;

        date_default_timezone_set($campaignTimezone);
        config(['app.timezone' => $campaignTimezone]);

        EmailCampActivity::where('campaign_live_id', $this->campLiveId)
            ->update([
                'compromised_at' => now(),
                'client_details' => json_encode($clientData)
            ]);
        log_action("Phishing email marked as compromised by {$campaignLive->user_email} in email simulation.", 'company', $companyId);

        audit_log(
            $companyId,
            $campaignLive->user_email,
            null,
            'EMPLOYEE_COMPROMISED',
            "{$campaignLive->user_email} compromised in Email campaign '{$campaignLive->campaign_name}'",
            'normal'
        );

        return response()->json(['success' => true, 'message' => 'Email marked as compromised.']);
    }

    public function assignTraining()
    {
        $campaign = CampaignLive::where('id', $this->campLiveId)->first();

        if (!$campaign) {
            return response()->json(['success' => false, 'message' => 'Invalid campaign or user.'], 400);
        }

        if ($campaign->training_module == null && $campaign->scorm_training == null) {

            return response()->json(['success' => false, 'message' => 'No training module nor scorm assigned.'], 400);
        }

        setCompanyTimezone($campaign->company_id);

        if (clickedByBot($campaign->company_id, $this->campLiveId, 'email')) {
            return;
        }

        //checking assignment
        $all_camp = Campaign::where('campaign_id', $campaign->campaign_id)->first();

        if ($all_camp->training_assignment == 'all') {
            $trainings = json_decode($all_camp->training_module, true);
            CampaignTrainingService::assignTraining($campaign, $trainings);
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
        // Update campaign_live table
        $campaign->update(['sent' => 1, 'training_assigned' => 1]);
        return response()->json(['success' => true, 'message' => 'Training assigned successfully.']);
    }
}
