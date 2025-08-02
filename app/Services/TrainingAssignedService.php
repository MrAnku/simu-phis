<?php

namespace App\Services;

use App\Models\WhiteLabelledSmtp;
use Illuminate\Support\Facades\DB;
use App\Mail\TrainingAssignedEmail;
use App\Models\ScormAssignedUser;
use App\Models\TrainingAssignedUser;
use App\Models\WhiteLabelledCompany;
use Illuminate\Support\Facades\Mail;
use App\Services\CheckWhitelabelService;

class TrainingAssignedService
{
    public function sendTrainingEmail($campData)
    {
        // $learnSiteAndLogo = checkWhitelabeled($campData['company_id']);
        $token = encrypt($campData['user_email']);

        $learning_dashboard_link = env('SIMUPHISH_LEARNING_URL') . '/training-dashboard/' . $token;
        $learn_domain = env('SIMUPHISH_LEARNING_URL') . '/';
        $companyName = env('APP_NAME');
        $companyLogo = env('CLOUDFRONT_URL') . "/assets/images/simu-logo-dark.png";
        $companyEmail = env('MAIL_FROM_ADDRESS');

        // Check if the company is whitelabeled
        $isWhitelabeled = new CheckWhitelabelService($campData['company_id']);
        if ($isWhitelabeled->isCompanyWhitelabeled()) {

            $whitelabelData = $isWhitelabeled->getWhiteLabelData();

            $learning_dashboard_link = "https://" . $whitelabelData->learn_domain . '/training-dashboard/' . $token;

            $learn_domain = "https://" . $whitelabelData->learn_domain . '/';
            $companyName = $whitelabelData->company_name;
            $companyLogo = env('CLOUDFRONT_URL') . $whitelabelData->dark_logo;
            $companyEmail = $whitelabelData->company_email;

            $isWhitelabeled->updateSmtpConfig();
        }


        DB::table('learnerloginsession')
            ->insert([
                'token' => $token,
                'email' => $campData['user_email'],
                'expiry' => now()->addHours(24), // Ensure it expires in 24 hours
                'created_at' => now(), // Ensure ordering works properly
            ]);
        $mailData = [
            'user_name' => $campData['user_name'],
            'company_name' => $companyName,
            'company_email' => $companyEmail,
            'learning_site' =>  $learning_dashboard_link,
            'logo' => $companyLogo,
            'company_id' => $campData['company_id'],
            'learn_domain' => $learn_domain,
        ];

        $allAssignedTrainings = TrainingAssignedUser::with('trainingData', 'trainingGame')->where('user_email', $campData['user_email'])->get();

        $scormTrainings = ScormAssignedUser::with('scormTrainingData')->where('user_email', $campData['user_email'])->get();

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


        $isMailSent = Mail::to($campData['user_email'])->send(new TrainingAssignedEmail($mailData, $trainingNames));

        if ($isMailSent) {
            return [
                'status' => 1,
                'msg' => 'Training assigned and email sent' . "\n"
            ];
        }
    }

    public function assignNewTraining($campData)
    {
        DB::table('training_assigned_users')
            ->insert($campData);

        return [
            'status' => 1,
            'msg' => "New training assigned successfully \n"
        ];
    }

    public function assignNewScormTraining($campData)
    {
        DB::table('scorm_assigned_users')
            ->insert($campData);

        return [
            'status' => 1,
            'msg' => "Scorm assigned successfully \n"
        ];
    }

    public function assignNewBlueCollarScormTraining($campData)
    {
        DB::table('blue_collar_scorm_assigned_users')
            ->insert($campData);

        return [
            'status' => 1,
            'msg' => "New training assigned successfully \n"
        ];
    }
}
