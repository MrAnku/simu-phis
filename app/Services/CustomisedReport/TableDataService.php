<?php

namespace App\Services\CustomisedReport;

use App\Services\CompanyReport;
use App\Services\EmployeeReport;

class TableDataService
{

    protected $type;
    protected $companyId;

    // $types = [
    //     "employees",
    //     "training_report",
    //     "scorm_report",
    //     "game_report",
    //     "campaign_report",
    //     "policy_report"
    // ];

    public function __construct($type, $companyId)
    {
        $this->type = $type;
        $this->companyId = $companyId;
    }

    public function getData($months): array
    {
        switch ($this->type) {
            case "employees":
                return $this->getEmployeesData($months);
            case "training_report":
                return $this->getTrainingReportData($months);
            case "scorm_report":
                return $this->getScormReportData($months);
            case "game_report":
                return $this->getGameReportData($months);
            case "campaign_report":
                return $this->getCampaignReportData($months);
            case "policy_report":
                return $this->getPolicyReportData($months);
            default:
                return [];
        }
    }

    public function getEmployeesData($months): array
    {
        $startDate = now()->subMonths($months)->startOfMonth();
        $endDate = now()->endOfMonth();

        $data = [];
        $keys = ['name', 'email', 'campaigns_ran', 'compromise_rate', 'risk_score', 'ignore_rate', 'training_assigned'];
        $columns = [];

        $employees = new CompanyReport($this->companyId);

        foreach ($employees->employees() as $employee) {
            $empReport = new EmployeeReport(
                $employee->user_email,
                $this->companyId,
                [$startDate, $endDate]
            );
            $data[] = [
                'name' => $employee->user_name,
                'email' => $employee->user_email,
                'campaigns_ran' => $empReport->totalSimulations(),
                'compromise_rate' => $empReport->compromiseRate(),
                'risk_score' => $empReport->calculateOverallRiskScore(),
                'ignore_rate' => $empReport->ignoreRate(),
                'training_assigned' => $empReport->assignedTrainings(),
            ];
        }

        foreach ($keys as $key) {
            $columns[] = [
                'key' => $key,
                'label' => ucwords(str_replace('_', ' ', $key)),
                'sortable' => true
            ];
        }

        $title = "Employee Report";
        $description = "This report provides an overview of employee performance and risk metrics.";

        return ['title' => $title, 'description' => $description, 'data' => $data, 'columns' => $columns];
    }

    // public function getTrainingReportData($months): array
    // {
    //     // Implementation for fetching training report data
    // }

    // public function getScormReportData($months): array
    // {
    //     // Implementation for fetching SCORM report data
    // }

    // public function getGameReportData($months): array
    // {
    //     // Implementation for fetching game report data
    // }

    // public function getCampaignReportData($months): array
    // {
    //     // Implementation for fetching campaign report data
    // }

    // public function getPolicyReportData($months): array
    // {
    //     // Implementation for fetching policy report data
    // }


}
