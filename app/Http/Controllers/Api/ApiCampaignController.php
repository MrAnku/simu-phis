<?php

namespace App\Http\Controllers\Api;

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
use App\Models\EmailCampActivity;
use Illuminate\Support\Facades\DB;
use App\Mail\TrainingAssignedEmail;
use App\Http\Controllers\Controller;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ApiCampaignController extends Controller
{
    public function index()
    {
        try {
            $companyId = Auth::user()->company_id;
            $allCamps = Campaign::with('usersGroup')
                ->where('company_id', $companyId)
                ->orderBy('id', 'desc')
                ->get();

            $lastCampaign = Campaign::where('company_id', $companyId)->orderBy('id', 'desc')->first();
            $daysSinceLastDelivery = $lastCampaign ? max(0, (int)Carbon::now()->diffInDays(Carbon::parse($lastCampaign->launch_time), false)) : 0;

            $all_sent = CampaignLive::where('sent', 1)->where('company_id', $companyId)->count();
            $mail_open = CampaignLive::where('mail_open', 1)->where('company_id', $companyId)->count();

            $usersGroups = UsersGroup::where('company_id', $companyId)->where('users', '!=', null)->get();
            $phishingEmails = PhishingEmail::where('company_id', $companyId)
                ->orWhere('company_id', 'default')
                ->limit(10)->get();
            $trainingModules = TrainingModule::where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhere('company_id', 'default');
            })->where('training_type', 'static_training')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'message' => __('Campaign data retrieved successfully.'),
                'data' => [
                    'allCamps' => $allCamps,
                    'usersGroups' => $usersGroups,
                    'phishingEmails' => $phishingEmails,
                    'trainingModules' => $trainingModules,
                    'daysSinceLastDelivery' => $daysSinceLastDelivery,
                    'all_sent' => $all_sent,
                    'mail_open' => $mail_open,
                ],
            ]);
        } catch (\Exception $err) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $err->getMessage(),
            ], 500);
        }
    }
    public function createCampaign(Request $request)
    {
        try {

            //xss check start
            $input = $request->all();
            foreach ($input as $key => $value) {
                if (is_array($value)) {
                    array_walk_recursive($value, function ($item) {
                        if (preg_match('/<[^>]*>|<\?php/', $item)) {
                            return response()->json([
                                'success' => false,
                                'message' => __('Invalid input detected.')
                            ], 422);
                        }
                    });
                } else {
                    if (preg_match('/<[^>]*>|<\?php/', $value)) {
                        return response()->json([
                            'success' => false,
                            'message' => __('Invalid input detected.')
                        ], 422);
                    }
                }
            }
            //xss check end

            // Validate request input
            $validated = $request->all();

            // return print_r($validated['phish_material']);

            $companyId = Auth::user()->company_id;

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
                        'success' => false,
                        'message' => __('Sender profile or Website is not associated with the selected phishing email template'),
                    ], 422);
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

            return response()->json([
                'success' => false,
                'message' => __('Invalid launch type')
            ], 422);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __("Error") . " :" . $e->validator->errors()->first(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function handleImmediateLaunch($data, $campId, $companyId)
    {
        // $scheduledDate = Carbon::createFromFormat("m/d/Y H:i", $data['launch_time']);
        $launchTimeFormatted = Carbon::now()->format("m/d/Y g:i A");

        $groupExists = UsersGroup::where('group_id', $data['users_group'])->exists();
        if (!$groupExists) {
            return response()->json([
                'success' => false,
                'message' => __('Group not found')
            ], 422);
        }

        $userIdsJson = UsersGroup::where('group_id', $data['users_group'])->value('users');
        $userIds = json_decode($userIdsJson, true);
        $users = Users::whereIn('id', $userIds)->get();

        // $users = User::where('group_id', $data['users_group'])->get();

        if ($users->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => __('No employees available in this group')
            ], 422);
        }

        foreach ($users as $user) {
            $camp_live = CampaignLive::create([
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

            EmailCampActivity::create([
                'campaign_id' => $campId,
                'campaign_live_id' => $camp_live->id,
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

        return response()->json([
            'success' => true,
            'message' => __('Campaign created and running!')
        ]);
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

        return response()->json([
            'success' => true,
            'message' => __('Campaign created and scheduled!')
        ]);
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

        return response()->json([
            'success' => true,
            'message' => __('Campaign saved successfully!')
        ]);
    }

    public function deleteCampaign(Request $request)
    {
        try {
            $campid = $request->route('campaign_id');
            $companyId = Auth::user()->company_id;

            Campaign::where('campaign_id', $campid)
                ->where('company_id', $companyId)
                ->delete();

            CampaignLive::where('campaign_id', $campid)
                ->where('company_id', $companyId)
                ->delete();

            CampaignReport::where('campaign_id', $campid)
                ->where('company_id', $companyId)
                ->delete();

            EmailCampActivity::where('campaign_id', $campid)
                ->where('company_id', $companyId)
                ->delete();

            log_action('Email campaign deleted');
            return response()->json([
                'success' => true,
                'message' => __('Campaign deleted successfully!')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function fetchCampaignDetail(Request $request)
    {
        try {
            $campid = $request->route('campaign_id');
            $companyId = Auth::user()->company_id;

            $campaign = Campaign::with(['campLive', 'campaignActivity', 'campReport', 'trainingAssignedUsers.trainingData'])->where('campaign_id', $campid)
                ->where('company_id', $companyId)
                ->first();

            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign not found')
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => __('Campaign details retrieved successfully'),
                'data' => $campaign
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function fetchGameDetail(Request $request)
    {
        try {
            $campid = $request->route('campaign_id');
            $companyId = Auth::user()->company_id;

            $campaignName = Campaign::where('campaign_id', $campid)
                ->where('company_id', Auth::user()->company_id)
                ->where('training_type', 'games')
                ->value('campaign_name');

            $playedUsers = CampaignLive::where('campaign_id', $campid)
                ->where('company_id', Auth::user()->company_id)
                ->where('training_type', 'games')
                ->count();

            $totalAssigned = TrainingAssignedUser::where('campaign_id', $campid)
                ->where('company_id', Auth::user()->company_id)
                ->where('training_type', 'games')
                ->count();

            $gameCompleted = TrainingAssignedUser::where('campaign_id', $campid)
                ->where('company_id', Auth::user()->company_id)
                ->where('training_type', 'games')
                ->where('completed', 1)
                ->count();

            $campaignDetail = [
                'campaign_name' => $campaignName,
                'played_users' => $playedUsers,
                'total_assigned' => $totalAssigned,
                'game_completed' => $gameCompleted
            ];

            $targetEmployees = TrainingAssignedUser::with('trainingGame')->where('training_type', 'games')
                ->where('campaign_id', $campid)
                ->where('company_id', Auth::user()->company_id)
                ->get();


            return response()->json([
                'success' => true,
                'message' => __('Game details retrieved successfully'),
                'data' => [
                    'campaign_detail' => $campaignDetail,
                    'target_employees' => $targetEmployees
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function relaunchCampaign(Request $request)
    {

        try {
            $campid = $request->route('campaign_id');
            $companyId = Auth::user()->company_id;

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

            $userIdsJson = UsersGroup::where('group_id', $campaign->users_group)->value('users');
            $userIds = json_decode($userIdsJson, true);
            $users = Users::whereIn('id', $userIds)->get();

            // $users = User::where('group_id', $campaign->users_group)->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => __('No employees available in this group')
                ], 422);
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
            return response()->json([
                'success' => true,
                'message' => __('Campaign relaunched successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function fetchPhishData(Request $request)
    {
        try {
            $website = $request->query('website', null);
            $senderProfile = $request->query('senderProfile', null);

            $phishData = [];

            if ($website == null && $senderProfile == null) {
                return response()->json([
                    'success' => false,
                    'message' => __('Invalid request')
                ], 422);
            }
            if ($website) {
                // Fetch website data
                $websiteData = DB::table('phishing_websites')->where('id', $website)->first();
                if ($websiteData) {
                    $phishData['website_name'] = $websiteData->name;
                    $phishData['website_url'] = $websiteData->domain;
                    $phishData['website_file'] = "/storage/uploads/phishingMaterial/phishing_websites/" . $websiteData->file;
                } else {
                    $phishData['website_name'] = "";
                    $phishData['website_url'] = "";
                    $phishData['website_file'] = "";
                }
            }

            if ($senderProfile) {
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
            }

            return response()->json([
                'success' => true,
                'message' => __('Phishing data retrieved successfully'),
                'data' => $phishData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function rescheduleCampaign(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $campaignId = $request->route('campaign_id', null);
            if (!$campaignId) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required')
                ], 422);
            }
            $request->validate([
                'schedule_type' => 'required'
            ]);

            if ($request->schedule_type == 'immediately') {

                $launchTime = Carbon::now()->format("m/d/Y g:i A");
                $email_freq = $request->email_frequency;
                $expire_after = $request->expire_after;

                $isLive = $this->makeCampaignLive($campaignId, $launchTime, $email_freq, $expire_after);

                if ($isLive['status'] === 0) {
                    return response()->json([
                        'success' => false,
                        'message' => $isLive['msg']
                    ], 422);
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

            if ($request->schedule_type == 'scheduled') {


                $schedule_date = $request->schedule_date;
                $startTime = $request->start_time;
                $endTime = $request->end_time;
                $timeZone = $request->time_zone;
                $email_freq = $request->email_frequency;
                $expire_after = $request->expire_after;

                $launchTime = $this->generateRandomDate($schedule_date, $startTime, $endTime);

                $campaign = Campaign::where('campaign_id', $campaignId)
                    ->where('company_id', Auth::user()->company_id)
                    ->first();
                if (!$campaign) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Campaign not found')
                    ], 404);
                }

                $campaign->launch_time = $launchTime;
                $campaign->launch_type = 'scheduled';
                $campaign->email_freq = $email_freq;
                $campaign->startTime = $startTime;
                $campaign->endTime = $endTime;
                $campaign->timeZone = $timeZone;
                $campaign->expire_after = $expire_after;
                $campaign->status = 'pending';
                $campaign->save();

                $isreportexist = CampaignReport::where('campaign_id', $campaign->campaign_id)->where('company_id', Auth::user()->company_id)
                    ->first();

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

            return response()->json([
                'success' => true,
                'message' => __('Campaign rescheduled successfully!')
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->validator->errors()->first()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    private function makeCampaignLive($campaignid, $launch_time, $email_freq, $expire_after)
    {
        $companyId = Auth::user()->company_id;
        // Retrieve the campaign instance
        $campaign = Campaign::where('campaign_id', $campaignid)->where('company_id', Auth::user()->company_id)->first();
        if (!$campaign) {
            return ['status' => 0, 'msg' => __('Campaign not found')];
        }

        $groupExists = UsersGroup::where('group_id', $campaign->users_group)->where('company_id', Auth::user()->company_id)->exists();
        if (!$groupExists) {
            return ['status' => 0, 'msg' => __('Group not found')];
        }
        // Retrieve the users in the specified group
        $userIdsJson = UsersGroup::where('group_id', $campaign->users_group)
            ->where('company_id', Auth::user()->company_id)
            ->value('users');

        $userIds = json_decode($userIdsJson, true);
        $users = Users::whereIn('id', $userIds)->get();

        // $users = Users::where('group_id', $campaign->users_group)->get();

        // Check if users exist in the group
        if ($users->isEmpty()) {
            return ['status' => 0, 'msg' => __('No employees available in this group')];
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
        try {
            $email = $request->route('email');
            if (!$email) {
                return response()->json([
                    'success' => false,
                    'message' => __('Email is required')
                ], 422);
            }
            $assignedTraining = TrainingAssignedUser::with('trainingData', 'trainingGame')->where('user_email', $email)
                ->first();

            if (!$assignedTraining) {
                return response()->json([
                    'success' => false,
                    'message' => __('Training not found for this email')
                ], 404);
            }


            $learnSiteAndLogo = checkWhitelabeled(Auth::user()->company_id);

            $mailData = [
                'user_name' => $assignedTraining->user_name,
                'training_name' => $assignedTraining->training_type == 'games' ? $assignedTraining->trainingGame->name : $assignedTraining->trainingData->name,
                // 'login_email' => $userCredentials->login_username,
                // 'login_pass' => $userCredentials->login_password,
                'company_name' => $learnSiteAndLogo['company_name'],
                'company_email' => $learnSiteAndLogo['company_email'],
                'learning_site' => $learnSiteAndLogo['learn_domain'],
                'logo' => $learnSiteAndLogo['logo']
            ];



            Mail::to($email)->send(new TrainingAssignedEmail(
                $mailData,
                $this->getTrainingNamesArray($email)
            ));

            log_action("Training reminder sent to {$request->email}");

            return response()->json([
                'success' => true,
                'message' => __('Training reminder sent successfully!')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    private function getTrainingNamesArray($email)
    {
        $assignedTrainings = TrainingAssignedUser::with('trainingData', 'trainingGame')->where('user_email', $email)->get();
        $trainingNames = [];

        foreach ($assignedTrainings as $assignedTraining) {
            if ($assignedTraining->training_type == 'games') {
                $trainingNames[] = $assignedTraining->trainingGame->name;
            } else {
                $trainingNames[] = $assignedTraining->trainingData->name;
            }
        }

        return $trainingNames;
    }

    public function completeTraining(Request $request)
    {
        try {
            $encodedTrainingId = $request->route('encodedTrainingId');
            if (!$encodedTrainingId) {
                return response()->json([
                    'success' => false,
                    'message' => __('Encoded training ID is required')
                ], 422);
            }
            $training_id = base64_decode($encodedTrainingId);

            $trainingAssigned = TrainingAssignedUser::find($training_id);

            if (!$trainingAssigned) {
                return response()->json([
                    'success' => false,
                    'message' => __('Training not found')
                ], 404);
            }

            $trainingAssigned->update([
                'completed' => 1,
                'personal_best' => 100,
                'completion_date' => now()
            ]);

            $reportUpdate = CampaignReport::where('campaign_id', $trainingAssigned->campaign_id)->increment('training_completed');

            log_action("Training marked as completed to {$trainingAssigned->user_email}");

            return response()->json([
                'success' => true,
                'message' => __('Training marked as completed successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function removeTraining(Request $request)
    {
        try {
            $encodedTrainingId = $request->route('encodedTrainingId');
            if (!$encodedTrainingId) {
                return response()->json([
                    'success' => false,
                    'message' => __('Encoded training ID is required')
                ], 422);
            }
            $training_id = base64_decode($encodedTrainingId);

            $trainingAssigned = TrainingAssignedUser::find($training_id);

            if (!$trainingAssigned) {
                return response()->json([
                    'success' => false,
                    'message' => __('Training not found')
                ], 404);
            }

            $trainingAssigned->delete();

            log_action("Training removed for {$trainingAssigned->user_email}");

            return response()->json([
                'success' => true,
                'message' => __('Training removed successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }
}
