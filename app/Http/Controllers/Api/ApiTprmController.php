<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PhishingEmail;
use App\Models\TpmrVerifiedDomain;
use App\Models\TprmCampaign;
use App\Models\TprmCampaignLive;
use App\Models\TprmCampaignReport;
use App\Models\TprmRequest;
use App\Models\TprmUsers;
use App\Models\TprmUsersGroup;
use App\Models\TrainingModule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApiTprmController extends Controller
{
    public function index()
    {
        try {
            $companyId = Auth::user()->company_id;
            $allCamps = TprmCampaign::where('company_id', $companyId)->orderBy('id', 'desc')
                ->paginate(10);

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
            $companyId = Auth::user()->company_id;

            $allDomains = TpmrVerifiedDomain::where('company_id', $companyId)->get();

            $companyId = Auth::user()->company_id;

            $company = TprmRequest::where('company_id', $companyId)->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'allCamps' => $allCamps,
                    'usersGroups' => $usersGroups,
                    'phishingEmails' => $phishingEmails,
                    'allDomains' => $allDomains,
                    'company' => $company
                ],
                'message' => 'Tprm Data fetched successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
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

    public function submitReq(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $partnerId = Auth::user()->partner_id;

            TprmRequest::create([
                'company_id' => $companyId,
                'partner_id' => $partnerId,
                'status' => 0
            ]);

            log_action('Request submitted for TPRM vishing simulation');
            return response()->json(['success' => true, 'message' => __('Your request has been submitted successfully.')], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function submitdomains(Request $request)
    {
        try {
            //xss check start
            $input = $request->all();
            foreach ($input as $key => $value) {
                if (is_array($value)) {
                    array_walk_recursive($value, function ($item) {
                        if (preg_match('/<[^>]*>|<\?php/', $item)) {
                            return response()->json(['success' => false, 'message' => __('Invalid input detected.')], 422);
                        }
                    });
                } else {
                    if (preg_match('/<[^>]*>|<\?php/', $value)) {
                        return response()->json(['success' => false, 'message' => __('Invalid input detected.')], 422);
                    }
                }
            }
            //xss check end


            // Retrieve domains from the request, assuming JSON format
            $domains = $request->input('domains');

            if (count($domains) >= 6) {
                return response()->json(['success' => false, 'message' => __('You can only add 5 domains at a time')], 422);
            }

            // Validate that the input is an array
            if (!is_array($domains) || empty($domains)) {
                return response()->json(['success' => false, 'message' => __('Please provide one or more domains in an array format.')], 422);
            }

            // Get the company ID of the authenticated user
            $companyId = Auth::user()->company_id;

            // Retrieve the partner ID from the Company table using the correct primary key
            $partnerId = Company::where('company_id', $companyId)->value('partner_id');

            // Check if partnerId is null
            if (!$partnerId) {
                return response()->json(['success' => false, 'message' => __('Partner ID not found for the specified company')], 404);
            }

            // Array to store the result of each domain verification attempt
            $requested = [];
            $error = [];

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
                    $error[] = [
                        'domain' => $domain,
                        'message' => __('Domain:') . ' ' . $domain . ' ' . __('already exists')
                    ];
                } else if ($verifiedDomain) {
                    $error[] = [
                        'domain' => $domain,
                        'message' => __('Domain:') . ' ' . $domain . ' ' . __('already verified by another company')
                    ];
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


                        $requested[] = [
                            'domain' => $domain,
                            'message' => __('Domain:') . ' ' . $domain . ' ' . __('verification requested successfully'),
                        ];
                    } else {
                        return response()->json(['success' => false, 'message' => __('Failed to verify domain; partner ID is missing.')], 422);
                    }
                }
            }

            if (count($error) > 0 && count($requested) > 0) {
                return response()->json([
                    'success' => false,
                    'message' => __('Some domains already exists or are verified by another company and also some new domain verification requested successfully'),
                    'data' => [
                        'errors' => $error,
                        'requested' => $requested
                    ]
                ], 422);
            } else if (count($error) > 0) {
                return response()->json([
                    'success' => false,
                    'message' => __('Some domains already exists or are verified by another company'),
                    'data' => [
                        'errors' => $error
                    ]
                ], 422);
            }
            return response()->json([
                'success' => true,
                'data' => ['requested' => $requested],
                'message' => __('New domain verification requested successfully.')
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function test()
    {
        try {
            return response()->json(['success' => true, 'message' => __('Frontend is working correctly!')], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function deleteDomain(Request $request)
    {
        try {
            // $domain = $request->vDomainId;
            $domain = $request->query('domain');
            if (!$domain) {
                return response()->json(['success' => false, 'message' => __('Domain is required')], 404);
            }
            $tprmUserGroup = TprmUsersGroup::where('group_name', $domain)
                ->where('company_id', Auth::user()->company_id)->first();

            if (!$tprmUserGroup) {
                return response()->json(['success' => false, 'message' => __('Domain not found')], 404);
            }

            if ($tprmUserGroup) {
                $group_id = $tprmUserGroup->group_id;
                $tprmUserGroup->delete();

                $tprmUsers = TprmUsers::where('group_id', $group_id)->get();

                foreach ($tprmUsers as $tprmUser) {
                    $tprmUser->delete();
                }
                $tprmCampaign = TprmCampaign::where('users_group', $group_id)->first();

                if ($tprmCampaign) {

                    $tprmCampaignId = $tprmCampaign->campaign_id;
                    $tprmCampaign->delete();
                    $TprmLiveCampaigns = TprmCampaignLive::where('campaign_id', $tprmCampaignId)->get();

                    foreach ($TprmLiveCampaigns as $TprmLiveCampaign) {
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

                return response()->json(['success' => true, 'message' => __('Domain and associated data deleted successfully')], 200);
            } catch (\Exception $e) {
                // Rollback the transaction if an error occurs
                DB::rollBack();
                return response()->json(['success' => false, 'message' => __('An error occurred: ') . $e->getMessage()], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function createCampaign(Request $request)
    {
        try {
            $request->validate([
                'camp_name' => 'required|string|max:255',
                'users_group' => 'required',
                'email_lang' => 'required|string',
                'phish_material' => 'required|integer',
            ]);
            //xss check start
            $input = $request->all();
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json(['success' => false, 'message' => __('Invalid input detected.')], 422);
                }
            }
            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);

            //xss check end
            $campName = $request->input('camp_name');
            $usersGroup = $request->input('users_group');
            $emailLang = $request->input('email_lang');
            $phishMaterial = $request->input('phish_material');
            $companyId = Auth::user()->company_id;


            $phishingEmail = PhishingEmail::where('id', $phishMaterial)
                ->where(function ($query) {
                    $query->where('senderProfile', '0')
                        ->orWhere('website', '0');
                })
                ->first();

            if ($phishingEmail) {
                return response()->json(['success' => false, 'message' => __('Sender profile or Website is not associated with the selected phishing email template')], 422);
            }

            $campId = generateRandom(); // Assuming you have a method to generate a random ID

            // $scheduledDate = Carbon::createFromFormat("m/d/Y H:i", 'immediately');
            // $launchTimeFormatted = $scheduledDate->format("m/d/Y g:i A");

            $scheduledDate = Carbon::now();
            $launchTimeFormatted = $scheduledDate->format("m/d/Y g:i A");


            $users = TprmUsers::where('group_id', $usersGroup)->get();

            if ($users->isEmpty()) {
                return response()->json(['success' => false, 'message' => __('No employees available in this group')], 422);
            }

            foreach ($users as $user) {
                TprmCampaignLive::create([
                    'campaign_id' => $campId,
                    'campaign_name' => $campName,
                    'user_id' => $user->id,
                    'user_name' => $user->user_name,
                    'user_email' => $user->user_email,
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
                'campaign_type' => 'Phishing',
                'status' => 'running',
                'email_lang' => $emailLang,
                'scheduled_date' => $launchTimeFormatted,
                'company_id' => $companyId,
            ]);

            TprmCampaign::create([
                'campaign_id' => $campId,
                'campaign_name' => $campName,
                'campaign_type' => 'Phishing',
                'users_group' => $usersGroup,
                'phishing_material' => $phishMaterial,
                'email_lang' => $emailLang,
                'launch_time' => $launchTimeFormatted,
                'launch_type' => 'immediately',
                'email_freq' => 'one',
                'startTime' => '00:00:00',
                'endTime' => '00:00:00',
                'timeZone' => 'Australia/Canberra',
                'status' => 'running',
                'company_id' => $companyId,
            ]);

            return response()->json(['success' => true, 'message' => __('Campaign created and running!')], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function deleteCampaign(Request $request)
    {
        try {
            $campId = $request->route('campId');
            if (!$campId) {
                return response()->json(['success' => false, 'message' => __('Campaign ID is required')], 422);
            }
            // Start transaction
            DB::beginTransaction();

            // Check if campaign exists
            $campaign = TprmCampaign::where('campaign_id', $campId)->first();
            if (!$campaign) {
                return response()->json(['success' => false, 'message' => __('Campaign not found')], 404);
            }

            $res1 = TprmCampaign::where('campaign_id', $campId)->delete();

            $res2 = TprmCampaignLive::where('campaign_id', $campId)->delete();

            $res3 = TprmCampaignReport::where('campaign_id', $campId)->delete();

            // Check if any records were deleted
            if ($res1 || $res2 || $res3) {
                DB::commit();
                return response()->json(['success' => true, 'message' => __('Campaign deleted successfully.')], 200);
            } else {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => __('No records deleted')], 400);
            }
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function relaunchCampaign(Request $request)
    {
        try {
            $campId = $request->route('campId');
            if (!$campId) {
                return response()->json(['success' => false, 'message' => __('Campaign ID is required')], 422);
            }
            $dateTime = Carbon::now();
            $formattedDateTime = $dateTime->format('m/d/Y g:i A');

            $company_id = Auth::user()->company_id;

            TprmCampaign::where('campaign_id', $campId)->update([
                'launch_time' => $formattedDateTime,
                'status' => 'running'
            ]);

            TprmCampaignReport::where('campaign_id', $campId)
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
            TprmCampaignLive::where('campaign_id', $campId)->delete();

            $campaign = TprmCampaign::where('campaign_id', $campId)->first();

            $users = TprmUsers::where('group_id', $campaign->users_group)->get();

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
            return response()->json(['success' => true, 'message' => __('Campaign relaunched successfully!')], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function fetchPhishData(Request $request)
    {
        try {
            $website = $request->input('website_id');
            $senderProfile = $request->input('senderProfile_id');

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

            return response()->json(['success' => true, 'data' => $phishData, 'message' => 'Phishing Data fetched successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function rescheduleCampaign(Request $request)
    {
        try {
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
            return response()->json(['success' => true, 'message' => __('Campaign rescheduled successfully')], 200);
        }catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        }
        catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    private function makeCampaignLive($campaignid, $launch_time, $email_freq, $expire_after)
    {
        try {
            $companyId = Auth::user()->company_id;
            // Retrieve the campaign instance
            $campaign = TprmCampaign::where('id', $campaignid)->first();

            // Retrieve the users in the specified group
            $users = TprmUsers::where('group_id', $campaign->users_group)->get();

            // Check if users exist in the group
            if ($users->isEmpty()) {
                return response()->json(['success' => false, 'message' => __('No employees available in this group GroupID:') . $campaign->users_group], 422);
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
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }
}
