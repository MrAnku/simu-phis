<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyTriggerTraining;
use Illuminate\Console\Command;

class ProcessTriggers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-triggers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companies = Company::where('approved', 1)
            ->where('service_status', 1)
            ->where('role', null)
            ->get();

        if ($companies->isEmpty()) {
            return;
        }

        foreach ($companies as $company) {

            setCompanyTimezone($company->company_id);
            $this->processTrainingTriggers($company);
        }
    }

    private function processTrainingTriggers($company)
    {
        $companyId = $company->company_id;

        $trainingPolicyInQues = CompanyTriggerTraining::where('company_id', $companyId)
            ->where('status', 0)
            ->take(5)
            ->get();
        if($trainingPolicyInQues->isEmpty()){
            return;
        }

        
    }

}