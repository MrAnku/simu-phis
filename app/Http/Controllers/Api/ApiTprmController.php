<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Company;
use App\Models\TprmUsers;
use App\Models\DomainEmail;
use App\Models\TprmRequest;
use App\Models\TprmCampaign;
use Illuminate\Http\Request;
use App\Models\PhishingEmail;
use App\Models\CompanyLicense;
use App\Models\TprmUsersGroup;
use App\Models\TprmCampaignLive;
use App\Models\TpmrVerifiedDomain;
use App\Models\TprmCampaignReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\DeletedTprmEmployee;
use App\Models\TprmActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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

            log_action('Request submitted for TPRM vishing simulation for company : ' . Auth::user()->company_name);
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
            log_action("New domain : {$requested['domain']} verification requested");
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
            TpmrVerifiedDomain::where('domain', $domain)->where('company_id', Auth::user()->company_id)->delete();

            $groupExist = TprmUsersGroup::where('group_name', $domain)->first();
            if ($groupExist) {
                TprmUsers::where('group_id', $groupExist->group_id)->delete();
                $campExist = TprmCampaign::where('users_group', $groupExist->group_id)->first();
                if ($campExist) {
                    TprmCampaignLive::where('campaign_id', $campExist->campaign_id)->delete();
                    TprmCampaignReport::where('campaign_id', $campExist->campaign_id)->delete();
                    $campExist->delete();
                }
                $groupExist->delete();
            }

            log_action("Domain deleted : {$domain}");
            return response()->json(['success' => true, 'message' => __('Domain deleted successfully')], 200);
            // $tprmUserGroup = TprmUsersGroup::where('group_name', $domain)
            //     ->where('company_id', Auth::user()->company_id)->first();

            // if (!$tprmUserGroup) {
            //     return response()->json(['success' => false, 'message' => __('Domain not found')], 404);
            // }

            // if ($tprmUserGroup) {
            //     $group_id = $tprmUserGroup->group_id;
            //     $tprmUserGroup->delete();

            //     $tprmUsers = TprmUsers::where('group_id', $group_id)->get();

            //     foreach ($tprmUsers as $tprmUser) {
            //         $tprmUser->delete();
            //     }
            //     $tprmCampaign = TprmCampaign::where('users_group', $group_id)->first();

            //     if ($tprmCampaign) {

            //         $tprmCampaignId = $tprmCampaign->campaign_id;
            //         $tprmCampaign->delete();
            //         $TprmLiveCampaigns = TprmCampaignLive::where('campaign_id', $tprmCampaignId)->get();

            //         foreach ($TprmLiveCampaigns as $TprmLiveCampaign) {
            //             $TprmLiveCampaign->delete();
            //         }
            //     }
            // }

            // DB::beginTransaction();

            // try {
            //     // 1. Delete users associated with the domain
            //     $users = TprmUsers::where('user_email', 'LIKE', '%' . $domain)->get();
            //     foreach ($users as $user) {

            //         // Delete user-related data
            //         DB::table('user_login')->where('user_id', $user->id)->delete();
            //         // Delete the user record itself
            //         $user->delete();
            //     }

            //     // 2. Delete user groups associated with the domain (use `group_name` instead of `domain`)
            //     $userGroups = TprmUsersGroup::where('group_name', $domain)->get();
            //     foreach ($userGroups as $group) {
            //         $groupId = $group->group_id;
            //         $companyId = Auth::user()->company_id;


            //         // Delete all users in the group
            //         TprmUsers::where('group_id', $groupId)->delete();

            //         // Delete the group itself
            //         $group->delete();

            //         // 3. Delete campaigns associated with this user group
            //         $campaigns = TprmCampaign::where('users_group', $groupId)
            //             ->where('company_id', $companyId)
            //             ->get();

            //         foreach ($campaigns as $campaign) {
            //             $campaignId = $campaign->campaign_id;

            //             // Delete campaign records from all associated tables
            //             TprmCampaign::where('campaign_id', $campaignId)
            //                 ->where('company_id', $companyId)
            //                 ->delete();
            //             TprmCampaignLive::where('campaign_id', $campaignId)
            //                 ->where('company_id', $companyId)
            //                 ->delete();
            //             TprmCampaignReport::where('campaign_id', $campaignId)
            //                 ->where('company_id', $companyId)
            //                 ->delete();
            //         }
            //     }

            //     // 4. Finally, delete the domain itself
            //     TpmrVerifiedDomain::where('domain', $domain)->delete();

            //     // Commit the transaction if all deletions are successful
            //     DB::commit();

            //     return response()->json(['success' => true, 'message' => __('Domain and associated data deleted successfully')], 200);
        } catch (\Exception $e) {
            // Rollback the transaction if an error occurs
            DB::rollBack();
            return response()->json(['success' => false, 'message' => __('An error occurred: ') . $e->getMessage()], 500);
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
                $camp_live = TprmCampaignLive::create([
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

                TprmActivity::create([
                    'campaign_id' => $campId,
                    'campaign_live_id' => $camp_live->id,
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

            log_action("TPRM Campaign created and running : {$campName}");

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
            $campaign_name = $campaign->campaign_name;

            if (!$campaign) {
                return response()->json(['success' => false, 'message' => __('Campaign not found')], 404);
            }

            $res1 = TprmCampaign::where('campaign_id', $campId)->delete();

            $res2 = TprmCampaignLive::where('campaign_id', $campId)->delete();

            $res3 = TprmCampaignReport::where('campaign_id', $campId)->delete();
            TprmActivity::where('campaign_id', $campId)
                ->where('company_id', Auth::user()->company_id)
                ->delete();

            // Check if any records were deleted
            if ($res1 || $res2 || $res3) {
                DB::commit();
                log_action("TPRM Campaign deleted : {$campaign_name}");

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
            log_action("TPRM Campaign relaunched : {$campaign->campaign_name}");

            return response()->json(['success' => true, 'message' => __('Campaign relaunched successfully!')], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
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

    public function fetchEmail(Request $request)
    {
        try {
            $token = $this->getAccessToken();

            // Check if access token was retrieved successfully
            if (empty($token)) {
                return response()->json(['success' => false, 'message' => __('Failed to retrieve access token.'),], 422);
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
                    'success' => false,
                    'message'  => __('Failed to fetch data from API.'),
                ], $response->status());
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
                    'success' => true,
                    'message' => 'Emails fetched and saved successfully!',
                    'data' => [
                        'domain'  => $res['meta']['domain'] ?? 'Unknown', // Add a default value
                        'emails'  => $res['data']
                    ],
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No emails found for this domain.',
                    'data' => [
                        'domain'  => $res['meta']['domain'] ?? 'Unknown', // Add a default value
                        'emails'  => []
                    ],
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function getAccessToken()
    {
        try {
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
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to fetch access token.')
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function addGroupUser(Request $request)
    {
        try {
            $request->validate([
                'domainName' => 'required|string',
                'emails' => 'required|array',
            ]);

            // Process the request if validation passes
            $domainName = $request->input('domainName');
            $emails = $request->input('emails');
            $companyId = Auth::user()->company_id;
            $message = null;

            $domainVerified = TpmrVerifiedDomain::where('domain', $domainName)
                ->where('company_id', $companyId)
                ->where(['verified' => 1])->first();

            if ($domainVerified) {
                $existingGroup = TprmUsersGroup::where('group_name', $domainName)->where('company_id', $companyId)->first();

                if (!$existingGroup) {
                    $tprmGroup = TprmUsersGroup::create([
                        'group_id' => generateRandom(6),
                        'group_name' => $domainName,
                        'users' => null,
                        'company_id' => $companyId,
                    ]);
                    $message = __('New group Created');
                }

                //check License limit
                $company_license = CompanyLicense::where('company_id', $companyId)->first();

                if ($company_license->used_tprm_employees >= $company_license->tprm_employees) {
                    return response()->json(['success' => false, 'message' => __('Employee limit exceeded')], 422);
                }

                // Check License Expiry
                if (now()->toDateString() > $company_license->expiry) {
                    return response()->json(['success' => false, 'message' => __('Your License has beeen Expired')], 422);
                }

                foreach ($emails as $userEmail) {
                    $emailDomain = explode('@', $userEmail)[1];

                    if ($emailDomain != $domainName) {
                        return response()->json(['success' => false, 'message' => __('Email domain does not match the group domain')], 422);
                    }
                    $emailDomainVerified = TpmrVerifiedDomain::where('domain', $emailDomain)
                        ->where('company_id', $companyId)
                        ->where(['verified' => 1])->first();

                    if (!$emailDomainVerified) {
                        return response()->json(['success' => false, 'message' => __('Domain: ') . $emailDomain . ' ' .  __('is not verified')], 422);
                    }

                    $emailExists = TprmUsers::where('user_email', $userEmail)->where('company_id', $companyId)->first();
                    if ($emailExists) {
                        return response()->json(['success' => false, 'message' => __('Email: ') . ' ' . $emailExists->user_email . ' ' . __('already exists')], 422);
                    }

                    $tprmUsers = new TprmUsers();
                    $tprmUsers->group_id = $existingGroup ? $existingGroup->group_id : $tprmGroup->group_id;
                    $tprmUsers->user_name = explode('@', $userEmail)[0];
                    $tprmUsers->user_email = $userEmail;
                    $tprmUsers->company_id = $companyId;
                    $tprmUsers->save();

                    $userExists = TprmUsers::where('user_email', $userEmail)
                        ->where('company_id', Auth::user()->company_id)
                        ->exists();

                    $deletedEmployee = DeletedTprmEmployee::where('email', $userEmail)
                        ->where('company_id', Auth::user()->company_id)
                        ->exists();

                    if (!$userExists && !$deletedEmployee) {
                        if ($company_license) {
                            $company_license->increment('used_tprm_employees');
                        }
                    }
                }
                if (!$message) {
                    log_action("Users added to group : {$domainName}");
                    return response()->json(['success' => true, 'message' => __('Users added to group successfully')], 200);
                } else {
                    log_action("Users added to group : {$domainName}");
                    return response()->json(['success' => true, 'message' => $message . ' ' . __('and users added to that group successfully')], 200);
                }
            } else {
                return response()->json(['success' => false, 'message' => __('Domain is not verified')], 422);
            }
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function getEmailsByDomain(Request $request)
    {
        try {
            if (!$request->route('domain')) {
                return response()->json(['success' => false, 'message' => __('Domain is required')], 422);
            }
            $companyId = Auth::user()->company_id;
            // Fetch emails from the database based on the domain
            $emails = TprmUsers::where('user_email', 'like', '%' . $request->route('domain'))->where('company_id', $companyId)->pluck('user_email');

            if (!$emails) {
                return response()->json(['success' => false, 'message' => __('No emails found for this domain')], 404);
            }

            return response()->json(['success' => true, 'data' => $emails, 'message' => __('Emails fetched by domain successfully')]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function deleteTprmUserByEmail(Request $request)
    {
        try {
            $request->validate([
                'user_email' => 'required'
            ]);
            $user_email = $request->input('user_email');
            $user = TprmUsers::where('user_email', $user_email)->where('company_id', Auth::user()->company_id)->first();
            if (!$user) {
                return response()->json(['success' => false, 'message' => __('TPRM Employee not found')], 404);
            }
            TprmCampaignLive::where('user_id', $user->id)->where('company_id', Auth::user()->company_id)->delete();

            $user->delete();

            $emailExists = DeletedTprmEmployee::where('email', $user_email)->where('company_id', Auth::user()->company_id)->exists();
            if (!$emailExists) {
                DeletedTprmEmployee::create([
                    'email' => $user_email,
                    'company_id' => Auth::user()->company_id,
                ]);
            }

            log_action("TPRM Employee deleted : {$user_email}");
            return response()->json(['success' => true, 'message' => __('TPRM Employee deleted successfully')], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }
}
