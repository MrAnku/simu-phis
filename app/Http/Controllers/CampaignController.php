<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Users;
use App\Models\Campaign;
use App\Models\UsersGroup;
use Illuminate\Support\Str;
use App\Models\CampaignLive;
use Illuminate\Http\Request;
use App\Models\PhishingEmail;
use App\Models\CampaignReport;
use App\Models\TrainingModule;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;

class CampaignController extends Controller
{
    //
    public function index()
    {

        $companyId = Auth::user()->company_id;
        $allCamps = Campaign::where('company_id', $companyId)->get();

        $lastCampaign = Campaign::orderBy('id', 'desc')->first();
        if ($lastCampaign) {
            $lastDeliveryDate = Carbon::parse($lastCampaign->delivery_date);
            $currentDate = Carbon::now();
            if ($currentDate->diffInDays($lastDeliveryDate) < 1) {
                $daysSinceLastDelivery = 1;
            } else {

                $daysSinceLastDelivery = $currentDate->diffInDays($lastDeliveryDate);
            }
        } else {
            // Handle the case where no campaign deliveries exist
            $daysSinceLastDelivery = 0;
        }

        $all_sent = CampaignLive::where('sent', 1)->where('company_id', $companyId)->count();
        $mail_open = CampaignLive::where('mail_open', 1)->where('company_id', $companyId)->count();

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
                    $campaign->relaunch_btn = '<button class="btn btn-icon btn-success btn-wave waves-effect waves-light" onclick="relaunch_camp(\'' . base64_encode($campaign->campaign_id) . '\')" title="Re-Launch"><i class="bx bx-sync"></i></button>';
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


        return view('campaigns', compact(
            'allCamps',
            'usersGroups',
            'phishingEmails',
            'trainingModules',
            'daysSinceLastDelivery',
            'all_sent',
            'mail_open'
        ));

        
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
        $trainingType = $request->input('training_type');
        $emailLang = $request->input('email_lang');
        $phishMaterial = $request->input('phish_material');
        $launchTime = $request->input('launch_time');
        $launchType = $request->input('schType');
        $timeZone = $request->input('schTimeZone');
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
            return response()->json(['status' => 0, 'msg' => 'Sender profile or Website is not associated with the selected phishing email template']);
        }

        $campId = generateRandom(); // Assuming you have a method to generate a random ID

        if ($launchType == 'immediately') {
            $scheduledDate = Carbon::createFromFormat("m/d/Y H:i", $launchTime);
            $currentDateTime = Carbon::now();
            $launchTimeFormatted = $scheduledDate->format("m/d/Y g:i A");

            $users = User::where('group_id', $usersGroup)->get();

            if ($users->isEmpty()) {
                return response()->json(['status' => 0, 'msg' => 'No employees available in this group'], 400);
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
                    'training_type' => $trainingType,
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
                'training_lang' => $trainingLang,
                'scheduled_date' => $launchTimeFormatted,
                'company_id' => $companyId,
            ]);



            Campaign::create([
                'campaign_id' => $campId,
                'campaign_name' => $campName,
                'campaign_type' => $campaignType,
                'users_group' => $usersGroup,
                'training_module' => $trainingMod,
                'training_lang' => $trainingLang,
                'training_type' => $trainingType,
                'phishing_material' => $phishMaterial,
                'email_lang' => $emailLang,
                'launch_time' => $launchTimeFormatted,
                'launch_type' => $launchType,
                'email_freq' => $frequency,
                'startTime' => '00:00:00',
                'endTime' => '00:00:00',
                'timeZone' => $timeZone,
                'expire_after' => $expAfter,
                'status' => 'running',
                'company_id' => $companyId,
            ]);

            log_action('Email campaign created');

