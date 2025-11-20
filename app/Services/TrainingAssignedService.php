<?php

namespace App\Services;

use App\Models\ScormAssignedUser;
use App\Models\WhiteLabelledSmtp;
use Illuminate\Support\Facades\DB;
use App\Mail\TrainingAssignedEmail;
use App\Models\TrainingAssignedUser;
use App\Models\WhiteLabelledCompany;
use Illuminate\Support\Facades\Mail;
use App\Services\CheckWhitelabelService;
use App\Models\BlueCollarScormAssignedUser;
use App\Models\BlueCollarTrainingUser;

class TrainingAssignedService
{
    public function sendTrainingEmail($campData)
    {
        // $learnSiteAndLogo = checkWhitelabeled($campData['company_id']);
        $token = encrypt($campData['user_email']);

        DB::table('learnerloginsession')
            ->insert([
                'token' => $token,
                'email' => $campData['user_email'],
                'expiry' => now()->addHours(24), // Ensure it expires in 24 hours
                'created_at' => now(), // Ensure ordering works properly
            ]);

        $branding = new CheckWhitelabelService($campData['company_id']);
        $learning_dashboard_link = $branding->learningPortalDomain() . '/training-dashboard/' . $token;
        $learn_domain = $branding->learningPortalDomain() . '/';
        $companyName = $branding->companyName();
        $companyLogo = $branding->companyDarkLogo();
        if ($branding->isCompanyWhitelabeled()) {

            $branding->updateSmtpConfig();
        } else {
            //reset the smtp config to default if not whitelabeled
            $branding->clearSmtpConfig();
        }
        
        $mailData = [
            'user_name' => $campData['user_name'],
            'company_name' => $companyName,
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
                    return $training->trainingGame?->name;
                }
                return $training->trainingData?->name;
            });
        }


        if ($scormTrainings->isNotEmpty()) {
            $scormNames = $scormTrainings->map(function ($training) {

                return $training->scormTrainingData?->name;
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
        TrainingAssignedUser::create($campData);

        return [
            'status' => 1,
            'msg' => "New training assigned successfully \n"
        ];
    }

    public function assignNewScormTraining($campData)
    {
        ScormAssignedUser::create($campData);

        return [
            'status' => 1,
            'msg' => "Scorm assigned successfully \n"
        ];
    }

    public function assignNewBlueCollarScormTraining($campData)
    {
        BlueCollarScormAssignedUser::create($campData);

        return [
            'status' => 1,
            'msg' => "New training assigned successfully \n"
        ];
    }

    public function assignNewBlueCollarTraining($campData)
    {
        BlueCollarTrainingUser::create($campData);

        return [
            'status' => 1,
            'msg' => "New training assigned successfully \n"
        ];
    }
}
