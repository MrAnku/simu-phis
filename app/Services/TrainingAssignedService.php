<?php

namespace App\Services;

use App\Models\WhiteLabelledSmtp;
use Illuminate\Support\Facades\DB;
use App\Mail\TrainingAssignedEmail;
use App\Models\TrainingAssignedUser;
use App\Models\WhiteLabelledCompany;
use Illuminate\Support\Facades\Mail;

class TrainingAssignedService
{
    public function sendTrainingEmail($campData)
    {
        $learnSiteAndLogo = checkWhitelabeled($campData['company_id']);
        $token = encrypt($campData['user_email']);

        $iswhitelabelled = WhiteLabelledCompany::where('company_id', $campData['company_id'])
            ->where('approved_by_partner', 1)
            ->where('service_status', 1)
            ->first();
        if ($iswhitelabelled) {
            $smtp =  WhiteLabelledSmtp::where('company_id', $campData['company_id'])
                ->first();
            config([
                'mail.mailers.smtp.host' => $smtp->smtp_host,
                'mail.mailers.smtp.username' => $smtp->smtp_username,
                'mail.mailers.smtp.password' => $smtp->smtp_password,
                'mail.from.address' => $smtp->from_address,
                'mail.from.name' => $smtp->from_name,
            ]);

            $learning_dashboard_link = $iswhitelabelled->learn_domain . '/training-dashboard/' . $token;
            $learn_domain = $iswhitelabelled->learn_domain;
        } else {
            $learning_dashboard_link = env('SIMUPHISH_LEARNING_URL') . '/training-dashboard/' . $token;
            $learn_domain = 'https://learn.simuphish.com/';
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
            'company_name' => $learnSiteAndLogo['company_name'],
            'company_email' => $learnSiteAndLogo['company_email'],
            'learning_site' =>  $learning_dashboard_link,
            'logo' => $learnSiteAndLogo['logo'],
            'company_id' => $campData['company_id'],
            'learn_domain' => $learn_domain,
        ];

        $allAssignedTrainings = TrainingAssignedUser::with('trainingData', 'trainingGame')->where('user_email', $campData['user_email'])->get();

        $trainingNames = $allAssignedTrainings->map(function ($training) {
            if ($training->training_type == 'games') {
                return $training->trainingGame->name;
            }
            return $training->trainingData->name;
        });

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
}