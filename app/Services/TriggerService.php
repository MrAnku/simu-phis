<?php

namespace App\Services;

use App\Models\Users;
use App\Models\Company;
use App\Models\CompanyTrigger;
use App\Models\CompanyTriggerTraining;

class TriggerService
{
    protected $eventType;
    protected $employeeType;
    protected $companyId;

    
    //constructor
    public function __construct($eventType, $employeeType, $companyId)
    {
        $this->eventType = $eventType;
        $this->employeeType = $employeeType;
        $this->companyId = $companyId;
    }

    public function companyHasTrigger(): bool
    {
        return CompanyTrigger::where('company_id', $this->companyId)
            ->where('event_type', $this->eventType)
            ->where('employee_type', $this->employeeType)
            ->exists();
    }

    public function executeTriggerActions(): void
    {
        if ($this->companyHasTrigger()) {

            if($this->eventType == 'new_user'){
                $this->runNewUserActions();
            }
            
        }

    }

    private function runNewUserActions(): void
    {
        // get the last user email added by the company
        $lastUser = Users::where('company_id', $this->companyId)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastUser) {
            $lastUserEmail = $lastUser->user_email;
            // Create a new entry in the company_trigger_trainings table
            CompanyTriggerTraining::create([
                'user_email' => $lastUserEmail,
                'training' => $this->getTrainingId() ?? null,
                'policy' => $this->getPolicyId() ?? null,
                'company_id' => $this->companyId
            ]);
        }
    }

    private function getTrainingId(): ?int
    {
        return CompanyTrigger::where('company_id', $this->companyId)
            ->where('event_type', $this->eventType)
            ->where('employee_type', $this->employeeType)
            ->value('training');
    }

    private function getPolicyId(): ?int
    {
        return CompanyTrigger::where('company_id', $this->companyId)
            ->where('event_type', $this->eventType)
            ->where('employee_type', $this->employeeType)
            ->value('policy');
    }

}