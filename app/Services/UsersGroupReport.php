<?php

namespace App\Services;

use App\Models\Users;
use App\Models\Campaign;
use App\Models\UsersGroup;
use App\Models\WaCampaign;
use App\Models\QuishingCamp;
use App\Models\AiCallCampaign;

class UsersGroupReport
{
    private string $groupId;
    private string $companyId;
    private array $dateRange;

    public function __construct(string $groupId, string $companyId, array $dateRange = [])
    {
        $this->groupId = $groupId;
        $this->companyId = $companyId;
        $this->dateRange = $dateRange;
    }

    public function employees(): object
    {
        $userIds =  UsersGroup::where('company_id', $this->companyId)
            ->where('group_id', $this->groupId)
            ->value('users') ?? null;
        if ($userIds) {
            $userIds = json_decode($userIds, true);
            return Users::whereIn('id', $userIds)->get();
        }

        return collect();
    }

    public function totalCampaigns(): int
    {
        return $this->totalEmailCampaigns() + $this->totalQuishingCampaigns() + $this->totalAiCampaigns() + $this->totalWhatsappCampaigns();
    }
    public function totalEmailCampaigns(): int
    {
        return Campaign::where('company_id', $this->companyId)
            ->where('users_group', $this->groupId)
            ->when(!empty($this->dateRange), function ($query) {
                $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();
    }

    public function totalQuishingCampaigns(): int
    {
        return QuishingCamp::where('company_id', $this->companyId)
            ->where('users_group', $this->groupId)
            ->when(!empty($this->dateRange), function ($query) {
                $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();
    }

    public function totalAiCampaigns(): int
    {
        return AiCallCampaign::where('company_id', $this->companyId)
            ->where('users_group', $this->groupId)
            ->when(!empty($this->dateRange), function ($query) {
                $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();
    }
    public function totalWhatsappCampaigns(): int
    {
        return WaCampaign::where('company_id', $this->companyId)
            ->where('users_group', $this->groupId)
            ->when(!empty($this->dateRange), function ($query) {
                $query->whereBetween('created_at', $this->dateRange);
            })
            ->count();
    }

    public function totalCompromised(): int
    {
        $compromised = 0;
        $employees = $this->employees();
        foreach ($employees as $employee) {
            $employeeReport = new EmployeeReport($employee->user_email, $this->companyId, $this->dateRange);
            $compromised += $employeeReport->compromised();
        }

        return $compromised;
    }

    public function compromiseRate(): float
    {
        $totalSimulations = $this->totalCampaigns();
        if ($totalSimulations === 0) {
            return 0.0;
        }

        return ($this->totalCompromised() / $totalSimulations) * 100;
    }
    public function totalEmployees(): int
    {
        return $this->employees()->count();
    }
    public function groupRiskScore(): float
    {
        $employees = $this->employees();

        if ($employees->isEmpty()) {
            return 0.0;
        }

        $totalRiskScore = $employees->sum(function ($employee) {
            $employeeReport = new EmployeeReport($employee->user_email, $this->companyId, $this->dateRange);
            return $employeeReport->calculateOverallRiskScore();
        });

        return $totalRiskScore / $employees->count();
    }
}