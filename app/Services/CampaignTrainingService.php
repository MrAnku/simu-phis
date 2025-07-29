<?php

namespace App\Services;

use App\Models\Users;
use App\Models\ScormAssignedUser;
use Illuminate\Support\Facades\DB;
use App\Models\TrainingAssignedUser;

class CampaignTrainingService
{

    public static function assignTraining($campaign, $trainingModules = null, $smishing = false, $scormTrainings = null)
    {

        if ($trainingModules !== null || $scormTrainings !== null) {
            return self::assignAllTrainings($campaign, $trainingModules, $smishing, $scormTrainings);
        } else {
            return self::assignSingleTraining($campaign, $smishing);
        }
    }

    private static function assignAllTrainings($campaign, $trainingModules = null, $smishing, $scormTrainings = null)
    {
        $trainingAssignedService = new TrainingAssignedService();

        if ($smishing) {
            $user_email = Users::find($campaign->user_id)->user_email;
        } else {
            $user_email = $campaign->user_email;
        }

        if (!empty($trainingModules)) {
            foreach ($trainingModules as $training) {

                //check if this training is already assigned to this user
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
            } else {
                $assignedTraining->update(
                    [
                        'training_due_date' => now()->addDays($campaign->days_until_due)->toDateString(),
                        'training_lang' => $campaign->training_lang,
                        'training_type' => $campaign->training_type,
                        'assigned_date' => now()->toDateString()
                    ]
                );
            }
        }
        }

        if (!empty($scormTrainings)) {
            foreach ($scormTrainings as $training) {

                //check if this training is already assigned to this user
                $assignedTraining = ScormAssignedUser::where('user_email', $user_email)
                    ->where('scorm', $training)
                    ->first();

                if (!$assignedTraining) {
                    //call assignNewTraining from service method
                    $campData = [
                        'campaign_id' => $campaign->campaign_id,
                        'user_id' => $campaign->user_id,
                        'user_name' => $campaign->user_name,
                        'user_email' => $user_email,
                        'scorm' => $training,
                        'assigned_date' => now()->toDateString(),
                        'scorm_due_date' => now()->addDays($campaign->days_until_due)->toDateString(),
                        'company_id' => $campaign->company_id
                    ];

                    // $trainingAssignedService->assignNewTraining($campData);

                    DB::table('scorm_assigned_users')
                        ->insert($campData);

                    echo 'Scorm assigned successfully to ' . $user_email . "\n";


                    // if ($trainingAssigned['status'] == true) {
                    //     return true;
                    // } else {
                    //     return false;
                    // }
                }
            }
        }



        //send mail to user
        $campData = [
            'user_name' => $campaign->user_name,
            'user_email' => $user_email,
            'company_id' => $campaign->company_id
        ];
        $isMailSent = $trainingAssignedService->sendTrainingEmail($campData);

        if ($isMailSent['status'] == true) {
            return true;
        } else {
            return false;
        }
    }

    private static function assignSingleTraining($campaign, $smishing)
    {
        $trainingAssignedService = new TrainingAssignedService();

        if ($smishing) {
            $user_email = Users::find($campaign->user_id)->user_email;
        } else {
            $user_email = $campaign->user_email;
        }

        if ($campaign->training_module !== null) {
            $assignedTrainingModule = TrainingAssignedUser::where('user_email', $user_email)
                ->where('training', $campaign->training_module)
                ->first();

            if (!$assignedTrainingModule) {
                //call assignNewTraining from service method
                $campData = [
                    'campaign_id' => $campaign->campaign_id,
                    'user_id' => $campaign->user_id,
                    'user_name' => $campaign->user_name,
                    'user_email' => $user_email,
                    'training' => $campaign->training_module,
                    'training_lang' => $campaign->training_lang,
                    'training_type' => $campaign->training_type,
                    'assigned_date' => now()->toDateString(),
                    'training_due_date' => now()->addDays($campaign->days_until_due)->toDateString(),
                    'company_id' => $campaign->company_id
                ];

            $trainingAssignedService->assignNewTraining($campData);
        } else {
            $assignedTrainingModule->update(
                [
                    'training_due_date' => now()->addDays($campaign->days_until_due)->toDateString(),
                    'training_lang' => $campaign->training_lang,
                    'training_type' => $campaign->training_type,
                    'assigned_date' => now()->toDateString()
                ]
            );
        }
    }


        if ($campaign->scorm_training !== null) {
            $assignedTrainingModule = ScormAssignedUser::where('user_email', $user_email)
                ->where('scorm', $campaign->scorm_training)
                ->first();

            if (!$assignedTrainingModule) {
                //call assignNewTraining from service method
                $campData = [
                    'campaign_id' => $campaign->campaign_id,
                    'user_id' => $campaign->user_id,
                    'user_name' => $campaign->user_name,
                    'user_email' => $user_email,
                    'scorm' => $campaign->scorm_training,
                    'assigned_date' => now()->toDateString(),
                    'scorm_due_date' => now()->addDays($campaign->days_until_due)->toDateString(),
                    'company_id' => $campaign->company_id
                ];

                DB::table('scorm_assigned_users')
                    ->insert($campData);

                echo 'Scorm assigned successfully to ' . $user_email . "\n";


                // if ($trainingAssigned['status'] == true) {
                //     return true;
                // } else {
                //     return false;
                // }
            }
        }



        //send mail to user
        $campData = [
            'user_name' => $campaign->user_name,
            'user_email' => $user_email,
            'company_id' => $campaign->company_id
        ];
        $isMailSent = $trainingAssignedService->sendTrainingEmail($campData);

        if ($isMailSent['status'] == true) {
            return true;
        } else {
            return false;
        }
    }
}
