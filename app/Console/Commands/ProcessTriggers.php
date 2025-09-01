<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use App\Models\TrainingAssignedUser;
use App\Models\CompanyTriggerTraining;
use App\Services\TrainingAssignedService;

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

        $queues = CompanyTriggerTraining::where('company_id', $companyId)
            ->where('sent', 0)
            ->take(5)
            ->get();
            
        if ($queues->isEmpty()) {
            return;
        }

        foreach ($queues as $queue) {
            $user_email = $queue->user_email;
            $training = $queue->training;
        }

        //sending training
        $assignedTraining = TrainingAssignedUser::where('user_email', $user_email)
            ->where('training', $training)
            ->first();

        if (!$assignedTraining) {
            //call assignNewTraining from service method
            $campData = [
                'campaign_id' => $campaign->campaign_id,
                'user_id' => $campaign->user_id,
                'user_name' => $campaign->user_name,
                'user_email' => $user_email,
                'training' => $training,
                'training_lang' => $campaign->training_lang,
                'training_type' => $campaign->training_type,
                'assigned_date' => now()->toDateString(),
                'training_due_date' => now()->addDays($campaign->days_until_due)->toDateString(),
                'company_id' => $campaign->company_id
            ];

            $trainingAssignedService->assignNewTraining($campData);
        }
    }
}
