<?php

namespace App\Services;

use App\Mail\AssignTrainingWithPassResetLink;
use App\Mail\TrainingAssignedEmail;
use App\Models\CampaignReport;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TrainingAssignedService
{
    public function sendTrainingEmail($campData)
    {
        $learnSiteAndLogo = checkWhitelabeled($campData['company_id']);
        $token = encrypt($campData['user_email']);
        // $token = Hash::make($campData['user_email']);
        $learning_dashboard_link = env('SIMUPHISH_LEARNING_URL') . '/training-dashboard/' . $token;
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
            'logo' => $learnSiteAndLogo['logo']
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