            return response()->json(['status' => 1, 'msg' => 'Campaign created and running!']);
        }

        if ($launchType == 'scheduled') {
            $schedule_date = $request->input('schBetRange');
            $startTime = $request->input('schTimeStart');
            $endTime = $request->input('schTimeEnd');
            $timeZone = $request->input('schTimeZone', 'Asia/Kolkata');

            $launchTime = $this->generateRandomDate($schedule_date, $startTime, $endTime, $timeZone);

            Campaign::create([
                'campaign_id' => $campId,
                'campaign_name' => $campName,
                'campaign_type' => $campaignType,
                'users_group' => $usersGroup,
                'training_module' => $trainingMod,
                'training_lang' => $trainingLang,
                'training_type' => $trainingType,
                'phishing_material' => $phishMaterial,
                'email_lang' => $emailLang,
                'launch_time' => $launchTime,
                'launch_type' => $launchType,
                'email_freq' => $frequency,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'timeZone' => $timeZone,
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
                'training_lang' => $trainingLang,
                'scheduled_date' => $launchTime,
                'company_id' => $companyId,
            ]);

            log_action('Email campaign scheduled');

            return response()->json(['status' => 1, 'msg' => 'Campaign created and scheduled!']);
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
                'training_type' => $trainingType,
                'phishing_material' => $phishMaterial,
                'email_lang' => $emailLang,
                'launch_time' => $launchTime,
                'launch_type' => $launchType,
                'status' => 'Not Scheduled',
                'company_id' => $companyId,
            ]);

            log_action('Email campaign created for schedule later');

            return response()->json(['status' => 1, 'msg' => 'Campaign scheduled successfully!']);
        }
    }

    public function generateRandomDate($dateS, $timeS, $timeE, $timeZone = 'Asia/Kolkata')
    {
        $dateString = $dateS;
        $timeStart = $timeS;
        $timeEnd = $timeE;

        // Create a Carbon instance from the date string
        $date = Carbon::createFromFormat('Y-m-d', $dateString, $timeZone);

        // Parse the start and end times
        $startTime = Carbon::createFromFormat('H:i', $timeStart, $timeZone);
        $endTime = Carbon::createFromFormat('H:i', $timeEnd, $timeZone);

        // Calculate the total minutes in the range
        $totalMinutes = $startTime->diffInMinutes($endTime);

        // Generate a random number of minutes to add to the start time
        $randomMinutes = rand(0, $totalMinutes);

        // Add the random minutes to the start time
        $randomTime = $startTime->copy()->addMinutes($randomMinutes);

        // Combine the date with the random time
        $date->setTime($randomTime->hour, $randomTime->minute);

        // Format the date and time as requested
        $formattedDate = $date->format('m/d/Y h:i A');

        return $formattedDate;
    }

    public function deleteCampaign(Request $request)
    {
        $campid = $request->input('campid');

       

        $res1 = Campaign::where('campaign_id', $campid)->delete();
        $res2 = CampaignLive::where('campaign_id', $campid)->delete();
        $res3 = CampaignReport::where('campaign_id', $campid)->delete();

        log_action('Email campaign deleted');

        return response()->json(['status' => 1, 'msg' => 'Campaign deleted successfully']);

       
    }

    public function relaunchCampaign(Request $request)
    {

        $campid = base64_decode($request->campid);
        $dateTime = Carbon::now();
        $formattedDateTime = $dateTime->format('m/d/Y g:i A');

        $company_id = Auth::user()->company_id;

        Campaign::where('campaign_id', $campid)->update([
            'launch_time' => $formattedDateTime,
            'status' => 'running'
        ]);

        CampaignReport::where('campaign_id', $campid)
            ->where('company_id', $company_id)
            ->update([
                'scheduled_date' => $formattedDateTime,
                'status' => 'running',
                'emails_delivered' => 0,
                'emails_viewed' => 0,
                'payloads_clicked' => 0,
                'emp_compromised' => 0,
                'email_reported' => 0,
                'training_assigned' => 0,
                'training_completed' => 0,
            ]);

        //deleting old campaign live
        CampaignLive::where('campaign_id', $campid)->delete();

        $campaign = Campaign::where('campaign_id', $campid)->first();

        $users = User::where('group_id', $campaign->users_group)->get();

        if ($users->isEmpty()) {
        }

        foreach ($users as $user) {
            CampaignLive::create([
                'campaign_id' => $campaign->campaign_id,
                'campaign_name' => $campaign->campaign_name,
                'user_id' => $user->id,
                'user_name' => $user->user_name,
                'user_email' => $user->user_email,
                'training_module' => $campaign->training_module,
                'training_lang' => $campaign->training_module,
                'launch_time' => $formattedDateTime,
                'phishing_material' => $campaign->phishing_material,
                'email_lang' => $campaign->email_lang,
                'sent' => '0',
                'company_id' => $company_id,
            ]);
        }

        log_action('Email campaign relaunched');
    }

    public function fetchPhishData(Request $request)
    {
        $website = $request->input('website');
        $senderProfile = $request->input('senderProfile');

        $phishData = [];

        // Fetch website data
        $websiteData = DB::table('phishing_websites')->where('id', $website)->first();
        if ($websiteData) {
            $phishData['website_name'] = $websiteData->name;
            $phishData['website_url'] = $websiteData->domain;
            $phishData['website_file'] = $websiteData->file;
        } else {
            $phishData['website_name'] = "";
            $phishData['website_url'] = "";
            $phishData['website_file'] = "";
        }

        // Fetch sender profile data
        $senderData = DB::table('senderprofile')->where('id', $senderProfile)->first();
        if ($senderData) {
            $phishData['senderProfile'] = $senderData->profile_name;
            $phishData['displayName'] = $senderData->from_name;
            $phishData['address'] = $senderData->from_email;
        } else {
            $phishData['senderProfile'] = "";
            $phishData['displayName'] = "";
            $phishData['address'] = "";
        }

        return response()->json($phishData, 200, [], JSON_PRETTY_PRINT);
    }

    public function rescheduleCampaign(Request $request)
    {
        
        $companyId = Auth::user()->company_id;

        $request->validate([
            'rschType' => 'required',
            'campid' => 'required'
        ]);

        if ($request->rschType == 'immediately') {

            $launchTime = Carbon::now()->format("m/d/Y g:i A");
            $email_freq = $request->emailFreq;
            $expire_after = $request->rexpire_after;

            $campaign = $this->makeCampaignLive($request->campid, $launchTime, $email_freq, $expire_after);

            $isreportexist = CampaignReport::where('campaign_id', $campaign->campaign_id)->first();

            if(!$isreportexist){
                CampaignReport::create([
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->campaign_name,
                    'campaign_type' => $campaign->campaign_type,
                    'status' => 'running',
                    'email_lang' => $campaign->email_lang,
                    'training_lang' => $campaign->training_lang,
                    'scheduled_date' => $launchTime,
                    'company_id' => $companyId,
                ]);
            }else{
                CampaignReport::where('campaign_id', $campaign->campaign_id)->update([
                    'scheduled_date' => $launchTime,
                    'status' => 'running'
                ]);
            }           
           
        }

        if($request->rschType == 'scheduled'){          

            
            $schedule_date = $request->rsc_launch_time;
            $startTime = $request->startTime;
            $endTime = $request->endTime;
            $timeZone = $request->rschTimeZone;
            $email_freq = $request->emailFreq;
            $expire_after = $request->rexpire_after;

            $launchTime = $this->generateRandomDate($schedule_date, $startTime, $endTime);

            $campaign = Campaign::where('id', $request->campid)->first();

            $campaign->launch_time = $launchTime;
            $campaign->launch_type = 'scheduled';
            $campaign->email_freq = $email_freq;
            $campaign->startTime = $startTime;
            $campaign->endTime = $endTime;
            $campaign->timeZone = $timeZone;
            $campaign->expire_after = $expire_after;
            $campaign->status = 'pending';
            $campaign->save();

            $isreportexist = CampaignReport::where('campaign_id', $campaign->campaign_id)->first();

            if(!$isreportexist){
                CampaignReport::create([
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->campaign_name,
                    'campaign_type' => $campaign->campaign_type,
                    'status' => 'pending',
                    'email_lang' => $campaign->email_lang,
                    'training_lang' => $campaign->training_lang,
                    'scheduled_date' => $launchTime,
                    'company_id' => $companyId,
                ]);
            }else{
                CampaignReport::where('campaign_id', $campaign->campaign_id)->update([
                    'scheduled_date' => $launchTime,
                    'status' => 'pending'
                ]);
            }     
        }

        log_action('Email campaign rescheduled');

        return redirect()->back()->with('success', 'Campaign rescheduled successfully');

        
    }

    private function makeCampaignLive($campaignid, $launch_time, $email_freq, $expire_after)
    {
        $companyId = Auth::user()->company_id;
        // Retrieve the campaign instance
        $campaign = Campaign::where('id', $campaignid)->first();

        // Retrieve the users in the specified group
        $users = Users::where('group_id', $campaign->users_group)->get();

        // Check if users exist in the group
        if ($users->isEmpty()) {
            return redirect()->back()->with('error', 'No employees available in this group GroupID:' . $campaign->users_group);
        } else {
            // Iterate through the users and create CampaignLive entries
            foreach ($users as $user) {
                CampaignLive::create([
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->campaign_name,
                    'user_id' => $user->id,
                    'user_name' => $user->user_name,
                    'user_email' => $user->user_email,
                    'training_module' => $campaign->training_module,
                    'training_lang' => $campaign->training_lang,
                    'launch_time' => $campaign->launch_time,
                    'phishing_material' => $campaign->phishing_material,
                    'email_lang' => $campaign->email_lang,
                    'sent' => '0',
                    'company_id' => $campaign->company_id,
                ]);
            }

            // Update the campaign status to 'running'
            $campaign->update([
                'status' => 'running',
                'launch_type' => 'immediately',
                'launch_time' => $launch_time,
                'email_freq' => $email_freq,
                'expire_after' => $expire_after

            ]);

            
        }

        return $campaign;
    }
}
