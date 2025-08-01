<?php

namespace App\Services;

use App\Models\BlueCollarScormAssignedUser;
use App\Models\BlueCollarTrainingUser;
use App\Models\Users;
use App\Models\ScormAssignedUser;
use Illuminate\Support\Facades\DB;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\Http;

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

                // DB::table('scorm_assigned_users')->insert($campData);

                // echo 'Scorm assigned successfully to ' . $user_email . "\n";
                $trainingAssignedService->assignNewScormTraining($campData);


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

    // Blue Collar Training

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
        $trainingAssignedService = new TrainingAssignedService();

        $user_phone = $campaign->user_phone;

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

                    // $trainingAssignedService->assignNewTraining($campData);

                    DB::table('blue_collar_training_users')
                        ->insert($campData);

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

                    // $trainingAssignedService->assignNewTraining($campData);

                    DB::table('blue_collar_scorm_assigned_users')
                        ->insert($campData);

                    echo 'Scorm assigned successfully to ' . $user_phone . "\n";


                    // if ($trainingAssigned['status'] == true) {
                    //     return true;
                    // } else {
                    //     return false;
                    // }
                }
            }
        }



        //send mail to user
        // $campData = [
        //     'user_name' => $campaign->user_name,
        //     'user_email' => $user_email,
        //     'company_id' => $campaign->company_id
        // ];
        // $isMailSent = $trainingAssignedService->sendTrainingEmail($campData);

        // if ($isMailSent['status'] == true) {
        //     return true;
        // } else {
        //     return false;
        // }

        //    $whatsapp_data = [
        //     "messaging_product" => "whatsapp",
        //     "to" => $campaign->user_phone, // Replace with actual user phone number
        //     "type" => "template",
        //     "template" => [
        //         "name" => "training_message",
        //         "language" => ["code" => "en"],
        //         "components" => [
        //             [
        //                 "type" => "body",
        //                 "parameters" => [
        //                     ["type" => "text", "text" => $campaign->user_name],
        //                     ["type" => "text", "text" => $campaign->trainingData->name],
        //                     ["type" => "text", "text" => env('SIMUPHISH_LEARNING_URL') . "/start-blue-collar-training/" . $token]
        //                 ]
        //             ]
        //         ]
        //     ]
        // ];



        // $whatsapp_response = Http::withHeaders([
        //     "Authorization" => "Bearer {$access_token}",
        //     "Content-Type" => "application/json"
        // ])->withOptions([
        //     'verify' => false
        // ])->post($whatsapp_url, $whatsapp_data);


        // if ($whatsapp_response->successful()) {
        //     log_action("Bluecollar Training Assigned | Training {$campaign->trainingData->name} assigned to {$campaign->user_phone}.", 'employee', 'employee');
        // } else {
        //     log_action("Training assignment failed", 'employee', 'employee');
        // }
    }

    private static function assignSingleBlueCollarTraining($campaign)
    {
        $trainingAssignedService = new TrainingAssignedService();

        $user_phone = $campaign->user_phone;

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

                // $trainingAssignedService->assignNewTraining($campData);
                DB::table('blue_collar_training_users')
                    ->insert($campData);

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

                DB::table('blue_collar_scorm_assigned_users')
                    ->insert($campData);

                echo 'Scorm assigned successfully to ' . $user_phone . "\n";
                return true;


                // if ($trainingAssigned['status'] == true) {
                //     return true;
                // } else {
                //     return false;
                // }
            }
        }



        //send mail to user
        // $campData = [
        //     'user_name' => $campaign->user_name,
        //     'user_email' => $user_email,
        //     'company_id' => $campaign->company_id
        // ];
        // $isMailSent = $trainingAssignedService->sendTrainingEmail($campData);

        // if ($isMailSent['status'] == true) {
        //     return true;
        // } else {
        //     return false;
        // }
    }
}
