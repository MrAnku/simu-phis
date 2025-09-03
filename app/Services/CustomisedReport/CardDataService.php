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
        $companyReport = new CompanyReport($this->companyId);
        $value = $companyReport->compromiseRate();

        return [
            'title' => 'Compromise Rate',
            'period' => 'All time',
            'value' => $value,
            'icon' => 'LucideAlertTriangle',
            'iconColor' => $value < 5 ? 'text-green-500' : 'text-red-500',
        ];
    }

    private function getClickRateData(): array
    {
        $companyReport = new CompanyReport($this->companyId);
        $value = $companyReport->clickRate();

        return [
            'title' => 'Click Rate',
            'period' => 'All time',
            'value' => $value,
            'icon' => 'LucideMousePointer',
            'iconColor' => $value < 5 ? 'text-green-500' : 'text-red-500',
        ];
    }

    private function getEmailClicksData(): array
    {
        $companyReport = new CompanyReport($this->companyId);
        $value = $companyReport->payloadClicked();

        return [
            'title' => 'Phishing Email Clicks',
            'period' => 'All time',
            'value' => $value,
            'icon' => 'LucideMousePointer',
            'iconColor' => $value < 5 ? 'text-green-500' : 'text-red-500',
        ];
    }

    private function getEmailReportedData(): array
    {
        $companyReport = new CompanyReport($this->companyId);
        $value = $companyReport->emailReported();

        return [
            'title' => 'Emails Reported',
            'period' => 'All time',
            'value' => $value,
            'icon' => 'LucideMailCheck',
            'iconColor' => $value > 5 ? 'text-green-500' : 'text-red-500',
        ];
    }

    private function getEmailCampaignsData(): array
    {
        $companyReport = new CompanyReport($this->companyId);
        $value = $companyReport->emailCampaigns();

        return [
            'title' => 'Email Campaigns',
            'period' => 'All time',
            'value' => $value,
            'icon' => 'LucideMails',
            'iconColor' => $value > 5 ? 'text-green-500' : 'text-red-500',
        ];
    }

    private function getQuishingCampaignsData(): array
    {
        $companyReport = new CompanyReport($this->companyId);
        $value = $companyReport->quishingCampaigns();

        return [
            'title' => 'Quishing Campaigns',
            'period' => 'All time',
            'value' => $value,
            'icon' => 'LucideMailCheck',
            'iconColor' => $value > 5 ? 'text-green-500' : 'text-red-500',
        ];
    }

    private function getAiCampaignsData(): array
    {
        $companyReport = new CompanyReport($this->companyId);
        $value = $companyReport->aiCampaigns();

        return [
            'title' => 'AI Vishing',
            'period' => 'All time',
            'value' => $value,
            'icon' => 'LucideSparkles',
            'iconColor' => $value > 5 ? 'text-green-500' : 'text-red-500',
        ];
    }

    private function getTprmCampaignsData(): array
    {
        $companyReport = new CompanyReport($this->companyId);
        $value = $companyReport->tprmCampaigns();

        return [
            'title' => 'TPRM Campaigns',
            'period' => 'All time',
            'value' => $value,
            'icon' => 'LucideMailCheck',
            'iconColor' => $value > 5 ? 'text-green-500' : 'text-red-500',
        ];
    }

    private function getWhatsappCampaignsData(): array
    {
        $companyReport = new CompanyReport($this->companyId);
        $value = $companyReport->whatsappCampaigns();

        return [
            'title' => 'WhatsApp Campaigns',
            'period' => 'All time',
            'value' => $value,
            'icon' => 'LucideSmartphone',
            'iconColor' => $value > 5 ? 'text-green-500' : 'text-red-500',
        ];
    }
}
