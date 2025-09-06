<?php

namespace App\Services\CustomisedReport;

use App\Services\CompanyReport;
use App\Services\UsersGroupReport;

class BubbleDataService
{
    protected $type;
    protected $companyId;

    // $types = [
    //     "division_report",
    //     "mitigation_rate",
    //     "average_time_to_click",
    //     "employee_performance",

    // ];

    public function __construct($type, $companyId)
    {
        $this->type = $type;
        $this->companyId = $companyId;
    }

    public function getData($months): array
    {
        switch ($this->type) {
            case "division_report":
                return $this->getDivisionReportData($months);
            // case "mitigation_rate":
            //     return $this->getMitigationRateData();
            // case "average_time_to_click":
            //     return $this->getAverageTimeToClickData();
            // case "employee_performance":
            //     return $this->getEmployeePerformanceData();
            default:
                return [];
        }
    }

    private function getDivisionReportData($months): array
    {
        $startDate = now()->subMonths($months)->startOfMonth();
        $endDate = now()->endOfMonth();

        $companyReport = new CompanyReport($this->companyId);
        $userGroups = $companyReport->userGroups();
        $data = [
            'title' => 'Division Report',
            'subtitle' => 'Division performance overview',
            'type' => 'bubble',
            'data' => [],
            'series' => [],
            'xAxisKey' => 'compromise_rate',
            'yAxisKey' => 'risk_score',
            'sizeKey' => 'employees',
            'description' => 'The performance of different division of their compromise rate and risk score.',
        ];
        $keys = ['division_name', 'phishing_attacks', 'compromised', 'security_score', 'employees'];

        foreach ($userGroups as $group) {
            $groupReport = new UsersGroupReport(
                $group->group_id,
                $this->companyId,
                [$startDate, $endDate]
            );
            $data['data'][] = [
                'division_name' => $group->group_name,
                'phishing_attacks' => $groupReport->totalCampaigns(),
                'compromised' => $groupReport->totalCompromised(),
                'compromise_rate' => $groupReport->compromiseRate(),
                'risk_score' => $groupReport->groupRiskScore(),
                'employees' => $groupReport->totalEmployees(),
            ];
        }
        foreach ($keys as $key) {
            $data['series'][] = [
                'key' => $key,
                'label' => ucwords(str_replace('_', ' ', $key)),
                'color' => '#' . substr(md5($key), 0, 6),
                'sizeKey' => $key
            ];
        }

        return $data;
    }
}
