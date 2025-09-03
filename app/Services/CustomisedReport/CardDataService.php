<?php

namespace App\Services\CustomisedReport;

use App\Services\CompanyReport;

class CardDataService
{
    protected $type;
    protected $companyId;

    // $types = [
    //     "company_risk_score",
    //     "training_completion_rate",
    //     "compromise_rate",
    //     "employee_compromised",
    //     "click_rate",
    //     "email_clicks",
    //     "email_reported",
    //     "email_campaigns",
    //     "quishing_campaigns",
    //     "ai_campaigns",
    //     "tprm_campaigns",
    //     "whatsapp_campaigns"
    // ];

    public function __construct($type, $companyId)
    {
        $this->type = $type;
        $this->companyId = $companyId;
    }

    public function getData(): array
    {
        switch ($this->type) {
            case "company_risk_score":
                return $this->getCompanyRiskScoreData();
            case "training_completion_rate":
                return $this->getTrainingCompletionRateData();
            case "compromise_rate":
                return $this->getCompromiseRateData();
            case "employee_compromised":
                return $this->getEmployeeCompromisedData();
            case "click_rate":
                return $this->getClickRateData();
            case "email_clicks":
                return $this->getEmailClicksData();
            case "email_reported":
                return $this->getEmailReportedData();
            case "email_campaigns":
                return $this->getEmailCampaignsData();
            case "quishing_campaigns":
                return $this->getQuishingCampaignsData();
            case "ai_campaigns":
                return $this->getAiCampaignsData();
            case "tprm_campaigns":
                return $this->getTprmCampaignsData();
            case "whatsapp_campaigns":
                return $this->getWhatsappCampaignsData();
            default:
                return [];
        }
    }

    private function getCompanyRiskScoreData(): array
    {

        $companyReport = new CompanyReport($this->companyId);
        $riskScore = $companyReport->calculateOverallRiskScore();

        return [
            'title' => 'Overall Risk Score',
            'period' => 'All time',
            'value' => $riskScore,
            'icon' => 'LucideAlert',
            'iconColor' => $riskScore < 50 ? 'text-red-500' : 'text-green-500',
        ];
    }

    private function getTrainingCompletionRateData(): array
    {
        $companyReport = new CompanyReport($this->companyId);
        $value = $companyReport->trainingCompletionRate();

        return [
            'title' => 'Training Completion Rate',
            'period' => 'All time',
            'value' => $value,
            'icon' => 'LucideCheckCircle',
            'iconColor' => $value < 5 ? 'text-red-500' : 'text-green-500',
        ];
    }

    private function getCompromiseRateData(): array
    {
        // Logic to fetch and return compromise rate data
        return [
            'type' => 'compromise_rate',
            'data' => 'Sample data for compromise rate',
        ];
    }

    private function getEmployeeCompromisedData(): array
    {
        // Logic to fetch and return employee compromised data
        return [
            'type' => 'employee_compromised',
            'data' => 'Sample data for employee compromised',
        ];
    }

    private function getClickRateData(): array
    {
        // Logic to fetch and return click rate data
        return [
            'type' => 'click_rate',
            'data' => 'Sample data for click rate',
        ];
    }

    private function getEmailClicksData(): array
    {
        // Logic to fetch and return email clicks data
        return [
            'type' => 'email_clicks',
            'data' => 'Sample data for email clicks',
        ];
    }

    private function getEmailReportedData(): array
    {
        // Logic to fetch and return email reported data
        return [
            'type' => 'email_reported',
            'data' => 'Sample data for email reported',
        ];
    }

    private function getEmailCampaignsData(): array
    {
        // Logic to fetch and return email campaigns data
        return [
            'type' => 'email_campaigns',
            'data' => 'Sample data for email campaigns',
        ];
    }

    private function getQuishingCampaignsData(): array
    {
        // Logic to fetch and return quishing campaigns data
        return [
            'type' => 'quishing_campaigns',
            'data' => 'Sample data for quishing campaigns',
        ];
    }

    private function getAiCampaignsData(): array
    {
        // Logic to fetch and return AI campaigns data
        return [
            'type' => 'ai_campaigns',
            'data' => 'Sample data for AI campaigns',
        ];
    }

    private function getTprmCampaignsData(): array
    {
        // Logic to fetch and return TPRM campaigns data
        return [
            'type' => 'tprm_campaigns',
            'data' => 'Sample data for TPRM campaigns',
        ];
    }

    private function getWhatsappCampaignsData(): array
    {
        // Logic to fetch and return WhatsApp campaigns data
        return [
            'type' => 'whatsapp_campaigns',
            'data' => 'Sample data for WhatsApp campaigns',
        ];
    }
}