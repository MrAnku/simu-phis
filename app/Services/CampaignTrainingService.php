<?php

namespace App\Services;

use App\Models\Users;
use App\Models\TrainingAssignedUser;

class CampaignTrainingService
{

    public static function assignTraining($campaign, $trainings = null, $smishing = false)
    {

        if ($trainings !== null) {
            return self::assignAllTrainings($campaign, $trainings, $smishing);
        } else {
            return self::assignSingleTraining($campaign, $smishing);
        }
    }

    private static function assignAllTrainings($campaign, $trainings, $smishing)
    {
        $trainingAssignedService = new TrainingAssignedService();

        if ($smishing) {
            $user_email = Users::find($campaign->user_id)->user_email;
        } else {
            $user_email = $campaign->user_email;
        }

        foreach ($trainings as $training) {

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

    private static function assignSingleTraining($campaign, $smishing)
    {
        $trainingAssignedService = new TrainingAssignedService();

        if ($smishing) {
            $user_email = Users::find($campaign->user_id)->user_email;
        } else {
            $user_email = $campaign->user_email;
        }

        $assignedTraining = TrainingAssignedUser::where('user_email', $user_email)
            ->where('training', $campaign->training_module)
            ->first();

        if (!$assignedTraining) {
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

            // if ($trainingAssigned['status'] == true) {
            //     return true;
            // } else {
            //     return false;
            // }
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
