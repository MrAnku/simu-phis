<?php

namespace App\Console\Commands;

use App\Models\Policy;
use App\Models\Company;
use App\Models\ScormTraining;
use App\Models\AssignedPolicy;
use App\Models\TrainingModule;
use Illuminate\Console\Command;
use App\Mail\PolicyCampaignEmail;
use App\Models\ScormAssignedUser;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\Mail;
use App\Models\BlueCollarTrainingUser;
use App\Models\CompanyTriggerTraining;
use App\Services\TrainingAssignedService;
use App\Models\BlueCollarScormAssignedUser;

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
            ->take(1)
            ->get();

        if ($queues->isEmpty()) {
            return;
        }

        foreach ($queues as $queue) {
            $trainings = $queue->training;
            $policies = $queue->policy;
            $scorms = $queue->scorm;

            if (!$trainings && !$policies && !$scorms) {
                return;
            }

            if ($trainings) {
                $this->assignTraining($trainings, $queue, $queue->employee_type);
            }

            if ($policies) {
                $this->assignPolicy($policies, $queue, $queue->employee_type);
            }

            if ($scorms) {
                $this->assignScorm($scorms, $queue, $queue->employee_type);
            }

            $queue->sent = 1;
            $queue->save();
        }
    }



    private function assignTraining($trainings, $queue, $employeeType)
    {
        $trainings = json_decode($trainings, true);
        if (!$trainings) {
            return;
        }
        foreach ($trainings as $training) {

            $trainingId = $training['training'];

            if (TrainingModule::where('id', $trainingId)->doesntExist()) {
                continue;
            }
            if ($employeeType == 'bluecollar') {
                echo "Assigning training to " . $employeeType . "\n";
                $this->assignTrainingToBluecollar($training, $queue);
                continue;
            }
            echo "Assigning training to " . $employeeType . "\n";
            //sending training
            $assignedTraining = TrainingAssignedUser::where('user_email', $queue->user_email)
                ->where('training', $trainingId)
                ->first();

            if (!$assignedTraining) {
                //call assignNewTraining from service method
                $campData = [
                    'campaign_id' => 'from_trigger',
                    'user_id' => $queue->user_id,
                    'user_name' => $queue->user_name,
                    'user_email' => $queue->user_email,
                    'training' => $trainingId,
                    'training_lang' => $training['training_lang'],
                    'training_type' => $training['training_type'],
                    'assigned_date' => now()->toDateString(),
                    'training_due_date' => now()->addDays($training['days_until_due'])->toDateString(),
                    'company_id' => $queue->company_id
                ];

                $trainingAssignedService = new TrainingAssignedService();

                $trainingAssignedService->assignNewTraining($campData);
            }

            $trainingNames = [];
            // Append training name to array
            $module = TrainingModule::find($trainingId);
            $trainingNames[] = $module->name;
        }
        if ($queue->employee_type == 'bluecollar') {
            $this->sendWhatsappNotification($queue);
        } else {
            $mailData = [
                'user_email' => $queue->user_email,
                'user_name' => $queue->user_name,
                'company_id' => $queue->company_id,
                'training_due_date' => isset($training['days_until_due']) ? now()->addDays($training['days_until_due'])->toDateString() : null,
            ];
            $trainingAssignedService = new TrainingAssignedService();
            $isSent = $trainingAssignedService->sendTrainingEmail($mailData, collect($trainingNames));
            if ($isSent['status'] == 1) {
                echo "Training assigned to " . $queue->user_name . "\n";
            }
        }
    }

    private function assignPolicy($policies, $queue, $employeeType)
    {
        if ($employeeType == 'bluecollar') {
            return;
        }

        $policies = json_decode($policies, true);

        if (!$policies) {
            return;
        }

        $policyNames = [];

        foreach ($policies as $policyId) {
            echo "Assigning policy to " . $queue->user_email . "\n";
            $policyName = Policy::where('id', $policyId)->value('policy_name') ?? null;
            if (!$policyName) {
                continue;
            }
            $policyNames[] = $policyName;

            $isPolicyExists = AssignedPolicy::where('user_email', $queue->user_email)
                ->where('policy', $policyId)
                ->where('company_id', $queue->company_id)
                ->exists();

            if ($isPolicyExists) {
                continue;
            }
            AssignedPolicy::create([
                'campaign_id' => 'from_trigger',
                'user_name' => $queue->user_name,
                'user_email' => $queue->user_email,
                'policy' => $policyId,
                'company_id' => $queue->company_id,
            ]);
        }

        $mailData = [
            'user_name' => $queue->user_name,
            'assigned_at' => now(),
            'policy_names' => $policyNames,
            'company_id' => $queue->company_id,
        ];
        try {
            Mail::to($queue->user_email)->send(new PolicyCampaignEmail($mailData));
        } catch (\Exception $e) {
            echo 'Failed to send email: ' . $e->getMessage() . "\n";
        }

        echo "Policy assigned to " . $queue->user_name . "\n";
    }

    private function assignScorm($scorms, $queue, $employeeType)
    {
        $scorms = json_decode($scorms, true);

        if (!$scorms) {
            return;
        }
        foreach ($scorms as $scormId) {

            $scormExists = ScormTraining::where('id', $scormId)->exists();
            if (!$scormExists) {
                continue;
            }
            if ($employeeType == 'bluecollar') {
                $this->assignScormToBluecollar($scormId, $queue);
                continue;
            }
            $scormAssigned = ScormAssignedUser::where('user_email', $queue->user_email)
                ->where('scorm', $scormId)
                ->first();

            if (!$scormAssigned) {
                $campData = [
                    'campaign_id' => 'from_trigger',
                    'user_id' => $queue->user_id,
                    'user_name' => $queue->user_name,
                    'user_email' => $queue->user_email,
                    'scorm' => $scormId,
                    'assigned_date' => now()->toDateString(),
                    'scorm_due_date' => now()->addDays($queue->days_until_due)->toDateString(),
                    'company_id' => $queue->company_id
                ];
                $trainingAssignedService = new TrainingAssignedService();
                $trainingAssignedService->assignNewScormTraining($campData);
            }

            $trainingNames = [];
            // Append scorm training name to array
            $module = ScormTraining::find($scormId);
            $trainingNames[] = $module->name;
        }
        if ($queue->employee_type == 'bluecollar') {
            $this->sendWhatsappNotification($queue);
        } else {
            //send mail to user
            $campData = [
                'user_name' => $queue->user_name,
                'user_email' => $queue->user_email,
                'company_id' => $queue->company_id,
            ];
            $trainingAssignedService = new TrainingAssignedService();
            $isMailSent = $trainingAssignedService->sendTrainingEmail($campData, collect($trainingNames));

            if ($isMailSent['status'] == true) {
                echo "Mail sent successfully to " . $queue->user_name . "\n";
            } else {
                echo "Failed to send mail to " . $queue->user_name . "\n";
            }
        }
    }

    private function assignTrainingToBluecollar($training, $queue)
    {
        $trainingId = $training['training'];

        $assignedTrainingModule = BlueCollarTrainingUser::where('user_whatsapp', $queue->user_whatsapp)
            ->where('training', $trainingId)
            ->first();

        if (!$assignedTrainingModule) {
            //call assignNewTraining from service method
            $campData = [
                'campaign_id' => 'from_trigger',
                'user_id' => $queue->user_id,
                'user_name' => $queue->user_name,
                'user_whatsapp' => $queue->user_whatsapp,
                'training' => $trainingId,
                'training_lang' => $training['training_lang'],
                'training_type' => $training['training_type'],
                'assigned_date' => now()->toDateString(),
                'training_due_date' => now()->addDays($training['days_until_due'])->toDateString(),
                'company_id' => $queue->company_id
            ];

            // $trainingAssignedService->assignNewTraining($campData);
            BlueCollarTrainingUser::create($campData);
        }
    }

    private function assignScormToBluecollar($scormId, $queue)
    {
        $assignedTrainingModule = BlueCollarScormAssignedUser::where('user_whatsapp', $queue->user_whatsapp)
            ->where('scorm', $scormId)
            ->first();

        if (!$assignedTrainingModule) {
            //call assignNewTraining from service method
            $campData = [
                'campaign_id' => 'from_trigger',
                'user_id' => $queue->user_id,
                'user_name' => $queue->user_name,
                'user_whatsapp' => $queue->user_whatsapp,
                'scorm' => $scormId,
                'assigned_date' => now()->toDateString(),
                'scorm_due_date' => now()->addDays($queue->days_until_due)->toDateString(),
                'company_id' => $queue->company_id
            ];

            BlueCollarScormAssignedUser::create($campData);

            echo 'Scorm assigned successfully to ' . $queue->user_whatsapp . "\n";
        }
    }

    private function sendWhatsappNotification($queue)
    {
        echo "Training/scorm notification sent to " . $queue->user_whatsapp . "\n";
    }
}
