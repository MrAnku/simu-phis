<?php

namespace App\Services\CustomisedReport;

use Carbon\Carbon;
use App\Services\CompanyReport;

class LineDataService
{
    protected $type;
    protected $companyId;

     // $types = [
    //     "interaction",
    //     "simulations",
    //     "training_report",
    //     "email_campaign",
    //     "quishing_campaign",
    //     "ai_campaign",
    //     "tprm_campaign",
    //     "whatsapp_campaign"
    // ];

    public function __construct($type, $companyId)
    {
        $this->type = $type;
        $this->companyId = $companyId;
    }

    public function getData($months): array
    {
        switch ($this->type) {
            case "interaction":
                return $this->getInteractionData($months);
            case "simulations":
                return $this->getSimulationsData($months);
            case "training_report":
                return $this->getTrainingReportData($months);
            case "email_campaign":
                return $this->getEmailCampaignData($months);
            case "quishing_campaign":
                return $this->getQuishingCampaignData($months);
            case "ai_campaign":
                return $this->getAiCampaignData($months);
            case "tprm_campaign":
                return $this->getTprmCampaignData($months);
            case "whatsapp_campaign":
                return $this->getWhatsappCampaignData($months);
            default:
                return [];
        }
    }

    private function getInteractionData($months): array
    {
        $startDate = now()->subMonths($months)->startOfMonth();
        $endDate = now()->endOfMonth();

        $data = [];

        $monthDiff = $startDate->diffInMonths($endDate);

        $companyReport = new CompanyReport($this->companyId);

        foreach (range(0, $monthDiff) as $i) {
            $currentMonth = $startDate->copy()->addMonths($i);
            $data[] = [
                'month' => $currentMonth->format('M Y'),
                'email_viewed' => $companyReport->emailViewed($currentMonth),
                'payload_clicked' => $companyReport->payloadClicked($currentMonth),
                'email_reported' => $companyReport->emailReported($currentMonth),
                'compromised' => $companyReport->compromised($currentMonth),
                'training_assigned' => $companyReport->totalTrainingAssigned($currentMonth),
                'ignored' => $companyReport->ignored($currentMonth)
            ];
        }

        return $data;
    }
}