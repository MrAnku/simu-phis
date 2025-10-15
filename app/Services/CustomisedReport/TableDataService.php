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
    //     "game_report",
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
            case "game_report":
                return $this->getGameReportData($months);
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
                'label' => __(ucwords(str_replace('_', ' ', $key))),
                'sortable' => true
            ];
        }

        $title = __("Employee Report");
        $description = __("This report provides an overview of employee performance and risk metrics.");

        return ['title' => $title, 'description' => $description, 'data' => $data, 'columns' => $columns];
    }

    public function getTrainingReportData($months): array
    {
        $startDate = now()->subMonths($months)->startOfMonth();
        $endDate = now()->endOfMonth();

        $data = [];
        $keys = ['name', 'email', 'training_assigned', 'training_started', 'training_completed', 'training_overdue', 'completion_rate', 'training_in_progress', 'certified'];
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
                'training_assigned' => $empReport->assignedTrainings(),
                'training_started' => $empReport->startedTrainings(),
                'training_completed' => $empReport->trainingCompleted(),
                'training_overdue' => $empReport->overdueTrainings(),
                'completion_rate' => $empReport->trainingCompletionRate(),
                'training_in_progress' => $empReport->trainingInProgress(),
                'certified' => $empReport->certifiedTrainings(),
            ];
        }

        foreach ($keys as $key) {
            $columns[] = [
                'key' => $key,
                'label' => __(ucwords(str_replace('_', ' ', $key))),
                'sortable' => true
            ];
        }

        $title = __("Employee Training Report");
        $description = __("This report provides an overview of employee training progress.");

        return ['title' => $title, 'description' => $description, 'data' => $data, 'columns' => $columns];
    }



    public function getGameReportData($months): array
    {

        $startDate = now()->subMonths($months)->startOfMonth();
        $endDate = now()->endOfMonth();

        $data = [];
        $keys = ['name', 'email', 'assigned_games', 'average_score', 'play_time'];
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
                'assigned_games' => $empReport->assignedGames(),
                'average_score' => $empReport->averageGameScore(),
                'play_time' => $empReport->averageGamePlayTime()
            ];
        }

        foreach ($keys as $key) {
            $columns[] = [
                'key' => $key,
                'label' => __(ucwords(str_replace('_', ' ', $key))),
                'sortable' => true
            ];
        }

        $title = __("Game analytics");
        $description = __("This report provides an overview of employee game performance.");

        return ['title' => $title, 'description' => $description, 'data' => $data, 'columns' => $columns];
    }

    public function getPolicyReportData($months): array
    {
        

        $startDate = now()->subMonths($months)->startOfMonth();
        $endDate = now()->endOfMonth();

        $data = [];
        $keys = ['name', 'email', 'policies_assigned', 'accepted', 'quiz_responded'];
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
                'policies_assigned' => $empReport->policiesAssigned(),
                'accepted' => $empReport->policiesAccepted(),
                'quiz_responded' => $empReport->policyQuizResponded()
            ];
        }

        foreach ($keys as $key) {
            $columns[] = [
                'key' => $key,
                'label' => __(ucwords(str_replace('_', ' ', $key))),
                'sortable' => true
            ];
        }

        $title = __("Policy Overview");
        $description = __("This report provides an overview of assigned policies to the employees");

        return ['title' => $title, 'description' => $description, 'data' => $data, 'columns' => $columns];
    }


}
