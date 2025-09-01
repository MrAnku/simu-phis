<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use App\Models\TrainingAssignedUser;
use App\Models\CompanyTriggerTraining;
use App\Models\TrainingModule;
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
            if($queue->employee_type == 'normal'){
                $this->processQueueForNormalUser($queue);
            }
            if($queue->employee_type == 'bluecollar'){
                $this->processQueueForBlueCollarUser($queue);
            }
        }

      
    }

    private function processQueueForNormalUser($queue)
    {

        $training = $queue->training;
        $policy = $queue->policy;
        $scorm = $queue->scorm;

        if (!$training && !$policy && !$scorm) {
            return;
        }

        if($training){
            $trainings = json_decode($training, true);
            if (!$trainings) {
                return;
            }
            foreach ($trainings as $training) {

                if(TrainingModule::where('id', $training['training'])->doesntExist()){
                    continue;
                }
                //sending training
                $assignedTraining = TrainingAssignedUser::where('user_email', $queue->user_email)
                    ->where('training', $training['training'])
                    ->first();

                if (!$assignedTraining) {
                    //call assignNewTraining from service method
                    $campData = [
                        'campaign_id' => 'from_trigger',
                        'user_id' => $queue->user_id,
                        'user_name' => $queue->user_name,
                        'user_email' => $queue->user_email,
                        'training' => $training['training'],
                        'training_lang' => $training['training_lang'],
                        'training_type' => $training['training_type'],
                        'assigned_date' => now()->toDateString(),
                        'training_due_date' => now()->addDays($training['days_until_due'])->toDateString(),
                        'company_id' => $queue->company_id
                    ];

                    $trainingAssignedService = new TrainingAssignedService();

                    $trainingAssignedService->assignNewTraining($campData);
                }
            }
            $mailData = [
                'user_email' => $queue->user_email,
                'user_name' => $queue->user_name,
                'company_id' => $queue->company_id,
            ];
            $isSent = $trainingAssignedService->sendTrainingEmail($mailData);
            if($isSent['status'] == 1){
                $queue->sent = 1;
                $queue->save();
            }
            

        }

    }

    private function processQueueForBlueCollarUser($queue)
    {
        // Process the queue for blue-collar users
    }

        // $assignedTraining = TrainingAssignedUser::where('user_email', $queue->user_email)
        //     ->where('training', $queue->training)
        //     ->first();

        // if (!$assignedTraining) {
        //     //call assignNewTraining from service method
        //     $campData = [
        //         'campaign_id' => $campaign->campaign_id,
        //         'user_id' => $campaign->user_id,
        //         'user_name' => $campaign->user_name,
        //         'user_email' => $user_email,
        //         'training' => $training,
        //         'training_lang' => $campaign->training_lang,
        //         'training_type' => $campaign->training_type,
        //         'assigned_date' => now()->toDateString(),
        //         'training_due_date' => now()->addDays($campaign->days_until_due)->toDateString(),
        //         'company_id' => $campaign->company_id
        //     ];

        //     $trainingAssignedService->assignNewTraining($campData);
        // }


}