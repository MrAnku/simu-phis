<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Users;
use App\Models\Company;
use App\Models\TprmUsers;
use App\Models\DomainEmail;
use Illuminate\Support\Str;
use App\Models\TprmCampaign;
use Illuminate\Http\Request;
use App\Models\PhishingEmail;
use App\Models\TprmUsersGroup;
use App\Models\TrainingModule;
use App\Models\TprmCampaignLive;
use App\Models\TpmrVerifiedDomain;
use App\Models\TprmCampaignReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\TrainingAssignedUser;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class TprmController extends Controller
{
    //
    public function index()
    {
        $companyId = Auth::user()->company_id;
        $allCamps = TprmCampaign::where('company_id', $companyId)->orderBy('id', 'desc')
            ->paginate(10);
        $lastCampaign = TprmCampaign::orderBy('id', 'desc')->first();
        // return $lastCampaign;
        if ($lastCampaign) {
            $lastDeliveryDate = Carbon::parse($lastCampaign->delivery_date);
            $currentDate = Carbon::now();  //dobut
            if ($currentDate->diffInDays($lastDeliveryDate) < 1) {
                $daysSinceLastDelivery = 1;
            } else {

                $daysSinceLastDelivery = $currentDate->diffInDays($lastDeliveryDate);
            } // dobut
        } else {
            // Handle the case where no campaign deliveries exist
            $daysSinceLastDelivery = 0;
        }

        $all_sent = TprmCampaignLive::where('sent', 1)->where('company_id', $companyId)->count();
        $mail_open = TprmCampaignLive::where('mail_open', 1)->where('company_id', $companyId)->count();

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

            $usersGroup = TprmUsersGroup::where('group_id', $campaign->users_group)
                ->where('company_id', $companyId)
                ->first();

            $campaign->users_group_name = $usersGroup ? e($usersGroup->group_name) : 'N/A'; //doubt
        }

        // Fetch users groups and phishing emails, and pass to view
        $usersGroups = $this->fetchUsersGroups(); //doubt
        $phishingEmails = $this->fetchPhishingEmails();
        $trainingModules = $this->fetchTrainingModules();
        $companyId = Auth::user()->company_id;
        $groups = TprmUsersGroup::withCount('users')
            ->where('company_id', $companyId)
            ->get();

        $totalEmps = $groups->sum('users_count');
        $verifiedDomains = TpmrVerifiedDomain::where('verified', 1)->where('company_id', $companyId)->get();
        $notVerifiedDomains = TpmrVerifiedDomain::where('verified', 0)->where('company_id', $companyId)->get();

        $allDomains = TpmrVerifiedDomain::where('company_id', $companyId)->get();
        // return $allDomains;
        // $allDomainsDownload = TpmrVerifiedDomain::where('company_id', $companyId)->get();

        return view('tprm', compact(
            'allCamps',
            'usersGroups',
            'phishingEmails',
            'trainingModules',
            'daysSinceLastDelivery',
            'all_sent',
            'mail_open',
            'groups',
            'totalEmps',
            'verifiedDomains',
            'notVerifiedDomains',
            'allDomains'
        ));
    }
    public function test()
    {
        return response()->json(['status' => 1, 'message' => 'Frontend is working correctly!']);
    }


    public function submitdomains(Request $request)
    {
        //xss check start
        $input = $request->all();
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                array_walk_recursive($value, function ($item) {
                    if (preg_match('/<[^>]*>|<\?php/', $item)) {
                        return response()->json(['status' => 0, 'msg' => 'Invalid input detected.']);
                    }
                });
            } else {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json(['status' => 0, 'msg' => 'Invalid input detected.']);
                }
            }
        }
        //xss check end


        // Retrieve domains from the request, assuming JSON format
        $domains = $request->input('domains');

        // Validate that the input is an array
        if (!is_array($domains) || empty($domains)) {
            return response()->json(['status' => 0, 'msg' => 'Please provide one or more domains in an array format.']);
        }

        // Get the company ID of the authenticated user
        $companyId = auth()->user()->company_id;

        // Retrieve the partner ID from the Company table using the correct primary key
        $partnerId = Company::where('company_id', $companyId)->value('partner_id');

        // Check if partnerId is null
        if (!$partnerId) {
            return response()->json(['status' => 0, 'msg' => 'Partner ID not found for the specified company']);
        }

        // Array to store the result of each domain verification attempt
        $results = [];

        // Loop through each domain in the array
        foreach ($domains as $domain) {

            // Check if the domain is already in the database for this company
            $domainExists = TpmrVerifiedDomain::where('domain', $domain)
                ->where('company_id', $companyId)
                ->first();

            $verifiedDomain = TpmrVerifiedDomain::where('domain', $domain)
                ->where('verified', 1)
                ->first();

            if ($domainExists) {
                return response()->json(['status' => 0, 'msg' => 'Domain already exists']);
            }else if ($verifiedDomain) {
                return response()->json(['status' => 0, 'msg' => 'Domain already verified by another company']);
            } else {
                // Generate a temporary code for the new domain and save it
                $genCode = generateRandom(6);

                // Ensure partnerId is valid before attempting to store
                if ($partnerId) {

                    TpmrVerifiedDomain::create([
                        'domain' => $domain,
                        'temp_code' => $genCode,
                        'verified' => '0',
                        'company_id' => $companyId,
                        'partner_id' => $partnerId,
                    ]);


                    $results[] = [
                        'domain' => $domain,
                        'status' => 1,
                        'msg' => 'New domain verification requested successfully.'
                    ];
                } else {
                    return response()->json(['status' => 0, 'msg' => 'Failed to verify domain; partner ID is missing.']);
                }
            }
        }


        return response()->json(['status' => 1, 'results' => $results, 'msg' => 'New domain verification requested successfully.']);
    }



    private function domainVerificationMail($email, $code)
    {
        Mail::send('emails.domainVerification', ['code' => $code], function ($message) use ($email) {
            $message->to($email)->subject('Domain Verification');
        });
    }


    public function verifyOtp(Request $request)
    {
        $verificationCode = $request->input('emailOTP');
        $companyId = auth()->user()->company_id; // Assuming company_id is stored in the authenticated user

        $verifiedDomain = TpmrVerifiedDomain::where('temp_code', $verificationCode)
            ->where('company_id', $companyId)
            ->first();
        if ($verifiedDomain) {
            $verifiedDomain->verified = '1';
            $verifiedDomain->save();

            return response()->json(['status' => 1, 'msg' => 'Domain verified successfully']);
        } else {
            return response()->json(['status' => 0, 'msg' => 'Invalid Code']);
        }
    }



    public function deleteDomain(Request $request)
    {
        $domain = $request->vDomainId;

        $tprmUserGroup = TprmUsersGroup::where('group_name', $domain)
                            ->where('company_id', Auth()->user()->company_id)->first();

        if($tprmUserGroup){
            $group_id = $tprmUserGroup->group_id;
            $tprmUserGroup->delete();

            $tprmUsers = TprmUsers::where('group_id', $group_id)->get();

            foreach($tprmUsers as $tprmUser){
                $tprmUser->delete();
            }

            $tprmCampaign = TprmCampaign::where('users_group', $group_id)->first();

            if($tprmCampaign){

                $tprmCampaignId = $tprmCampaign->campaign_id;
                $tprmCampaign->delete();
                $TprmLiveCampaigns = TprmCampaignLive::where('campaign_id', $tprmCampaignId)->get();
                
                foreach($TprmLiveCampaigns as $TprmLiveCampaign){
                    $TprmLiveCampaign->delete();
                }
            }
        }

        DB::beginTransaction();

        try {

            // 1. Delete users associated with the domain
            $users = TprmUsers::where('user_email', 'LIKE', '%' . $domain)->get();
            foreach ($users as $user) {

                // Delete user-related data
                DB::table('user_login')->where('user_id', $user->id)->delete();


                // Delete the user record itself
                $user->delete();
            }

            // 2. Delete user groups associated with the domain (use `group_name` instead of `domain`)
            $userGroups = TprmUsersGroup::where('group_name', $domain)->get();
            foreach ($userGroups as $group) {
                $groupId = $group->group_id;
                $companyId = Auth::user()->company_id;


                // Delete all users in the group
                TprmUsers::where('group_id', $groupId)->delete();

                // Delete the group itself
                $group->delete();

                // 3. Delete campaigns associated with this user group
                $campaigns = TprmCampaign::where('users_group', $groupId)
                    ->where('company_id', $companyId)
                    ->get();

                foreach ($campaigns as $campaign) {
                    $campaignId = $campaign->campaign_id;


                    // Delete campaign records from all associated tables
                    TprmCampaign::where('campaign_id', $campaignId)
                        ->where('company_id', $companyId)
                        ->delete();
                    TprmCampaignLive::where('campaign_id', $campaignId)
                        ->where('company_id', $companyId)
                        ->delete();
                    TprmCampaignReport::where('campaign_id', $campaignId)
                        ->where('company_id', $companyId)
                        ->delete();
                }
            }

            // 4. Finally, delete the domain itself
            TpmrVerifiedDomain::where('domain', $domain)->delete();

            // Commit the transaction if all deletions are successful
            DB::commit();

            return response()->json(['status' => 1, 'msg' => 'Domain and associated data deleted successfully']);
        } catch (\Exception $e) {
            // Rollback the transaction if an error occurs
            DB::rollBack();



            return response()->json(['status' => 0, 'msg' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }



    public function newGroup(Request $request)
    {
        $grpName = $request->input('usrGroupName');
        $grpId = generateRandom(6);
        $companyId = auth()->user()->company_id; // Assuming company_id is stored in the authenticated user

        TprmUsersGroup::create([
            'group_id' => $grpId,
            'group_name' => $grpName,
            'users' => null,
            'company_id' => $companyId,
        ]);

        return redirect()->route('employees');
    }

    public function viewUsers($groupid)
    {
        $companyId = auth()->user()->company_id;
        $users = TprmUsers::where('group_id', $groupid)->where('company_id', $companyId)->get();

        if (!$users->isEmpty()) {
            return response()->json(['status' => 1, 'data' => $users]);
        } else {
            return response()->json(['status' => 0, 'msg' => 'no employees found']);
        }
    }

    public function deleteUser(Request $request)
    {
        $user = TprmUsers::find($request->user_id);

        if ($user) {
            $user->delete();
            return response()->json(['status' => 1, 'msg' => 'User deleted successfully'], 200);
        } else {
            return response()->json(['status' => 0, 'msg' => 'User not found'], 404);
        }
    }





    public function fetchUsersGroups()
    {
        $companyId = Auth::user()->company_id;
        return TprmUsersGroup::where('company_id', $companyId)->get();
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

        //xss check start

        $input = $request->all();
        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return response()->json(['status' => 0, 'msg' => 'Invalid input detected.']);
            }
        }
        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);

        //xss check end

        $campaignType = $request->input('campaign_type');
        $campName = $request->input('camp_name');
        $usersGroup = $request->input('users_group');
        $trainingMod = $request->input('training_mod');
        $trainingLang = $request->input('trainingLang');
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

            $users = TprmUsers::where('group_id', $usersGroup)->get();

            if ($users->isEmpty()) {
                return response()->json(['status' => 0, 'msg' => 'No employees available in this group']);
            }

            foreach ($users as $user) {
                TprmCampaignLive::create([
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

            TprmCampaignReport::create([
                'campaign_id' => $campId,
                'campaign_name' => $campName,
                'campaign_type' => $campaignType,
                'status' => 'running',
                'email_lang' => $emailLang,
                'training_lang' => $trainingLang,
                'scheduled_date' => $launchTimeFormatted,
                'company_id' => $companyId,
            ]);

            TprmCampaign::create([
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
                'timeZone' => $timeZone,
                'expire_after' => $expAfter,
                'status' => 'running',
                'company_id' => $companyId,
            ]);

            return response()->json(['status' => 1, 'msg' => 'Campaign created and running!']);
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


        try {
            // Start transaction
            DB::beginTransaction();

            // Check if campaign exists
            $campaign = TprmCampaign::where('campaign_id', $campid)->first();
            if (!$campaign) {
                return response()->json(['status' => 0, 'msg' => 'Campaign not found'], 404);
            }


            $res1 = TprmCampaign::where('campaign_id', $campid)->delete();

            $res2 = TprmCampaignLive::where('campaign_id', $campid)->delete();

            $res3 = TprmCampaignReport::where('campaign_id', $campid)->delete();

            // Check if any records were deleted
            if ($res1 || $res2 || $res3) {
                DB::commit();
                return response()->json(['status' => 1, 'msg' => 'Campaign deleted successfully']);
            } else {
                DB::rollBack();
                return response()->json(['status' => 0, 'msg' => 'No records deleted'], 400);
            }
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['status' => 0, 'msg' => 'Error: ' . $e->getMessage()], 500);
        }
    }



    public function relaunchCampaign(Request $request)
    {

        $campid = base64_decode($request->campid);
        $dateTime = Carbon::now();
        $formattedDateTime = $dateTime->format('m/d/Y g:i A');

        $company_id = Auth::user()->company_id;

        TprmCampaign::where('campaign_id', $campid)->update([
            'launch_time' => $formattedDateTime,
            'status' => 'running'
        ]);

        TprmCampaignReport::where('campaign_id', $campid)
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
        TprmCampaignLive::where('campaign_id', $campid)->delete();

        $campaign = TprmCampaign::where('campaign_id', $campid)->first();

        $users = TprmUsers::where('group_id', $campaign->users_group)->get();

        if ($users->isEmpty()) {
        }

        foreach ($users as $user) {
            TprmCampaignLive::create([
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

            $isreportexist = TprmCampaignReport::where('campaign_id', $campaign->campaign_id)->first();

            if (!$isreportexist) {
                TprmCampaignReport::create([
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->campaign_name,
                    'campaign_type' => $campaign->campaign_type,
                    'status' => 'running',
                    'email_lang' => $campaign->email_lang,
                    'training_lang' => $campaign->training_lang,
                    'scheduled_date' => $launchTime,
                    'company_id' => $companyId,
                ]);
            } else {
                TprmCampaignReport::where('campaign_id', $campaign->campaign_id)->update([
                    'scheduled_date' => $launchTime,
                    'status' => 'running'
                ]);
            }
        }


        return redirect()->back()->with('success', 'Campaign rescheduled successfully');
    }

    private function makeCampaignLive($campaignid, $launch_time, $email_freq, $expire_after)
    {
        $companyId = Auth::user()->company_id;
        // Retrieve the campaign instance
        $campaign = TprmCampaign::where('id', $campaignid)->first();

        // Retrieve the users in the specified group
        $users = TprmUsers::where('group_id', $campaign->users_group)->get();

        // Check if users exist in the group
        if ($users->isEmpty()) {
            return redirect()->back()->with('error', 'No employees available in this group GroupID:' . $campaign->users_group);
        } else {
            // Iterate through the users and create CampaignLive entries
            foreach ($users as $user) {
                TprmCampaignLive::create([
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
    public function fetchEmail(Request $request)
    {
        $token = $this->getAccessToken();

        // Check if access token was retrieved successfully
        if (empty($token)) {
            return response()->json([
                'message' => 'Failed to retrieve access token.',
            ], 500);
        }

        // Prepare the API request parameters
        $params = [
            'access_token' => $token,
            'domain'       => $request->domain,
            'type'         => 'all',
            'limit'        => 10,
            'lastId'       => 0,
        ];

        // Make the API request using Laravel's HTTP client
        $response = Http::timeout(30) // Set a timeout to prevent hanging
            ->withOptions(['verify' => false])
            ->get('https://api.snov.io/v2/domain-emails-with-info', $params);

        // Log the raw response for debugging
        Log::info('Raw API response:', ['response' => $response->body(), 'status' => $response->status()]);

        // Handle unsuccessful HTTP responses
        if (!$response->successful()) {
            return response()->json([
                'message'  => 'Failed to fetch data from API.',
                'httpCode' => $response->status(),
            ], 500);
        }

        // Decode the JSON response
        $res = $response->json();

        // Check if the response contains data
        if (isset($res['data']) && is_array($res['data'])) {
            // Save the fetched emails to the database
            foreach ($res['data'] as $emailData) {
                DomainEmail::create([
                    'domain' => $res['meta']['domain'] ?? 'N/A', // Default if domain is not present
                    'email'  => $emailData['email'] ?? 'N/A',    // Default if email is not present
                    'status' => $emailData['status'] ?? 'N/A',   // Default if status is not present
                ]);
            }

            return response()->json([
                'message' => 'Emails fetched and saved successfully!',
                'domain'  => $res['meta']['domain'] ?? 'Unknown', // Add a default value
                'emails'  => $res['data'],
            ]);
        } else {
            return response()->json([
                'message' => 'No emails found for this domain.',
                'domain'  => $res['meta']['domain'] ?? 'Unknown', // Add a default value
                'emails'  => [],
            ]);
        }
    }




    public function getAccessToken()
    {
        $response = Http::asForm()->withOptions(['verify' => false])->post('https://api.snov.io/v1/oauth/access_token', [
            'grant_type'    => 'client_credentials',
            'client_id'     => '8bd9fa3d70268f7d3594b5349c4c9230',  // Replace with your client ID
            'client_secret' => 'fc7b92d3dc94798f9044193fc4b21400'   // Replace with your client secret
        ]);

        // Check for a successful response
        if ($response->successful()) {
            $data = $response->json();

            // Debug: Print the entire response for troubleshooting
            // print_r($data); // This will display the response structure

            // Check if the access_token exists and return it, otherwise handle the error
            if (isset($data['access_token'])) {
                return $data['access_token'];
            } else {
                // Handle the case where the access token is not available
                return $data; // or you can throw an exception or handle it accordingly
            }
        } else {
            // Handle an unsuccessful response
            return ['error' => 'Failed to fetch access token', 'status' => $response->status()];
        }
    }
    public function viewEmails()
    {
        $emails = DomainEmail::all();
        return view('emails', ['emails' => $emails]);
    }

    public function tprmnewGroup(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'domainName' => 'required|string',  // Ensure 'domainName' is passed correctly
            'emails' => 'required|array',       // Ensure 'emails' is passed as an array
        ]);

        // Process the request if validation passes
        $domainName = $request->input('domainName'); // Get the domainName from the request
        $emails = $request->input('emails'); // Get the emails from the request
        $companyId = auth()->user()->company_id;



        // Check if the combination of domain name and company ID already exists
        $existingGroup = TprmUsersGroup::where('group_name', $domainName)
            ->where('company_id', $companyId)
            ->first();

        if ($existingGroup) {

            foreach ($emails as $usrEmail) {
                if ($this->TprmdomainVerified($usrEmail, $companyId)) {
                    if ($this->TprmuniqueEmail($usrEmail)) {
                        // Extract username from email (part before '@')
                        $userName = explode('@', $usrEmail)[0];

                        $user = new TprmUsers();
                        $user->group_id = $existingGroup->group_id;
                        $user->user_email = $usrEmail;
                        $user->user_name = $userName;  // Set the username
                        $user->company_id = $companyId;
                        $user->save();
                    } else {
                    }
                } else {
                }
            }
            return redirect()->route('tprmcampaigns')->with('success', 'Emails added to the existing group successfully.');
        }

        // Generate a new group ID
        $grpId = generateRandom(6);

        // Create a new group using the domain name
        TprmUsersGroup::create([
            'group_id' => $grpId,
            'group_name' => $domainName,
            'users' => null,
            'company_id' => $companyId,
        ]);


        // If new group created, add emails to the tprm_users database
        foreach ($emails as $usrEmail) {
            if ($this->TprmdomainVerified($usrEmail, $companyId)) {
                if ($this->TprmuniqueEmail($usrEmail)) {
                    // Extract username from email (part before '@')
                    $userName = explode('@', $usrEmail)[0];

                    $user = new TprmUsers();
                    $user->group_id = $grpId;
                    $user->user_email = $usrEmail;
                    $user->user_name = $userName;  // Set the username
                    $user->company_id = $companyId;
                    $user->save();
                } else {
                }
            } else {
            }
        }

        return redirect()->route('tprmcampaigns')->with('success', 'New group created and emails added successfully.');
    }

    private function TprmdomainVerified($email, $companyId)
    {
        $domain = explode("@", $email)[1];
        $checkDomain = TpmrVerifiedDomain::where('domain', $domain)
            ->where('verified', 1)
            ->where('company_id', $companyId)
            ->exists();

        return $checkDomain;
    }


    public function emailtprmnewGroup(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'domainName' => 'required|string',  // Ensure 'domainName' is passed correctly
            'emails' => 'required|array',       // Ensure 'emails' is passed as an array
        ]);

        // Process the request if validation passes
        $domainName = $request->input('domainName'); // Get the domainName from the request
        $emails = $request->input('emails'); // Get the emails from the request
        $companyId = auth()->user()->company_id;


        // Check if the combination of domain name and company ID already exists
        $existingGroup = TprmUsersGroup::where('group_name', $domainName)
            ->where('company_id', $companyId)
            ->first();

        if ($existingGroup) {

            foreach ($emails as $usrEmail) {
                if ($this->TprmdomainVerified($usrEmail, $companyId)) {
                    if ($this->TprmuniqueEmail($usrEmail)) {
                        // Extract username from email (part before '@')
                        $userName = explode('@', $usrEmail)[0];

                        $user = new TprmUsers();
                        $user->group_id = $existingGroup->group_id;
                        $user->user_email = $usrEmail;
                        $user->user_name = $userName;  // Set the username
                        $user->company_id = $companyId;
                        $user->save();
                    } else {
                    }
                } else {
                }
            }
            return response()->json(['status' => 1, 'message' => 'Success']);
        }

        // Generate a new group ID
        $grpId = generateRandom(6);

        // Create a new group using the domain name
        TprmUsersGroup::create([
            'group_id' => $grpId,
            'group_name' => $domainName,
            'users' => null,
            'company_id' => $companyId,
        ]);


        // If new group created, add emails to the tprm_users database
        foreach ($emails as $usrEmail) {
            if ($this->TprmdomainVerified($usrEmail, $companyId)) {
                if ($this->TprmuniqueEmail($usrEmail)) {
                    // Extract username from email (part before '@')
                    $userName = explode('@', $usrEmail)[0];

                    $user = new TprmUsers();
                    $user->group_id = $grpId;
                    $user->user_email = $usrEmail;
                    $user->user_name = $userName;  // Set the username
                    $user->company_id = $companyId;
                    $user->save();
                } else {
                }
            } else {
            }
        }

        return response()->json(['status' => 1, 'message' => 'Success']);
    }

    private function TprmuniqueEmail($email)
    {
        return !TprmUsers::where('user_email', $email)->exists();
    }

    public function getEmailsByDomain($domain)
    {
        $companyId = auth()->user()->company_id;
        // Fetch emails from the database based on the domain
        $emails = TprmUsers::where('user_email', 'like', '%' . $domain)->where('company_id', $companyId)->pluck('user_email');

        return response()->json($emails); // Return emails as JSON response
    }
}
