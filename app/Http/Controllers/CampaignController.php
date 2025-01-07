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
use App\Mail\TrainingAssignedEmail;
use App\Models\TrainingAssignedUser;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class CampaignController extends Controller
{
    //
    public function index()
    {

        $companyId = Auth::user()->company_id;
        $allCamps = Campaign::with('usersGroup')->where('company_id', $companyId)->get();

        $lastCampaign = Campaign::where('company_id', $companyId)->orderBy('id', 'desc')->first();
        $daysSinceLastDelivery = $lastCampaign ? max(0, Carbon::now()->diffInDays(Carbon::parse($lastCampaign->launch_time), false)) : 0;

        $all_sent = CampaignLive::where('sent', 1)->where('company_id', $companyId)->count();
        $mail_open = CampaignLive::where('mail_open', 1)->where('company_id', $companyId)->count();



        // Fetch users groups and phishing emails, and pass to view
        $usersGroups = UsersGroup::where('company_id', $companyId)->get();
        $phishingEmails = PhishingEmail::where('company_id', $companyId)
            ->orWhere('company_id', 'default')
            ->limit(10)->get();
        $trainingModules = TrainingModule::where('company_id', $companyId)
            ->orWhere('company_id', 'default')
            ->limit(10)->get();


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

    public function showMorePhishingEmails(Request $request)
    {
        $page = $request->input('page', 1);
        $companyId = Auth::user()->company_id;

        $phishingEmails = PhishingEmail::where('company_id', $companyId)
            ->orWhere('company_id', 'default')
            ->skip(($page - 1) * 10)
            ->take(10)
            ->get();

        return response()->json(['status' => 1, 'data' => $phishingEmails]);
    }

    public function searchPhishingMaterial(Request $request)
    {

        $searchTerm = $request->input('search');
        $companyId = Auth::user()->company_id;

        $phishingEmails = PhishingEmail::where('company_id', $companyId)
            ->orWhere('company_id', 'default')
            ->where(function ($query) use ($searchTerm) {
                $query->where('name', 'LIKE', "%{$searchTerm}%");
            })
            ->get();

        return response()->json(['status' => 1, 'data' => $phishingEmails]);
    }


    public function createCampaign(Request $request)
    {
        try {
            // Validate request input
            $validated = $request->all();

            // return print_r($validated['phish_material']);

            $companyId = auth()->user()->company_id;

            if ($validated['campaign_type'] !== 'Training') {
                // Check phishing email validity
                $phishingEmail = PhishingEmail::where('id', $validated['phish_material'])
                    ->where(function ($query) {
                        $query->where('senderProfile', '0')
                            ->orWhere('website', '0');
                    })
                    ->first();

                if ($phishingEmail) {
                    return response()->json([
                        'status' => 0,
                        'msg' => 'Sender profile or Website is not associated with the selected phishing email template',
                    ]);
                }
            }



            $campId = Str::random(6);
            $launchType = $validated['schType'];

            if ($launchType === 'immediately') {
                return $this->handleImmediateLaunch($validated, $campId, $companyId);
            }

            if ($launchType === 'scheduled') {
                return $this->handleScheduledLaunch($validated, $campId, $companyId);
            }

            if ($launchType === 'schLater') {
                return $this->handleLaterLaunch($validated, $campId, $companyId);
            }

            return response()->json(['status' => 0, 'msg' => 'Invalid launch type']);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 0,
                'msg' => 'Validation error',
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'msg' => $e->getMessage(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function handleImmediateLaunch($data, $campId, $companyId)
    {
        $scheduledDate = Carbon::createFromFormat("m/d/Y H:i", $data['launch_time']);
        $launchTimeFormatted = $scheduledDate->format("m/d/Y g:i A");

        $users = User::where('group_id', $data['users_group'])->get();

        if ($users->isEmpty()) {
            return response()->json(['status' => 0, 'msg' => 'No employees available in this group']);
        }

        foreach ($users as $user) {
            CampaignLive::create([
                'campaign_id' => $campId,
                'campaign_name' => $data['camp_name'],
                'user_id' => $user->id,
                'user_name' => $user->user_name,
                'user_email' => $user->user_email,
                'training_module' => ($data['training_mod'] == '') ? null : $data['training_mod'][array_rand($data['training_mod'])],
                'days_until_due' => $data['days_until_due'],
                'training_lang' => $data['trainingLang'],
                'training_type' => $data['training_type'],
                'launch_time' => $launchTimeFormatted,
                'phishing_material' => $data['phish_material'] == '' ? null : $data['phish_material'][array_rand($data['phish_material'])],
                'email_lang' => $data['email_lang'],
                'sent' => '0',
                'company_id' => $companyId,
            ]);
        }

        CampaignReport::create([
            'campaign_id' => $campId,
            'campaign_name' => $data['camp_name'],
            'campaign_type' => $data['campaign_type'],
            'status' => 'running',
            'email_lang' => $data['email_lang'],
            'training_lang' => $data['trainingLang'],
            'days_until_due' => $data['days_until_due'],
            'scheduled_date' => $launchTimeFormatted,
            'company_id' => $companyId,
        ]);

        Campaign::create([
            'campaign_id' => $campId,
            'campaign_name' => $data['camp_name'],
            'campaign_type' => $data['campaign_type'],
            'users_group' => $data['users_group'],
            'training_module' => $data['training_mod'] == '' ? null : json_encode($data['training_mod']),
            'training_assignment' => $data['training_assignment'] ?? null,
            'days_until_due' => $data['days_until_due'],
            'training_lang' => $data['trainingLang'],
            'training_type' => $data['training_type'],
            'phishing_material' => $data['phish_material'] == '' ? null : json_encode($data['phish_material']),
            'email_lang' => $data['email_lang'],
            'launch_time' => $launchTimeFormatted,
            'launch_type' => 'immediately',
            'email_freq' => $data['emailFreq'],
            'startTime' => '00:00:00',
            'endTime' => '00:00:00',
            'timeZone' => $data['schTimeZone'],
            'expire_after' => $data['expire_after'],
            'status' => 'running',
            'company_id' => $companyId,
        ]);

        log_action('Email campaign created');

        return response()->json(['status' => 1, 'msg' => 'Campaign created and running!']);
    }

    private function handleScheduledLaunch($data, $campId, $companyId)
    {
        $launchTime = $this->generateRandomDate(
            $data['schBetRange'],
            $data['schTimeStart'],
            $data['schTimeEnd'],
            $data['schTimeZone']
        );

        Campaign::create([
            'campaign_id' => $campId,
            'campaign_name' => $data['camp_name'],
            'campaign_type' => $data['campaign_type'],
            'users_group' => $data['users_group'],
            'training_module' => $data['training_mod'] == '' ? null : json_encode($data['training_mod']),
            'training_assignment' => $data['training_assignment'] ?? null,
            'training_lang' => $data['trainingLang'],
            'training_type' => $data['training_type'],
            'days_until_due' => $data['days_until_due'],
            'phishing_material' => $data['phish_material'] == '' ? null : json_encode($data['phish_material']),
            'email_lang' => $data['email_lang'],
            'launch_time' => $launchTime,
            'launch_type' => 'scheduled',
            'email_freq' => $data['emailFreq'],
            'startTime' => $data['schTimeStart'],
            'endTime' => $data['schTimeEnd'],
            'timeZone' => $data['schTimeZone'],
            'expire_after' => $data['expire_after'],
            'status' => 'pending',
            'company_id' => $companyId,
        ]);

        CampaignReport::create([
            'campaign_id' => $campId,
            'campaign_name' => $data['camp_name'],
            'campaign_type' => $data['campaign_type'],
            'days_until_due' => $data['days_until_due'],
            'status' => 'pending',
            'email_lang' => $data['email_lang'],
            'training_lang' => $data['trainingLang'],
            'scheduled_date' => $launchTime,
            'company_id' => $companyId,
        ]);

        log_action('Email campaign scheduled');

        return response()->json(['status' => 1, 'msg' => 'Campaign created and scheduled!']);
    }

    private function handleLaterLaunch($data, $campId, $companyId)
    {
        $launchTime = Carbon::createFromFormat("m/d/Y H:i", $data['launch_time'])->format("m/d/Y g:i A");

        Campaign::create([
            'campaign_id' => $campId,
            'campaign_name' => $data['camp_name'],
            'campaign_type' => $data['campaign_type'],
            'users_group' => $data['users_group'],
            'training_module' => $data['training_mod'] == '' ? null : json_encode($data['training_mod']),
            'training_assignment' => $data['training_assignment'] ?? null,
            'training_lang' => $data['trainingLang'],
            'training_type' => $data['training_type'],
            'days_until_due' => $data['days_until_due'],
            'phishing_material' => $data['phish_material'] == '' ? null : json_encode($data['phish_material']),
            'email_lang' => $data['email_lang'],
            'launch_time' => $launchTime,
            'launch_type' => 'schLater',
            'email_freq' => $data['emailFreq'],
            'startTime' => $data['schTimeStart'],
            'endTime' => $data['schTimeEnd'],
            'timeZone' => $data['schTimeZone'],
            'expire_after' => $data['expire_after'],
            'status' => 'Not Scheduled',
            'company_id' => $companyId,
        ]);

        log_action('Email campaign created for schedule later');

        return response()->json(['status' => 1, 'msg' => 'Campaign saved successfully!']);
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

        $campid = $request->campid;
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
            return response()->json(['status' => 0, 'msg' => 'No employees available in this group']);
        }

        foreach ($users as $user) {

            CampaignLive::create([
                'campaign_id' => $campaign->campaign_id,
                'campaign_name' => $campaign->campaign_name,
                'user_id' => $user->id,
                'user_name' => $user->user_name,
                'user_email' => $user->user_email,
                'training_module' => ($campaign->training_module == null) ? null : json_decode($campaign->training_module, true)[array_rand(json_decode($campaign->training_module, true))],
                'days_until_due' => $campaign->days_until_due ?? null,
                'training_lang' => $campaign->training_lang ?? null,
                'training_type' => $campaign->training_type ?? null,
                'launch_time' => $formattedDateTime,
                'phishing_material' => $campaign->phishing_material == null ? null : json_decode($campaign->phishing_material, true)[array_rand(json_decode($campaign->phishing_material, true))],
                'email_lang' => $campaign->email_lang ?? null,
                'sent' => '0',
                'company_id' => $company_id,
            ]);
        }

        log_action('Email campaign relaunched');

        return response()->json(['status' => 1, 'msg' => 'Campaign relaunched successfully']);
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

            $isLive = $this->makeCampaignLive($request->campid, $launchTime, $email_freq, $expire_after);

            if ($isLive['status'] === 0) {
                return redirect()->back()->with('error', $isLive['msg']);
            }

            $campaign = $isLive['campaign'];

            $isreportexist = CampaignReport::where('campaign_id', $campaign->campaign_id)->first();

            if (!$isreportexist) {
                CampaignReport::create([
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->campaign_name,
                    'campaign_type' => $campaign->campaign_type,
                    'status' => 'running',
                    'email_lang' => $campaign->email_lang,
                    'training_lang' => $campaign->training_lang,
                    'days_until_due' => $campaign->days_until_due,
                    'scheduled_date' => $launchTime,
                    'company_id' => $companyId,
                ]);
            } else {
                CampaignReport::where('campaign_id', $campaign->campaign_id)->update([
                    'scheduled_date' => $launchTime,
                    'status' => 'running'
                ]);
            }
        }

        if ($request->rschType == 'scheduled') {


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

            if (!$isreportexist) {
                CampaignReport::create([
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->campaign_name,
                    'campaign_type' => $campaign->campaign_type,
                    'status' => 'pending',
                    'email_lang' => $campaign->email_lang,
                    'training_lang' => $campaign->training_lang,
                    'days_until_due' => $campaign->days_until_due,
                    'scheduled_date' => $launchTime,
                    'company_id' => $companyId,
                ]);
            } else {
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
            return ['status' => 0, 'msg' => 'No employees available in this group'];
        }

        // Iterate through the users and create CampaignLive entries
        foreach ($users as $user) {
            CampaignLive::create([
                'campaign_id' => $campaign->campaign_id,
                'campaign_name' => $campaign->campaign_name,
                'user_id' => $user->id,
                'user_name' => $user->user_name,
                'user_email' => $user->user_email,
                'training_module' => $campaign->training_module !== null ? json_decode($campaign->training_module)[array_rand(json_decode($campaign->training_module))] : null,
                'days_until_due' => $campaign->days_until_due ?? null,
                'training_lang' => $campaign->training_lang ?? null,
                'training_type' => $campaign->training_type ?? null,
                'launch_time' => $launch_time,
                'phishing_material' => $campaign->phishing_material !== null ? json_decode($campaign->phishing_material)[array_rand(json_decode($campaign->phishing_material))] : null,
                'email_lang' => $campaign->email_lang ?? null,
                'sent' => '0',
                'company_id' => $companyId,
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

        return ['status' => 1, 'campaign' => $campaign];
    }

    public function sendTrainingReminder(Request $request)
    {
        // Fetch user credentials
        $userCredentials = DB::table('user_login')
            ->where('login_username', $request->email)
            ->first();

        if (!$userCredentials) {
            return response()->json(['status' => 0, 'msg' => 'User credentials not found']);
        }

        $assignedTraining = TrainingAssignedUser::where('user_email', $request->email)
            ->first();

        if (!$assignedTraining) {
            return response()->json(['status' => 0, 'msg' => 'No training assigned to this user']);
        }


        $learnSiteAndLogo = checkWhitelabeled(auth()->user()->company_id);

        $mailData = [
            'user_name' => $assignedTraining->user_name,
            'training_name' => $request->training,
            'login_email' => $userCredentials->login_username,
            'login_pass' => $userCredentials->login_password,
            'company_name' => $learnSiteAndLogo['company_name'],
            'company_email' => $learnSiteAndLogo['company_email'],
            'learning_site' => $learnSiteAndLogo['learn_domain'],
            'logo' => $learnSiteAndLogo['logo']
        ];



        Mail::to($userCredentials->login_username)->send(new TrainingAssignedEmail($mailData));

        log_action("Training reminder sent to {$request->email}");

        return response()->json(['status' => 1, 'msg' => 'Training reminder sent successfully']);
    }

    public function completeTraining(Request $request)
    {

        $training_id = base64_decode($request->encodedTrainingId);

        $trainingAssigned = TrainingAssignedUser::find($training_id);

        if (!$trainingAssigned) {
            return response()->json(['status' => 0, 'msg' => 'No training assigned to this user']);
        }

        $trainingAssigned->update([
            'completed' => 1,
            'personal_best' => 100,
            'completion_date' => now()
        ]);

        $reportUpdate = CampaignReport::where('campaign_id', $trainingAssigned->campaign_id)->increment('training_completed');

        log_action("Training marked as completed to {$trainingAssigned->user_email}");

        return response()->json(['status' => 1, 'msg' => 'Training completed successfully']);
    }

    public function removeTraining(Request $request)
    {
        $training_id = base64_decode($request->encodedTrainingId);

        $trainingAssigned = TrainingAssignedUser::find($training_id);
        if (!$trainingAssigned) {
            return response()->json(['status' => 0, 'msg' => 'No training assigned to this user']);
        }
        $trainingAssigned->delete();


        log_action("Training removed from {$trainingAssigned->user_email}");

        return response()->json(['status' => 1, 'msg' => 'Training removed successfully']);
    }

    public function fetchCampaignDetail(Request $request)
    {

        $detail = Campaign::with(['campLive', 'campReport', 'trainingAssignedUsers'])->where('campaign_id', $request->campaignId)->first();

        return response()->json($detail);
    }

    public function fetchTrainingIndividual(Request $request)
    {
        $request->validate([
            'campaignId' => 'required|string',
        ]);

        $campId = $request->input('campaignId');
        $companyId = Auth::user()->company_id;

        // Fetch assigned training users
        $assignedUsers = TrainingAssignedUser::where('campaign_id', $campId)->where('company_id', $companyId)->get();

        if ($assignedUsers->isEmpty()) {
            return response()->json([
                'html' => '
                <tr>
                    <td colspan="7" class="text-center"> No records found</td>
                </tr>',
            ]);
        }

        $responseHtml = '';
        foreach ($assignedUsers as $assignedUser) {
            $trainingDetail = TrainingModule::find($assignedUser->training);

            $today = new \DateTime(date('Y-m-d'));
            $dueDate = new \DateTime($assignedUser->training_due_date);

            if ($assignedUser->completed == 1) {
                $status = "<span class='text-success'><strong>Training Completed</strong></span>";
            } else {
                if ($dueDate > $today) {
                    $status = "<span class='text-success'><strong>In training period</strong></span>";
                } else {
                    $days_difference = $today->diff($dueDate)->days;
                    $status = "<span class='text-danger'><strong>Overdue - " . $days_difference . ' Days</strong></span>';
                }
            }


            $responseHtml .=
                '
                <tr>
                    <td>' . $assignedUser->user_name . '</td>
                    <td>' . $assignedUser->user_email . '</td>
                    <td><span class="badge rounded-pill bg-primary">' . $trainingDetail->name . '</span></td>
                    <td>' . $assignedUser->assigned_date . '</td>
                    <td>' . $assignedUser->personal_best . '%</td>
                    <td>' . $trainingDetail->passing_score . '%</td>
                    <td>' . $status . '</td>
                    <td> 
                        <button type="button" 
                        onclick="resendTrainingAssignmentReminder(this, `' . $assignedUser->user_email . '`, `' . $trainingDetail->name . '`)" 
                        class="btn btn-icon btn-primary-transparent rounded-pill btn-wave" 
                        data-bs-toggle="tooltip"
                        data-bs-placement="top" 
                        title="Would you like to send a training reminder to this employee? The reminder email will include all outstanding training assignments.">
                            <i class="ri-mail-send-line"></i>
                        </button>

                        <button type="button" 
                        class="btn btn-icon btn-secondary-transparent rounded-pill btn-wave" 
                        onclick="completeAssignedTraining(this, `' . base64_encode($assignedUser->id) . '`)"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top" 
                        title="Would you like to auto-complete the assigned training for this employee? This will assign a passing score of 100% for this training module.">
                        <i class="ri-checkbox-circle-line"></i>
                        </button>

                        <button type="button" 
                        class="btn btn-icon btn-danger-transparent rounded-pill btn-wave" 
                        data-bs-toggle="tooltip"
                        onclick="removeAssignedTraining(this, `' . base64_encode($assignedUser->id) . '`, `' . $trainingDetail->name . '`, `' . $assignedUser->user_email . '`)"
    data-bs-placement="top" title="Should this employee not be assigned this training? Click here to remove it.">
                            <i class="ri-delete-bin-line"></i>
                        </button> 
                    </td>
                </tr>';
        }

        return response()->json(['html' => $responseHtml]);
    }
}
