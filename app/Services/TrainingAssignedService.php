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

    public function sendTrainingEmail($campData, $trainings = null, $trainingIndex = null, $lastIndex = null)
    {
        // Fetch user credentials
        $userCredentials = DB::table('learnerloginsession')
            ->where('email', $campData['user_email'])
            ->first();

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

        if ($trainingIndex !== null) {
            if ($trainingIndex === $lastIndex) {
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
                        'msg' => 'Training assigned and email sent'
                    ];
                }
            }
            return;
        } else {
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
                    'msg' => 'Training assigned and email sent'
                ];
            }
        }
    }

    public function assignAnotherTraining($userLogin, $campData, $training = null, $trainings = null, $trainingIndex = null, $lastIndex = null)
    {

        // Insert into training_assigned_users table

        $res2 = DB::table('training_assigned_users')->insert($campData);

        if (!$res2) {
            return [
                'status' => 0,
                'msg' => 'Failed to assign another training'
            ];
        }

        // echo "user created successfully";

        $learnSiteAndLogo = checkWhitelabeled($campData['company_id']);
        $token = encrypt($campData['user_email']);
        // $token = Hash::make($campaign->user_email);
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

        if ($trainingIndex !== null) {

            if ($trainingIndex === $lastIndex) {
                $allAssignedTrainings = TrainingAssignedUser::with('trainingData', 'trainingGame')->where('user_email', $campData['user_email'])->get();

                $trainingNames = $allAssignedTrainings->map(function ($training) {
                    if ($training->training_type == 'games') {
                        return $training->trainingGame->name;
                    }
                    return $training->trainingData->name;
                });

                $isMailSent = Mail::to($userLogin->login_username)->send(new TrainingAssignedEmail($mailData, $trainingNames));

                if(!$isMailSent) {
                    return [
                        'status' => 0,
                        'msg' => 'Training not sent'
                    ];
                }
                return [
                    'status' => 1,
                    'msg' => 'Training assigned and email sent'
                ];
            }
            return;
        } else {
            $allAssignedTrainings = TrainingAssignedUser::with('trainingData', 'trainingGame')->where('user_email', $campData['user_email'])->get();

            $trainingNames = $allAssignedTrainings->map(function ($training) {
                if ($training->training_type == 'games') {
                    return $training->trainingGame->name; // Return the name from trainingGame
                }
                return $training->trainingData->name; // Return the name from trainingData
            });

            $isMailSent = Mail::to($userLogin->login_username)->send(new TrainingAssignedEmail($mailData, $trainingNames));

            if(!$isMailSent) {
                return [
                    'status' => 0,
                    'msg' => 'Training not sent'
                ];
            }
            return [
                'status' => 1,
                'msg' => 'Training assigned and email sent'
            ];
        }
    }

    public function assignNewTraining($campData, $training = null, $trainings = null, $trainingIndex = null, $lastIndex = null)
    {
        DB::table('training_assigned_users')
            ->insert($campData);

        echo "New training assigned successfully \n";

        if ($trainingIndex !== null) {

            if ($trainingIndex === $lastIndex) {
                $allAssignedTrainings = TrainingAssignedUser::with('trainingData', 'trainingGame')->where('user_email', $campData['user_email'])->get();

                $trainingNames = $allAssignedTrainings->map(function ($training) {
                    if ($training->training_type == 'games') {
                        return $training->trainingGame->name;
                    }
                    return $training->trainingData->name;
                });

                $learnSiteAndLogo = checkWhitelabeled($campData['company_id']);

                // $token = substr(encrypt($campaign->user_email), 0, 200);
                $token = encrypt($campData['user_email']);
                // $token = Hash::make($campaign->user_email);

                $learning_dashboard_link = env('SIMUPHISH_LEARNING_URL') . '/training-dashboard/' . $token;
                DB::table('learnerloginsession')
                    ->insert([
                        'token' => $token,
                        'email' => $campData['user_email'],
                        'expiry' => now()->addHours(24), // Ensure it expires in 24 hours
                        'created_at' => now(), // Ensure ordering works properly
                    ]);

                $passwordGenLink = env('APP_URL') . '/learner/create-password/' . $token;

                $mailData = [
                    'user_name' => $campData['user_name'],
                    'password_create_link' => $learning_dashboard_link,
                    'company_name' => $learnSiteAndLogo['company_name'],
                    'company_email' => $learnSiteAndLogo['company_email'],
                    'learning_site' => $learning_dashboard_link,
                    'logo' => $learnSiteAndLogo['logo']
                ];

                $isMailSent = Mail::to($campData['user_email'])->send(new AssignTrainingWithPassResetLink($mailData, $trainingNames));

                if(!$isMailSent) {
                    return [
                        'status' => 0,
                        'msg' => 'Training not sent'
                    ];
                }

                return [
                    'status' => 1,
                    'msg' => 'Training assigned and email sent'
                ];
            }
            return;
        } else {
            $allAssignedTrainings = TrainingAssignedUser::with('trainingData', 'trainingGame')->where('user_email', $campData['user_email'])->get();
            $trainingNames = $allAssignedTrainings->map(function ($training) {
                if ($training->training_type == 'games') {
                    return $training->trainingGame->name;
                }
                return $training->trainingData->name;
            });

            $learnSiteAndLogo = checkWhitelabeled($campData['company_id']);

            $token = encrypt($campData['user_email']);
            // $token = Hash::make($campaign->user_email);

            $learning_dashboard_link = env('SIMUPHISH_LEARNING_URL') . '/training-dashboard/' . $token;
            DB::table('learnerloginsession')
                ->insert([
                    'token' => $token,
                    'email' => $campData['user_email'],
                    "expiry" => now()->addHours(24), // Ensure it expires in 24 hours
                    'created_at' => now(), // Ensure ordering works properly
                ]);

            $passwordGenLink = env('APP_URL') . '/learner/create-password/' . $token;

            $mailData = [
                'user_name' => $campData['user_name'],
                'password_create_link' => $learning_dashboard_link,
                'company_name' => $learnSiteAndLogo['company_name'],
                'company_email' => $learnSiteAndLogo['company_email'],
                'learning_site' => $learnSiteAndLogo['learn_domain'],
                'logo' => $learnSiteAndLogo['logo']
            ];

            $isMailSent = Mail::to($campData['user_email'])->send(new AssignTrainingWithPassResetLink($mailData, $trainingNames));

            if(!$isMailSent) {
                return [
                    'status' => 0,
                    'msg' => 'Training not sent'
                ];
            }

            return [
                'status' => 1,
                'msg' => 'Training assigned and email sent'
            ];
        }
    }
}