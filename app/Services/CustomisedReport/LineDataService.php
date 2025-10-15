<?php

namespace App\Services\CustomisedReport;

use App\Services\CompanyReport;
use App\Services\Simulations\EmailCampReport;
use App\Services\Simulations\QuishingCampReport;
use App\Services\Simulations\TprmCampReport;
use App\Services\Simulations\VishingCampReport;
use App\Services\Simulations\WhatsappCampReport;

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
        $keys = ['email_viewed', 'payload_clicked', 'email_reported', 'compromised', 'training_assigned', 'email_ignored'];
        $series = [];

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
                'email_ignored' => $companyReport->emailIgnored($currentMonth)
            ];
        }

        foreach ($keys as $key) {
            $series[] = [
                'key' => $key,
                'label' => __(ucwords(str_replace('_', ' ', $key))),
                'color' => '#' . substr(md5($key), 0, 6),
                'type' => 'line'
            ];
        }
        $title = __("Interaction Analytics");
        $description = __("The report overview of employee interaction of simulation.");

        return ['title' => $title, 'description' => $description, 'data' => $data, 'series' => $series];
    }

    private function getSimulationsData($months): array
    {
        $startDate = now()->subMonths($months)->startOfMonth();
        $endDate = now()->endOfMonth();

        $data = [];
        $keys = ['email_campaign', 'quishing_campaign', 'whatsapp_campaign', 'ai_vishing', 'tprm_campaign'];
        $series = [];

        $monthDiff = $startDate->diffInMonths($endDate);

        $companyReport = new CompanyReport($this->companyId);

        foreach (range(0, $monthDiff) as $i) {
            $currentMonth = $startDate->copy()->addMonths($i);
            $data[] = [
                'month' => $currentMonth->format('M Y'),
                'email_campaign' => $companyReport->emailCampaigns($currentMonth),
                'quishing_campaign' => $companyReport->quishingCampaigns($currentMonth),
                'whatsapp_campaign' => $companyReport->whatsappCampaigns($currentMonth),
                'ai_vishing' => $companyReport->aiCampaigns($currentMonth),
                'tprm_campaign' => $companyReport->tprmCampaigns($currentMonth)
            ];
        }

        foreach ($keys as $key) {
            $series[] = [
                'key' => $key,
                'label' => __(ucwords(str_replace('_', ' ', $key))),
                'color' => '#' . substr(md5($key), 0, 6),
                'type' => 'line'
            ];
        }

        $title = __("Simulation Overview");
        $description = __("The simulation data report and analytics.");


        return ['title' => $title, 'description' => $description, 'data' => $data, 'series' => $series];
    }

    private function getTrainingReportData($months): array
    {
        $startDate = now()->subMonths($months)->startOfMonth();
        $endDate = now()->endOfMonth();

        $data = [];
        $keys = ['training_assigned', 'training_completed', 'training_in_progress', 'certified', 'training_overdue'];
        $series = [];

        $monthDiff = $startDate->diffInMonths($endDate);

        $companyReport = new CompanyReport($this->companyId);

        foreach (range(0, $monthDiff) as $i) {
            $currentMonth = $startDate->copy()->addMonths($i);
            $data[] = [
                'month' => $currentMonth->format('M Y'),
                'training_assigned' => $companyReport->totalTrainingAssigned($currentMonth),
                'training_completed' => $companyReport->completedTraining($currentMonth),
                'training_in_progress' => $companyReport->inProgressTraining($currentMonth),
                'certified' => $companyReport->certifiedUsers($currentMonth),
                'training_overdue' => $companyReport->overdueTraining($currentMonth)
            ];
        }

        foreach ($keys as $key) {
            $series[] = [
                'key' => $key,
                'label' => __(ucwords(str_replace('_', ' ', $key))),
                'color' => '#' . substr(md5($key), 0, 6),
                'type' => 'line'
            ];
        }

        $title = __("Training Overview");
        $description = __("The training progress and analysis.");


        return ['title' => $title, 'description' => $description, 'data' => $data, 'series' => $series];
    }

    private function getEmailCampaignData($months): array
    {
        $startDate = now()->subMonths($months)->startOfMonth();
        $endDate = now()->endOfMonth();

        $data = [];
        $keys = ['email_sent', 'email_viewed', 'payload_clicked', 'email_reported', 'compromised'];
        $series = [];

        $monthDiff = $startDate->diffInMonths($endDate);

        $companyReport = new EmailCampReport($this->companyId);

        foreach (range(0, $monthDiff) as $i) {
            $currentMonth = $startDate->copy()->addMonths($i);
            $data[] = [
                'month' => $currentMonth->format('M Y'),
                'email_sent' => $companyReport->emailSent($currentMonth),
                'email_viewed' => $companyReport->emailViewed($currentMonth),
                'payload_clicked' => $companyReport->payloadClicked($currentMonth),
                'email_reported' => $companyReport->emailReported($currentMonth),
                'compromised' => $companyReport->compromised($currentMonth)
            ];
        }

        foreach ($keys as $key) {
            $series[] = [
                'key' => $key,
                'label' => __(ucwords(str_replace('_', ' ', $key))),
                'color' => '#' . substr(md5($key), 0, 6),
                'type' => 'line'
            ];
        }

        $title = __("Email Campaign Overview");
        $description = __("Email campaign and interaction overview");


        return ['title' => $title, 'description' => $description, 'data' => $data, 'series' => $series];
    }

    private function getQuishingCampaignData($months): array
    {
        $startDate = now()->subMonths($months)->startOfMonth();
        $endDate = now()->endOfMonth();

        $data = [];
        $keys = ['email_sent', 'email_viewed', 'qr_scanned', 'email_reported', 'compromised'];
        $series = [];

        $monthDiff = $startDate->diffInMonths($endDate);

        $companyReport = new QuishingCampReport($this->companyId);

        foreach (range(0, $monthDiff) as $i) {
            $currentMonth = $startDate->copy()->addMonths($i);
            $data[] = [
                'month' => $currentMonth->format('M Y'),
                'email_sent' => $companyReport->emailSent($currentMonth),
                'email_viewed' => $companyReport->emailViewed($currentMonth),
                'qr_scanned' => $companyReport->qrScanned($currentMonth),
                'email_reported' => $companyReport->emailReported($currentMonth),
                'compromised' => $companyReport->compromised($currentMonth)
            ];
        }

        foreach ($keys as $key) {
            $series[] = [
                'key' => $key,
                'label' => __(ucwords(str_replace('_', ' ', $key))),
                'color' => '#' . substr(md5($key), 0, 6),
                'type' => 'line'
            ];
        }

        $title = __("Quishing Campaign Overview");
        $description = __("Quishing campaign and interaction overview");


        return ['title' => $title, 'description' => $description, 'data' => $data, 'series' => $series];
    }

    private function getAiCampaignData($months): array
    {

        $startDate = now()->subMonths($months)->startOfMonth();
        $endDate = now()->endOfMonth();

        $data = [];
        $keys = ['calls_sent', 'calls_received', 'compromised', 'busy_calls', 'completed_calls', 'calls_in_progress', 'calls_failed', 'canceled', 'no_answer'];
        $series = [];

        $monthDiff = $startDate->diffInMonths($endDate);

        $companyReport = new VishingCampReport($this->companyId);

        foreach (range(0, $monthDiff) as $i) {
            $currentMonth = $startDate->copy()->addMonths($i);
            $data[] = [
                'month' => $currentMonth->format('M Y'),
                'calls_sent' => $companyReport->callsSent($currentMonth),
                'calls_received' => $companyReport->callsReceived($currentMonth),
                'compromised' => $companyReport->compromised($currentMonth),
                'busy_calls' => $companyReport->busyCalls($currentMonth),
                'completed_calls' => $companyReport->completedCalls($currentMonth),
                'calls_in_progress' => $companyReport->callInProgress($currentMonth),
                'calls_failed' => $companyReport->callsFailed($currentMonth),
                'canceled' => $companyReport->canceled($currentMonth),
                'no_answer' => $companyReport->noAnswer($currentMonth)
            ];
        }

        foreach ($keys as $key) {
            $series[] = [
                'key' => $key,
                'label' => __(ucwords(str_replace('_', ' ', $key))),
                'color' => '#' . substr(md5($key), 0, 6),
                'type' => 'line'
            ];
        }

        $title = __("Vishing Campaign Overview");
        $description = __("Vishing campaign and interaction overview");


        return ['title' => $title, 'description' => $description, 'data' => $data, 'series' => $series];
    }

    private function getTprmCampaignData($months): array
    {

        $startDate = now()->subMonths($months)->startOfMonth();
        $endDate = now()->endOfMonth();

        $data = [];
        $keys = ['email_sent', 'email_viewed', 'payload_clicked', 'email_reported', 'compromised'];
        $series = [];

        $monthDiff = $startDate->diffInMonths($endDate);

        $companyReport = new TprmCampReport($this->companyId);

        foreach (range(0, $monthDiff) as $i) {
            $currentMonth = $startDate->copy()->addMonths($i);
            $data[] = [
                'month' => $currentMonth->format('M Y'),
                'email_sent' => $companyReport->emailSent($currentMonth),
                'email_viewed' => $companyReport->emailViewed($currentMonth),
                'payload_clicked' => $companyReport->payloadClicked($currentMonth),
                'email_reported' => $companyReport->emailReported($currentMonth),
                'compromised' => $companyReport->compromised($currentMonth)
            ];
        }

        foreach ($keys as $key) {
            $series[] = [
                'key' => $key,
                'label' => __(ucwords(str_replace('_', ' ', $key))),
                'color' => '#' . substr(md5($key), 0, 6),
                'type' => 'line'
            ];
        }

        $title = __("TPRM Campaign Overview");
        $description = __("TPRM campaign and interaction overview");


        return ['title' => $title, 'description' => $description, 'data' => $data, 'series' => $series];
    }

    private function getWhatsappCampaignData($months): array
    {


        $startDate = now()->subMonths($months)->startOfMonth();
        $endDate = now()->endOfMonth();

        $data = [];
        $keys = ['message_sent', 'message_viewed', 'link_clicked', 'compromised'];
        $series = [];

        $monthDiff = $startDate->diffInMonths($endDate);

        $companyReport = new WhatsappCampReport($this->companyId);

        foreach (range(0, $monthDiff) as $i) {
            $currentMonth = $startDate->copy()->addMonths($i);
            $data[] = [
                'month' => $currentMonth->format('M Y'),
                'message_sent' => $companyReport->messageSent($currentMonth),
                'message_viewed' => $companyReport->messageViewed($currentMonth),
                'link_clicked' => $companyReport->linkClicked($currentMonth),
                'compromised' => $companyReport->compromised($currentMonth)
            ];
        }

        foreach ($keys as $key) {
            $series[] = [
                'key' => $key,
                'label' => __(ucwords(str_replace('_', ' ', $key))),
                'color' => '#' . substr(md5($key), 0, 6),
                'type' => 'line'
            ];
        }

        $title = __("WhatsApp Campaign Overview");
        $description = __("WhatsApp campaign and interaction overview");


        return ['title' => $title, 'description' => $description, 'data' => $data, 'series' => $series];
    }
}
