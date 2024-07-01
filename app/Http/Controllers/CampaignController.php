<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignLive;
use App\Models\CampaignReport;
use App\Models\PhishingEmail;
use App\Models\TrainingModule;
use App\Models\UsersGroup;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CampaignController extends Controller
{
    //
    public function index()
    {

        $companyId = Auth::user()->company_id;
        $allCamps = Campaign::where('company_id', $companyId)->get();

        foreach ($allCamps as $campaign) {
            switch ($campaign->status) {
                case 'pending':
                    $campaign->status_button = '<button type="button" class="btn btn-warning rounded-pill btn-wave waves-effect waves-light">Pending</button>';
                    $campaign->reschedule_btn = '<button class="btn btn-icon btn-warning btn-wave waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#reschedulemodal" title="Re-Schedule" onclick="reschedulecampid(\'' . e($campaign->id) . '\')"><i class="bx bx-time-five"></i></button>';
                    break;

                case 'running':
                    $campaign->status_button = '<button type="button" class="btn btn-success rounded-pill btn-wave waves-effect waves-light">Running</button>';
                    break;

                case 'Not Scheduled':
                    $campaign->status_button = '<button type="button" class="btn btn-warning rounded-pill btn-wave waves-effect waves-light">Not Scheduled</button>';
                    $campaign->reschedule_btn = '<button class="btn btn-icon btn-warning btn-wave waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#reschedulemodal" title="Re-Schedule" onclick="reschedulecampid(\'' . e($campaign->id) . '\')"><i class="bx bx-time-five"></i></button>';
                    $campaign->launch_time = '--';
                    $campaign->camp_name = e($campaign->campaign_name);
                    break;

                default:
                    $campaign->status_button = '<button type="button" class="btn btn-success rounded-pill btn-wave waves-effect waves-light">Completed</button>';
                    $campaign->relaunch_btn = '<button class="btn btn-icon btn-success btn-wave waves-effect waves-light" onclick="relaunch_camp(\'' . e($campaign->id) . '\')" title="Re-Launch"><i class="bx bx-sync"></i></button>';
                    break;
            }

            $usersGroup = UsersGroup::where('group_id', $campaign->users_group)
                ->where('company_id', $companyId)
                ->first();

            $campaign->users_group_name = $usersGroup ? e($usersGroup->group_name) : 'N/A';
        }

        // Fetch users groups and phishing emails, and pass to view
        $usersGroups = $this->fetchUsersGroups();
        $phishingEmails = $this->fetchPhishingEmails();
        $trainingModules = $this->fetchTrainingModules();

        return view('campaigns', compact('allCamps', 'usersGroups', 'phishingEmails', 'trainingModules'));
    }

    public function fetchUsersGroups()
    {
        $companyId = Auth::user()->company_id;
        return UsersGroup::where('company_id', $companyId)->get();
    }

    public function fetchPhishingEmails()
    {
        $companyId = Auth::user()->company_id;
        return PhishingEmail::where('company_id', $companyId)
            ->orWhere('company_id', 'default')
            ->get();
    }

    public function fetchTrainingModules()
    {
        $companyId = Auth::user()->company_id;
        return TrainingModule::where('company_id', $companyId)
            ->orWhere('company_id', 'default')
            ->get();
    }

    public function createCampaign(Request $request)
    {
        $campaignType = $request->input('campaign_type');
        $campName = $request->input('camp_name');
        $usersGroup = $request->input('users_group');
        $trainingMod = $request->input('training_mod');
        $trainingLang = $request->input('trainingLang');
        $emailLang = $request->input('email_lang');
        $phishMaterial = $request->input('phish_material');
        $launchTime = $request->input('launch_time');
        $launchType = $request->input('schType');
        $frequency = $request->input('emailFreq');
        $expAfter = $request->input('expire_after');
        $companyId = auth()->user()->company_id; // Assuming the company ID is retrieved from the authenticated user

        $phishingEmail = PhishingEmail::where('id', $phishMaterial)
            ->where(function ($query) {
                $query->where('senderProfile', '0')
                    ->orWhere('website', '0');
            })
            ->first();

        if ($phishingEmail) {
            return response()->json(['message' => 'Sender profile or Website is not associated with the selected phishing email template'], 400);
        }

        $campId = generateRandom(); // Assuming you have a method to generate a random ID

        if ($launchType == 'immediately') {
            $scheduledDate = Carbon::createFromFormat("m/d/Y H:i", $launchTime);
            $currentDateTime = Carbon::now();
            $launchTimeFormatted = $scheduledDate->format("m/d/Y g:i A");

            $users = User::where('group_id', $usersGroup)->get();

            if ($users->isEmpty()) {
                return response()->json(['message' => 'No employees available in this group'], 400);
            }

            foreach ($users as $user) {
                CampaignLive::create([
                    'campaign_id' => $campId,
                    'campaign_name' => $campName,
                    'user_id' => $user->id,
                    'user_name' => $user->user_name,
                    'user_email' => $user->user_email,
                    'training_module' => $trainingMod,
                    'training_lang' => $trainingLang,
                    'launch_time' => $launchTimeFormatted,
                    'phishing_material' => $phishMaterial,
                    'email_lang' => $emailLang,
                    'sent' => '0',
                    'company_id' => $companyId,
                ]);
            }

            CampaignReport::create([
                'campaign_id' => $campId,
                'campaign_name' => $campName,
                'campaign_type' => $campaignType,
                'status' => 'running',
                'email_lang' => $emailLang,
                'scheduled_date' => $launchTimeFormatted,
                'company_id' => $companyId,
            ]);

            $cstatus = 'running';

            if ($frequency == 'weekly') {
                $scheduledDate->addWeek();
                $launchTimeFormatted = $scheduledDate->format("m/d/Y g:i A");
                $cstatus = 'pending';
            } elseif ($frequency == 'monthly') {
                $scheduledDate->addMonth();
                $launchTimeFormatted = $scheduledDate->format("m/d/Y g:i A");
                $cstatus = 'pending';
            } elseif ($frequency == 'quaterly') {
                $scheduledDate->addMonths(3);
                $launchTimeFormatted = $scheduledDate->format("m/d/Y g:i A");
                $cstatus = 'pending';
            }

            Campaign::create([
                'campaign_id' => $campId,
                'campaign_name' => $campName,
                'campaign_type' => $campaignType,
                'users_group' => $usersGroup,
                'training_module' => $trainingMod,
                'training_lang' => $trainingLang,
                'phishing_material' => $phishMaterial,
                'email_lang' => $emailLang,
                'launch_time' => $launchTimeFormatted,
                'launch_type' => $launchType,
                'email_freq' => $frequency,
                'startTime' => '00:00:00',
                'endTime' => '00:00:00',
                'expire_after' => $expAfter,
                'status' => $cstatus,
                'company_id' => $companyId,
            ]);

            return response()->json(['message' => 'Campaign created and running']);
        }

        if ($launchType == 'scheduled') {
            $betweenDays = $request->input('schBetRange');
            $startTime = $request->input('schTimeStart');
            $endTime = $request->input('schTimeEnd');
            $timeZone = $request->input('schTimeZone');

            $launchTime = generateRandomDate($betweenDays, $startTime, $endTime);

            Campaign::create([
                'campaign_id' => $campId,
                'campaign_name' => $campName,
                'campaign_type' => $campaignType,
                'users_group' => $usersGroup,
                'training_module' => $trainingMod,
                'training_lang' => $trainingLang,
                'phishing_material' => $phishMaterial,
                'email_lang' => $emailLang,
                'launch_time' => $launchTime,
                'launch_type' => $launchType,
                'email_freq' => $frequency,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'expire_after' => $expAfter,
                'status' => 'pending',
                'company_id' => $companyId,
            ]);

            CampaignReport::create([
                'campaign_id' => $campId,
                'campaign_name' => $campName,
                'campaign_type' => $campaignType,
                'status' => 'pending',
                'email_lang' => $emailLang,
                'scheduled_date' => $launchTime,
                'company_id' => $companyId,
            ]);

            return response()->json(['message' => 'Campaign created successfully']);
        }

        if ($launchType == 'schLater') {
            $launchTime = Carbon::createFromFormat("m/d/Y H:i", $launchTime)->format("m/d/Y g:i A");

            Campaign::create([
                'campaign_id' => $campId,
                'campaign_name' => $campName,
                'campaign_type' => $campaignType,
                'users_group' => $usersGroup,
                'training_module' => $trainingMod,
                'training_lang' => $trainingLang,
                'phishing_material' => $phishMaterial,
                'email_lang' => $emailLang,
                'launch_time' => $launchTime,
                'launch_type' => $launchType,
                'status' => 'Not Scheduled',
                'company_id' => $companyId,
            ]);

            return response()->json(['message' => 'Campaign scheduled successfully']);
        }
    }

    
}
