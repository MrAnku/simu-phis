<?php

namespace App\Services;

use App\Models\Users;
use App\Models\Company;
use App\Models\CompanyTrigger;
use App\Models\BlueCollarEmployee;
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
                if($this->employeeType == 'normal'){
                    $this->runNormalUserActions();
                }
                if($this->employeeType == 'bluecollar'){
                    $this->runBlueCollarUserActions();
                }
            }
            
        }

    }

    private function runNormalUserActions(): void
    {
        // get the last user email added by the company
        $lastUser = Users::where('company_id', $this->companyId)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastUser) {
            // Create a new entry in the company_trigger_trainings table
            CompanyTriggerTraining::create([
                'employee_type' => $this->employeeType,
                'user_id' => $lastUser->id,
                'user_name' => $lastUser->user_name,
                'user_email' => $lastUser->user_email,
                'training' => $this->getAction('training') ?? null,
                'policy' => $this->getAction('policy') ?? null,
                'scorm' => $this->getAction('scorm') ?? null,
                'company_id' => $this->companyId
            ]);
        }
    }

    private function runBlueCollarUserActions(): void
    {
        // get the last user email added by the company
        $lastUser = BlueCollarEmployee::where('company_id', $this->companyId)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastUser) {
            // Create a new entry in the company_trigger_trainings table
            CompanyTriggerTraining::create([
                'employee_type' => $this->employeeType,
                'user_id' => $lastUser->id,
                'user_name' => $lastUser->user_name,
                'user_whatsapp' => $lastUser->whatsapp,
                'training' => $this->getAction('training') ?? null,
                'policy' => $this->getAction('policy') ?? null,
                'scorm' => $this->getAction('scorm') ?? null,
                'company_id' => $this->companyId
            ]);
        }
    }

    private function getAction(string $type): ?string
    {
        return CompanyTrigger::where('company_id', $this->companyId)
            ->where('event_type', $this->eventType)
            ->where('employee_type', $this->employeeType)
            ->value($type);
    }

   

}