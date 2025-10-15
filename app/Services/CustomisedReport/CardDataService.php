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
            'title' => __('Overall Risk Score'),
            'period' => __('All time'),
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
            'title' => __('Training Completion Rate'),
            'period' => __('All time'),
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
            'title' => __('Compromise Rate'),
            'period' => __('All time'),
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
            'title' => __('Click Rate'),
            'period' => __('All time'),
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
            'title' => __('Phishing Email Clicks'),
            'period' => __('All time'),
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
            'title' => __('Emails Reported'),
            'period' => __('All time'),
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
            'title' => __('Email Campaigns'),
            'period' => __('All time'),
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
            'title' => __('Quishing Campaigns'),
            'period' => __('All time'),
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
            'title' => __('AI Vishing'),
            'period' => __('All time'),
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
            'title' => __('TPRM Campaigns'),
            'period' => __('All time'),
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
            'title' => __('WhatsApp Campaigns'),
            'period' => __('All time'),
            'value' => $value,
            'icon' => 'LucideSmartphone',
            'iconColor' => $value > 5 ? 'text-green-500' : 'text-red-500',
        ];
    }
}
