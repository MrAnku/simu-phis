<?php

namespace App\Services;

use App\Mail\SecurityAwarenessMail;
use App\Models\Users;
use App\Models\ScormAssignedUser;
use App\Models\ScormTraining;
use App\Models\TrainingAssignedUser;
use App\Models\TrainingModule;
use App\Models\UserMember;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Throwable;

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

        $trainingNames = []; // Initialize before loops to collect all training names

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

                    $trainingAssigned = $trainingAssignedService->assignNewTraining($campData);

                    if ($trainingAssigned['status'] == 1) {
                        // echo $trainingAssigned['msg'];
                    }

                    $module = TrainingModule::find($training);
                    // Audit log
                    audit_log(
                        $campaign->company_id,
                        $campaign->user_email,
                        null,
                        'TRAINING_ASSIGNED',
                        "{$module->name} has been assigned to {$campaign->user_email}",
                        'normal'
                    );
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

                // Append training name to array
                $module = TrainingModule::find($training);
                if ($module) {
                    $trainingNames[] = $module->name;
                }
            }
        }

        if (!empty($scormTrainings)) {
            foreach ($scormTrainings as $training) {
                $assignedTraining = ScormAssignedUser::where('user_email', $user_email)
                    ->where('scorm', $training)
                    ->first();

                if (!$assignedTraining) {
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
                    $trainingAssigned = $trainingAssignedService->assignNewScormTraining($campData);

                    if ($trainingAssigned['status'] == 1) {
                        // echo $trainingAssigned['msg'];
                    }

                    $scorm = ScormTraining::find($training);
                    // Audit log
                    audit_log(
                        $campaign->company_id,
                        $campaign->user_email,
                        null,
                        'SCORM_ASSIGNED',
                        "{$scorm->name} has been assigned to {$campaign->user_email}",
                        'normal'
                    );
                }

                // Append scorm training name to array
                $module = ScormTraining::find($training);
                if($module) {
                    $trainingNames[] = $module->name;
                }
            }
        }

        //send mail to user
        $campData = [
            'user_name' => $campaign->user_name,
            'user_email' => $user_email,
            'company_id' => $campaign->company_id,
            'training_names' => $trainingNames,
            'training_due_date' => now()->addDays($campaign->days_until_due)->toDateString()
        ];
        $isMailSent = $trainingAssignedService->sendTrainingEmail($campData, collect($trainingNames));

        if ($isMailSent['status'] == true) {
            // send security awareness mail to members of this user
            $userMembers = UserMember::where('user_id', $campaign->user_id)->get();
            if ($userMembers && $userMembers->count() > 0) {
                foreach ($userMembers as $userMember) {
                    try {
                        Mail::to($userMember->email)->send(new SecurityAwarenessMail($userMember->email, $userMember->name));
                    } catch (Throwable $e) {
                        // Log and continue with next recipient to avoid stopping the whole process
                        Log::error('Failed sending SecurityAwarenessMail to user member' . $e->getMessage());
                        continue;
                    }
                }
            }
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

        $trainingNames = []; // Initialize array to collect training names

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

                $trainingAssigned = $trainingAssignedService->assignNewTraining($campData);

                if ($trainingAssigned['status'] == 1) {
                    // echo $trainingAssigned['msg'];
                }

                $module = TrainingModule::find($campaign->training_module);
                // Audit log
                audit_log(
                    $campaign->company_id,
                    $campaign->user_email,
                    null,
                    'TRAINING_ASSIGNED',
                    "{$module->name} has been assigned to {$campaign->user_email}",
                    'normal'
                );
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

            // Append training module name to array
            $module = TrainingModule::find($campaign->training_module);
            if ($module) {
                $trainingNames[] = $module->name;
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

                $trainingAssigned = $trainingAssignedService->assignNewScormTraining($campData);

                if ($trainingAssigned['status'] == 1) {
                    // echo $trainingAssigned['msg'];
                }

                $scorm = ScormTraining::find($campaign->scorm_training);
                // Audit log
                audit_log(
                    $campaign->company_id,
                    $campaign->user_email,
                    null,
                    'SCORM_ASSIGNED',
                    "{$scorm->name} has been assigned to {$campaign->user_email}",
                    'normal'
                );
            }

            // Append scorm training name to array
            $scorm = ScormTraining::find($campaign->scorm_training);
            if ($scorm) {
                $trainingNames[] = $scorm->name;
            }
        }

        //send mail to user
        $campData = [
            'user_name' => $campaign->user_name,
            'user_email' => $user_email,
            'company_id' => $campaign->company_id,
            'training_names' => $trainingNames,
            'training_due_date' => now()->addDays($campaign->days_until_due)->toDateString()
        ];
        $isMailSent = $trainingAssignedService->sendTrainingEmail($campData, collect($trainingNames));

        if ($isMailSent['status'] == true) {
            // send security awareness mail to members of this user
            $userMembers = UserMember::where('user_id', $campaign->user_id)->get();
            if ($userMembers && $userMembers->count() > 0) {
                foreach ($userMembers as $userMember) {
                    try {
                        Mail::to($userMember->email)->send(new SecurityAwarenessMail($userMember->email, $userMember->name));
                    } catch (Throwable $e) {
                        // Log and continue with next recipient to avoid stopping the whole process
                        Log::error('Failed sending SecurityAwarenessMail to user member' . $e->getMessage());
                        continue;
                    }
                }
            }
            return true;
        } else {
            return false;
        }
    }
}
