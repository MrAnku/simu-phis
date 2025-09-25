<?php

namespace App\Services;

use App\Models\BlueCollarScormAssignedUser;
use App\Models\BlueCollarTrainingUser;
use App\Models\ScormTraining;
use App\Models\TrainingModule;

class BlueCollarCampTrainingService
{
    public static function assignBlueCollarTraining($campaign, $trainingModules = null, $scormTrainings = null)
    {

        if ($trainingModules !== null || $scormTrainings !== null) {
            return self::assignAllBlueCollarTrainings($campaign, $trainingModules, $scormTrainings);
        } else {
            return self::assignSingleBlueCollarTraining($campaign);
        }
    }

    private static function assignAllBlueCollarTrainings($campaign, $trainingModules = null, $scormTrainings = null)
    {
         // Logic to choose phone field from whatsapp or AI
        if (isset($campaign->to_mobile) && !empty($campaign->to_mobile)) {
            $user_phone = ltrim($campaign->to_mobile, '+');
        } else {
            $user_phone = $campaign->user_phone;
        }

        if (!empty($trainingModules)) {
            foreach ($trainingModules as $training) {

                //check if this training is already assigned to this user
                $assignedTraining = BlueCollarTrainingUser::where('user_whatsapp', $user_phone)
                    ->where('training', $training)
                    ->first();

                if (!$assignedTraining) {
                    //call assignNewTraining from service method
                    $campData = [
                        'campaign_id' => $campaign->campaign_id,
                        'user_id' => $campaign->user_id,
                        'user_name' => $campaign->user_name,
                        'user_whatsapp' => $user_phone,
                        'training' => $training,
                        'training_lang' => $campaign->training_lang,
                        'training_type' => $campaign->training_type,
                        'assigned_date' => now()->toDateString(),
                        'training_due_date' => now()->addDays($campaign->days_until_due)->toDateString(),
                        'company_id' => $campaign->company_id
                    ];

                    BlueCollarTrainingUser::create($campData);

                    $module = TrainingModule::find($training);
                    // Audit log
                    audit_log(
                        $campaign->company_id,
                        $campaign->user_email ?? null,
                        $user_phone,
                        'TRAINING_ASSIGNED',
                        "{$module->name} has been assigned to {$user_phone}",
                        'bluecollar'
                    );
                    echo "New training assigned successfully \n";
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
                $assignedTraining = BlueCollarScormAssignedUser::where('user_whatsapp', $user_phone)
                    ->where('scorm', $training)
                    ->first();

                if (!$assignedTraining) {
                    //call assignNewTraining from service method
                    $campData = [
                        'campaign_id' => $campaign->campaign_id,
                        'user_id' => $campaign->user_id,
                        'user_name' => $campaign->user_name,
                        'user_whatsapp' => $user_phone,
                        'scorm' => $training,
                        'assigned_date' => now()->toDateString(),
                        'scorm_due_date' => now()->addDays($campaign->days_until_due)->toDateString(),
                        'company_id' => $campaign->company_id
                    ];


                    BlueCollarScormAssignedUser::create($campData);

                    $scorm = ScormTraining::find($training);
                    // Audit log
                    audit_log(
                        $campaign->company_id,
                        $campaign->user_email ?? null,
                        $user_phone,
                        'SCORM_ASSIGNED',
                        "{$scorm->name} has been assigned to {$user_phone}",
                        'bluecollar'
                    );
                    echo 'Scorm assigned successfully to ' . $user_phone . "\n";
                }
            }
        }


        $trainingNames = self::getAllTrainingNames($user_phone);

        $trainingNamesString = $trainingNames->implode(', ');

        // Prepare data object/array
        $data = (object)[
            'user_phone' => $user_phone,
            'user_name' => $campaign->user_name,
            'training_names' => $trainingNamesString
        ];

        $blueCollarWhatsappService = new BlueCollarWhatsappService($campaign->company_id);

        $whatsapp_response = $blueCollarWhatsappService->sendTrainingAssign($data);

        if ($whatsapp_response->successful()) {
            return true;
        } else {
            return false;
        }
    }

    private static function assignSingleBlueCollarTraining($campaign)
    {
        // Logic to choose phone field from whatsapp or AI
        if (isset($campaign->to_mobile) && !empty($campaign->to_mobile)) {
            $user_phone = ltrim($campaign->to_mobile, '+');
        } else {
            $user_phone = $campaign->user_phone;
        }
        
        if ($campaign->training_module !== null) {
            $assignedTrainingModule = BlueCollarTrainingUser::where('user_whatsapp', $user_phone)
                ->where('training', $campaign->training_module)
                ->first();

            if (!$assignedTrainingModule) {
                //call assignNewTraining from service method
                $campData = [
                    'campaign_id' => $campaign->campaign_id,
                    'user_id' => $campaign->user_id,
                    'user_name' => $campaign->user_name,
                    'user_whatsapp' => $user_phone,
                    'training' => $campaign->training_module,
                    'training_lang' => $campaign->training_lang,
                    'training_type' => $campaign->training_type,
                    'assigned_date' => now()->toDateString(),
                    'training_due_date' => now()->addDays($campaign->days_until_due)->toDateString(),
                    'company_id' => $campaign->company_id
                ];
                BlueCollarTrainingUser::create($campData);

                $training = TrainingModule::find($campaign->training_module);
                audit_log(
                    $campaign->company_id,
                    $campaign->user_email ?? null,
                    $user_phone,
                    'TRAINING_ASSIGNED',
                    "{$training->name} has been assigned to {$user_phone}",
                    'bluecollar'
                );

                echo "New training assigned successfully \n";
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
            $assignedTrainingModule = BlueCollarScormAssignedUser::where('user_whatsapp', $user_phone)
                ->where('scorm', $campaign->scorm_training)
                ->first();

            if (!$assignedTrainingModule) {
                //call assignNewTraining from service method
                $campData = [
                    'campaign_id' => $campaign->campaign_id,
                    'user_id' => $campaign->user_id,
                    'user_name' => $campaign->user_name,
                    'user_whatsapp' => $user_phone,
                    'scorm' => $campaign->scorm_training,
                    'assigned_date' => now()->toDateString(),
                    'scorm_due_date' => now()->addDays($campaign->days_until_due)->toDateString(),
                    'company_id' => $campaign->company_id
                ];

                BlueCollarScormAssignedUser::create($campData);

                $scorm = ScormTraining::find($campaign->scorm_training);
                // Audit log
                audit_log(
                    $campaign->company_id,
                    $campaign->user_email ?? null,
                    $user_phone,
                    'SCORM_ASSIGNED',
                    "{$scorm->name} has been assigned to {$user_phone}",
                    'bluecollar'
                );
                echo 'Scorm assigned successfully to ' . $user_phone . "\n";
            }
        }

        $trainingNames = self::getAllTrainingNames($user_phone);

        $trainingNamesString = $trainingNames->implode(', ');

        // Prepare data object/array
        $data = (object)[
            'user_phone' => $user_phone,
            'user_name' => $campaign->user_name,
            'training_names' => $trainingNamesString
        ];

        $blueCollarWhatsappService = new BlueCollarWhatsappService($campaign->company_id);

        $whatsapp_response = $blueCollarWhatsappService->sendTrainingAssign($data);

        if ($whatsapp_response->successful()) {
            return true;
        } else {
            return false;
        }
    }

    private static function getAllTrainingNames($user_phone)
    {
        $allAssignedTrainings = BlueCollarTrainingUser::with('trainingData', 'trainingGame')->where('user_whatsapp', $user_phone)->get();

        $scormTrainings = BlueCollarScormAssignedUser::with('scormTrainingData')->where('user_whatsapp', $user_phone)->get();

        $trainingNames = collect();
        $scormNames = collect();

        if ($allAssignedTrainings->isNotEmpty()) {
            $trainingNames = $allAssignedTrainings->map(function ($training) {
                if ($training->training_type == 'games') {
                    return $training->trainingGame->name;
                }
                return $training->trainingData->name;
            });
        }


        if ($scormTrainings->isNotEmpty()) {
            $scormNames = $scormTrainings->map(function ($training) {

                return $training->scormTrainingData->name;
            });
        }

        $trainingNames = $trainingNames->merge($scormNames)->filter();
        return $trainingNames;
    }
}