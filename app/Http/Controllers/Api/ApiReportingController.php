<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Users;
use App\Models\Policy;
use App\Models\Campaign;
use App\Models\TprmUsers;
use App\Models\UsersGroup;
use App\Models\WaCampaign;
use App\Models\CampaignLive;
use App\Models\QuishingCamp;
use App\Models\TprmCampaign;
use App\Models\TrainingGame;
use Illuminate\Http\Request;
use App\Models\PhishingEmail;
use App\Models\AiCallCampaign;
use App\Models\AiCallCampLive;
use App\Models\AssignedPolicy;
use App\Models\CampaignReport;
use App\Models\CompanyLicense;
use App\Models\TrainingModule;
use App\Models\WaLiveCampaign;
use App\Models\BlueCollarGroup;
use App\Models\CompanySettings;
use App\Models\PhishingWebsite;
use App\Models\QuishingLiveCamp;
use App\Models\TprmCampaignLive;
use App\Models\WhatsappCampaign;
use Illuminate\Http\JsonResponse;
use App\Models\BlueCollarEmployee;
use App\Models\TprmCampaignReport;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\TrainingAssignedUser;
use App\Models\WhatsAppCampaignUser;
use Illuminate\Support\Facades\Auth;
use App\Models\BlueCollarTrainingUser;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class ApiReportingController extends Controller
{
    //
    public function index()
    {
        try {
            $companyId = Auth::user()->company_id;

            $camps = CampaignReport::where('company_id', $companyId)->get();
            $emails_delivered = $camps->sum('emails_delivered');
            $training_assigned = $camps->sum('training_assigned');

            $whatsapp_campaigns = WhatsappCampaign::with('targetUsers')
                ->where('company_id', $companyId)->get();

            $tprmcamps = TprmCampaignReport::where('company_id', $companyId)->get();
            $tprm_campaigns = TprmCampaign::with('tprmReport')
                ->where('company_id', $companyId)->get();

            $tprmemails_delivered = $tprmcamps->sum('emails_delivered');
            $tprmemails_reported = $tprmcamps->sum('email_reported');
            $emp_compromised_reported = $tprmcamps->sum('emp_compromised');
            $payloads_clicked_reported = $tprmcamps->sum('payloads_clicked');

            Session::forget('campaign_details');

            $Arraydetails = [
                'Emails Delivered' => $tprmemails_delivered ?? 0,
                'TPRM Email Report' => $tprmemails_reported ?? 0,
                'Emp Compromised' => $emp_compromised_reported ?? 0,
                'Payload Clicked' => $payloads_clicked_reported ?? 0
            ];

            $ai_calls = AiCallCampaign::with('individualCamps')
                ->where('company_id', $companyId)->get();

            $ai_calls_individual = AiCallCampLive::where('company_id', $companyId)->get();

            $wcamps = WhatsappCampaign::where('company_id', $companyId)->get();
            $ccamps = AiCallCampaign::with('individualCamps')
                ->where('company_id', $companyId)->get();

            $msg_delivered = WhatsAppCampaignUser::where('company_id', $companyId)
                ->where('status', 'sent')->count();

            $call_delivered = $ccamps->where('status', 'completed')->count();

            $wtraining_assigned = DB::table('whatsapp_camp_users')
                ->where('company_id', $companyId)->sum('training_assigned');

            $ctraining_assigned = $ccamps->sum('training_assigned');

            return response()->json([
                'success' => true,
                'message' => __('Campaign data fetched successfully'),
                'data' => [
                    'campaign_reports' => $camps,
                    'emails_delivered' => $emails_delivered,
                    'training_assigned' => $training_assigned,
                    'msg_delivered' => $msg_delivered,
                    'whatsapp_campaigns' => $whatsapp_campaigns,
                    'wcamps' => $wcamps,
                    'wtraining_assigned' => $wtraining_assigned,
                    'call_delivered' => $call_delivered,
                    'ccamps' => $ccamps,
                    'ctraining_assigned' => $ctraining_assigned,
                    'ai_calls' => $ai_calls,
                    'ai_calls_individual' => $ai_calls_individual,
                    'tprm_campaigns' => $tprm_campaigns,
                    'tprm_stats' => $Arraydetails
                ]
            ], 200);
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



    public function getChartData()
    {
        try {
            $companyId = Auth::user()->company_id;

            $startDate = Carbon::now()->subDays(11)->startOfDay()->format('Y-m-d H:i:s');
            $endDate = Carbon::now()->endOfDay()->format('Y-m-d H:i:s');

            $data = DB::table('campaign_live')
                ->select(
                    DB::raw('DATE(STR_TO_DATE(launch_time, "%m/%d/%Y %h:%i %p")) as date'),
                    DB::raw('SUM(mail_open) as mail_open'),
                    DB::raw('SUM(payload_clicked) as payload_clicked'),
                    DB::raw('SUM(emp_compromised) as emp_compromised'),
                    DB::raw('SUM(email_reported) as email_reported')
                )
                ->whereBetween(DB::raw('STR_TO_DATE(launch_time, "%m/%d/%Y %h:%i %p")'), [$startDate, $endDate])
                ->where('company_id', $companyId)
                ->groupBy(DB::raw('DATE(STR_TO_DATE(launch_time, "%m/%d/%Y %h:%i %p"))'))
                ->orderBy('date', 'asc')
                ->get();


            $dates = [];
            for ($i = 11; $i >= 0; $i--) {
                $dates[] = Carbon::now()->subDays($i)->format('Y-m-d');
            }

            $formattedData = [
                'dates' => $dates,
                'mail_open' => array_fill(0, 12, 0),
                'payload_clicked' => array_fill(0, 12, 0),
                'employee_compromised' => array_fill(0, 12, 0),
                'email_reported' => array_fill(0, 12, 0),
            ];

            foreach ($data as $item) {
                $index = array_search($item->date, $formattedData['dates']);
                if ($index !== false) {
                    $formattedData['mail_open'][$index] = (int) $item->mail_open;
                    $formattedData['payload_clicked'][$index] = (int) $item->payload_clicked;
                    $formattedData['employee_compromised'][$index] = (int) $item->emp_compromised;
                    $formattedData['email_reported'][$index] = (int) $item->email_reported;
                }
            }

            return response()->json([
                'success' => true,
                'message' => __('Chart data fetched successfully.'),
                'data' => $formattedData,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->validator->errors()->first()
            ], 422);
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


    public function wgetChartData()
    {
        try {
            $companyId = Auth::user()->company_id;
            $startDate = Carbon::now()->subDays(11)->startOfDay()->format('Y-m-d H:i:s');
            $endDate = Carbon::now()->endOfDay()->format('Y-m-d H:i:s');

            $data = DB::table('whatsapp_camp_users')
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(link_clicked) as mail_open'),
                    DB::raw('SUM(training_assigned) as payload_clicked'),
                    DB::raw('SUM(emp_compromised) as emp_compromised'),
                    DB::raw("SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as email_reported")
                )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('company_id', $companyId)
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date', 'asc')
                ->get();

            // Last 12 days
            $dates = [];
            for ($i = 11; $i >= 0; $i--) {
                $dates[] = Carbon::now()->subDays($i)->format('Y-m-d');
            }

            $formattedData = [
                'dates' => $dates,
                'mail_open' => array_fill(0, 12, 0),
                'payload_clicked' => array_fill(0, 12, 0),
                'employee_compromised' => array_fill(0, 12, 0),
                'email_reported' => array_fill(0, 12, 0),
            ];

            foreach ($data as $item) {
                $index = array_search($item->date, $formattedData['dates']);
                if ($index !== false) {
                    $formattedData['mail_open'][$index] = (int) $item->mail_open;
                    $formattedData['payload_clicked'][$index] = (int) $item->payload_clicked;
                    $formattedData['employee_compromised'][$index] = (int) $item->emp_compromised;
                    $formattedData['email_reported'][$index] = (int) $item->email_reported;
                }
            }

            return response()->json([
                'success' => true,
                'message' => __('WhatsApp chart data fetched successfully.'),
                'data' => $formattedData
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->validator->errors()->first()
            ], 422);
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


    public function cgetChartData()
    {
        try {
            $companyId = Auth::user()->company_id;
            $startDate = Carbon::now()->subDays(11)->startOfDay()->format('Y-m-d H:i:s');
            $endDate = Carbon::now()->endOfDay()->format('Y-m-d H:i:s');

            $data = DB::table('ai_call_camp_live')
                ->select(
                    DB::raw('DATE(STR_TO_DATE(created_at, "%m/%d/%Y %h:%i %p")) as date'),
                    DB::raw('SUM(mail_open) as mail_open'),
                    DB::raw('SUM(payload_clicked) as payload_clicked'),
                    DB::raw('SUM(emp_compromised) as emp_compromised'),
                    DB::raw('SUM(training_assigned) as email_reported')
                )
                ->whereBetween(DB::raw('STR_TO_DATE(created_at, "%m/%d/%Y %h:%i %p")'), [$startDate, $endDate])
                ->where('company_id', $companyId)
                ->groupBy(DB::raw('DATE(STR_TO_DATE(created_at, "%m/%d/%Y %h:%i %p"))'))
                ->orderBy('date', 'asc')
                ->get();

            $dates = [];
            for ($i = 11; $i >= 0; $i--) {
                $dates[] = Carbon::now()->subDays($i)->format('Y-m-d');
            }

            $formattedData = [
                'dates' => $dates,
                'mail_open' => array_fill(0, 12, 0),
                'payload_clicked' => array_fill(0, 12, 0),
                'employee_compromised' => array_fill(0, 12, 0),
                'email_reported' => array_fill(0, 12, 0),
            ];

            foreach ($data as $item) {
                $index = array_search($item->date, $formattedData['dates']);
                if ($index !== false) {
                    $formattedData['mail_open'][$index] = (int) $item->mail_open;
                    $formattedData['payload_clicked'][$index] = (int) $item->payload_clicked;
                    $formattedData['employee_compromised'][$index] = (int) $item->emp_compromised;
                    $formattedData['email_reported'][$index] = (int) $item->email_reported;
                }
            }

            return response()->json([
                'success' => true,
                'message' => __('Call campaign chart data fetched successfully.'),
                'data' => $formattedData
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->validator->errors()->first()
            ], 422);
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


    public function fetchCampaignReport(Request $request)
    {
        try {
            // Fetch campaignId from route parameter
            $campId = $request->route('campaignId');

            // Check if campaignId exists
            if (!$campId) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required.')
                ], 400);
            }

            // Ensure the user is authenticated
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => __('Unauthorized user.')
                ], 401);
            }

            $companyId = $user->company_id;

            // Fetch campaign report
            $reportRow = CampaignReport::where('campaign_id', $campId)
                ->where('company_id', $companyId)
                ->first();

            // Fetch user group ID
            $userGroup = Campaign::where('campaign_id', $campId)
                ->where('company_id', $companyId)
                ->first();

            if ($reportRow && $userGroup) {
                // Count the number of users in the group
                $no_of_users = Users::where('group_id', $userGroup->users_group)->count();

                // Prepare the response
                $response = [
                    'campaign_name' => $reportRow->campaign_name,
                    'campaign_type' => $reportRow->campaign_type,
                    'emails_delivered' => $reportRow->emails_delivered,
                    'emails_viewed' => $reportRow->emails_viewed,
                    'payloads_clicked' => $reportRow->payloads_clicked,
                    'emp_compromised' => $reportRow->emp_compromised,
                    'email_reported' => $reportRow->email_reported,
                    'status' => $reportRow->status,
                    'no_of_users' => $no_of_users,
                ];

                return response()->json([
                    'success' => true,
                    'message' => __('Campaign report fetched successfully.'),
                    'data' => $response
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign report or user group not found.')
                ], 404);
            }
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


    public function whatsappfetchCampaignReport(Request $request)
    {
        try {
            $campaignId = $request->route('campaignId');

            // Check if campaignId exists
            if (!$campaignId) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required.')
                ], 400);
            }
            // $campaignId = $request->campaignId;
            $company_id = Auth::user()->company_id;

            $camp_detail = WhatsappCampaign::with('trainingData')
                ->where('company_id', $company_id)
                ->where('camp_id', $campaignId)
                ->first();

            if ($camp_detail) {
                return response()->json([
                    'success' => true,
                    'message' => __('Campaign details fetched successfully.'),
                    'data' => $camp_detail
                ], 200); // OK
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign not found.'),
                    'data' => null
                ], 404); // Not Found
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->validator->errors()->first()
            ], 422);
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

    public function tprmfetchCampaignReport(Request $request)
    {
        try {
            $campId = $request->route('campaignId');

            // Check if campaignId exists
            if (!$campId) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required.')
                ], 400);
            }

            $companyId = Auth::user()->company_id;

            // Step 2: Fetch report and user group
            $reportRow = TprmCampaignReport::where('campaign_id', $campId)
                ->where('company_id', $companyId)
                ->first();

            $userGroup = TprmCampaign::where('campaign_id', $campId)
                ->where('company_id', $companyId)
                ->first();

            // Step 3: Check if both exist
            if ($reportRow && $userGroup) {
                // Step 4: Count users
                $no_of_users = Users::where('group_id', $userGroup->users_group)->count();

                // Step 5: Prepare response
                $response = [
                    'campaign_name' => $reportRow->campaign_name,
                    'campaign_type' => $reportRow->campaign_type,
                    'emails_delivered' => $reportRow->emails_delivered,
                    'emails_viewed' => $reportRow->emails_viewed,
                    'payloads_clicked' => $reportRow->payloads_clicked,
                    'emp_compromised' => $reportRow->emp_compromised,
                    'email_reported' => $reportRow->email_reported,
                    'status' => $reportRow->status,
                    'no_of_users' => $no_of_users,
                ];

                // Step 6: Store details in session
                $Arraydetails = [
                    'emails_delivered' => $reportRow->emails_delivered ?? 10,
                    'emails_viewed' => $reportRow->emails_viewed ?? 10,
                    'email_reported' => $reportRow->email_reported ?? 10,
                    'emp_compromised' => $reportRow->emp_compromised ?? 10,
                ];

                Session::put('campaign_details', $Arraydetails);

                // Step 7: Return success response
                return response()->json([
                    'success' => true,
                    'message' => __('TPRM campaign report fetched successfully.'),
                    'data' => $response
                ], 200);
            } else {
                // Not found
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign report or user group not found.'),
                    'data' => null
                ], 404);
            }
        } catch (\Illuminate\Validation\ValidationException $ve) {
            // Validation failed
            return response()->json([
                'success' => false,
                'message' => __('Validation failed.'),
                'errors' => $ve->errors()
            ], 422);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->validator->errors()->first()
            ], 422);
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


    public function aicallingfetchCampaignReport(Request $request)
    {
        try {
            // Validate request
            $campId = $request->route('campaignId');

            // Check if campaignId exists
            if (!$campId) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required.')
                ], 400);
            }

            $companyId = Auth::user()->company_id;

            // Fetch campaign report
            $reportRow = AiCallCampLive::where('campaign_id', $campId)
                ->where('company_id', $companyId)
                ->first();

            // Fetch user group ID
            $userGroup = AiCallCampaign::where('campaign_id', $campId)
                ->where('company_id', $companyId)
                ->first();

            if ($reportRow && $userGroup) {
                // Count users
                $no_of_users = Users::where('group_id', $userGroup->users_group)->count();

                // Prepare response
                $response = [
                    'campaign_name' => $userGroup->campaign_name,
                    'campaign_type' => 'Phishing & Training',
                    'created_at' => $userGroup->created_at,
                    'ai_agent' => $userGroup->ai_agent,
                    'ai_agent_name' => $userGroup->ai_agent_name,
                    'phone_no' => $userGroup->phone_no,
                    'status' => $userGroup->status,
                    'no_of_users' => $no_of_users,
                ];

                return response()->json([
                    'success' => true,
                    'message' => __('AI Calling campaign report fetched successfully.'),
                    'data' => $response
                ], 200); // OK
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign report or user group not found.'),
                    'data' => null
                ], 404); // Not Found
            }
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


    public function tfetchCampaignReport(Request $request)
    {
        try {
            $campId = $request->route('campaignId');

            // Check if campaignId exists
            if (!$campId) {
                return response()->json([
                    'status' => false,
                    'message' => __('Campaign ID is required.')
                ], 400);
            }

            $companyId = Auth::user()->company_id;

            // Fetch campaign report
            $reportRow = TprmCampaignReport::where('campaign_id', $campId)->where('company_id', $companyId)->first();

            // Fetch user group ID
            $userGroup = TprmCampaign::where('campaign_id', $campId)->where('company_id', $companyId)->first();
            $phishingMaterial = PhishingEmail::find($userGroup->phishing_material);

            if ($reportRow && $userGroup) {
                // Count the number of users in the group
                $no_of_users = TprmUsers::where('group_id', $userGroup->users_group)->count();

                // Prepare the response
                $response = [
                    'campaign_name' => $reportRow->campaign_name,
                    'campaign_type' => $reportRow->campaign_type,
                    'emails_delivered' => $reportRow->emails_delivered,
                    'emails_viewed' => $reportRow->emails_viewed,
                    'payloads_clicked' => $reportRow->payloads_clicked,
                    'emp_compromised' => $reportRow->emp_compromised,
                    'email_reported' => $reportRow->email_reported,
                    'phishing_material' => $phishingMaterial ?? null,
                    'status' => $reportRow->status,
                    'no_of_users' => $no_of_users,
                ];

                return response()->json([
                    'success' => true,
                    'message' => __('TPRM campaign report fetched successfully.'),
                    'data' => $response
                ], 200);
            } else {
                return response()->json(['success' => false, [], 'message' => __('Campaign report or user group not found')], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function fetchCampReportByUsers(Request $request)
    {
        try {
            // Step 1: Validate input
            $campId = $request->route('campaignId');

            // Check if campaignId exists
            if (!$campId) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required.')
                ], 400);
            }

            $campId = $request->input('campaignId');
            $companyId = Auth::user()->company_id;

            // Step 2: Fetch users based on campaign and company
            $allUsers = CampaignLive::where('campaign_id', $campId)
                ->where('company_id', $companyId)
                ->get();

            // Step 3: Check if no records found
            if ($allUsers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => __('No records found'),
                    'data' => []
                ], 404);
            }

            // Step 4: Prepare the HTML response for users


            // Step 5: Return success response with HTML data
            return response()->json([
                'success' => true,
                'message' => __('User report fetched successfully.'),
                'data' =>  $allUsers,
            ], 200);
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



    public function tprmfetchCampReportByUsers(Request $request)
    {
        try {
            $campId = $request->route('campaignId');

            // Check if campaignId exists
            if (!$campId) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required.')
                ], 400);
            }

            $campId = $request->input('campaignId');
            $companyId = Auth::user()->company_id;

            $allUsers = TprmCampaignLive::where('campaign_id', $campId)
                ->where('company_id', $companyId)
                ->get();

            if ($allUsers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => __('No records found'),
                    'data' => []
                ], 404);
            }

            $data = [];

            foreach ($allUsers as $userReport) {
                $data[] = [
                    'name' => $userReport->user_name,
                    'email' => $userReport->user_email,
                    'sent_status' => $userReport->sent == '1' ? 'Success' : 'Pending',
                    'viewed' => $userReport->mail_open == '1' ? 'Yes' : 'No',
                    'payload_clicked' => $userReport->payload_clicked == '1' ? 'Yes' : 'No',
                    'email_compromised' => $userReport->emp_compromised == '1' ? 'Yes' : 'No',
                    'email_reported' => $userReport->email_reported == '1' ? 'Yes' : 'No',
                ];
            }

            return response()->json([
                'success' => true,
                'message' => __('Campaign user report fetched successfully'),
                'data' => $data
            ], 200);
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


    public function whatsappfetchCampReportByUsers(Request $request)
    {
        try {
            $campId = $request->route('campaignId');

            // Check if campaignId exists
            if (!$campId) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required.')
                ], 400);
            }


            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'

                ], 401);
            }

            $companyId = $user->company_id;

            $allUsers = WhatsAppCampaignUser::where('camp_id', $campId)->where('company_id', $companyId)->get();

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp campaign report fetched successfully',
                'data' =>  $allUsers
            ], 200);
            // return response()->json(['html' => $responseHtml]);
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

    public function aicallingfetchCampReportByUsers(Request $request)
    {
        try {
            $campId = $request->route('campaignId');
            // Check if campaignId exists
            if (!$campId) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required.')
                ], 400);
            }

            // $campId = $request->input('campaignId');
            $companyId = Auth::user()->company_id;

            $allUsers = AiCallCampLive::where('campaign_id', $campId)
                ->where('company_id', $companyId)
                ->get();

            if ($allUsers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No records found',
                    'data' => []
                ], 404);
            }

            $data = [];

            foreach ($allUsers as $userReport) {
                $data[] = [
                    'employee_name' => $userReport->employee_name,
                    'mobile' => $userReport->to_mobile,
                    'call_time' => $userReport->created_at,
                    'email' => $userReport->employee_email,
                    'status' => $userReport->status == 'completed' ? 'Completed' : ucfirst($userReport->status),
                    'training_assigned' => $userReport->training_assigned == '1' ? 'Yes' : 'No',
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'AI calling campaign report fetched successfully',
                'data' => $data
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function tfetchCampReportByUsers(Request $request)
    {
        try {
            $campId = $request->route('campaignId');
            if (!$campId) {
                return response()->json([
                    'status' => false,
                    'message' => __('Campaign ID is required')
                ], 422);
            }
            $companyId = Auth::user()->company_id;
            $allUsers = TprmCampaignLive::with('campaignActivity')->where('campaign_id', $campId)->where('company_id', $companyId)->get();

            if ($allUsers->isEmpty()) {

                return response()->json([
                    'success' => false,
                    'message' => __('No records found'),
                ], 422);
            }


            return response()->json([
                'success' => true,
                'data' => $allUsers,
                'message' => __('Campaign user report fetched successfully')
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => __('Error: ') . $e->validator->errors()->first()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function fetchCampTrainingDetails(Request $request)
    {
        try {

            $campId = $request->route('campaignId');

            // Check if campaignId exists
            if (!$campId) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required.')
                ], 400);
            }

            $companyId = Auth::user()->company_id;

            // Fetch campaign report
            $reportRow = CampaignReport::where('campaign_id', $campId)
                ->where('company_id', $companyId)
                ->first();

            // Fetch user group ID
            $userGroup = Campaign::where('campaign_id', $campId)
                ->where('company_id', $companyId)
                ->first();

            if ($reportRow && $userGroup) {
                // Count users
                $no_of_users = Users::where('group_id', $userGroup->users_group)->count();

                // Prepare status flags
                $isAssigned = (int) $reportRow->training_assigned > 0 ? true : false;
                $isCompleted = (int) $reportRow->training_completed > 0 ? true : false;

                // Campaign status
                $status = $reportRow->status ?? 'unknown';

                // Prepare JSON response
                return response()->json([
                    'success' => true,
                    'message' => __('Training details fetched successfully'),
                    'data' => [
                        'campaign_name' => $reportRow->campaign_name,
                        'campaign_status' => ucfirst($status),
                        'total_users' => $no_of_users,
                        'training_assigned_count' => (int) $reportRow->training_assigned,
                        'training_completed_count' => (int) $reportRow->training_completed,
                        'training_assigned' => $isAssigned ? 'Yes' : 'No',
                        'training_completed' => $isCompleted ? 'Yes' : 'No',
                    ]
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No records found',
                    'data' => []
                ], 404);
            }
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


    public function whatsappfetchCampTrainingDetails(Request $request)
    {
        try {
            $campId = $request->route('campaignId');

            // Check if campaignId exists
            if (!$campId) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required.')
                ], 400);
            }
            $companyId = Auth::user()->company_id;

            // Fetch campaign report data
            $reportRow = WhatsAppCampaignUser::where('camp_id', $campId)
                ->where('company_id', $companyId)
                ->first();

            // Fetch user group data
            $userGroup = WhatsappCampaign::where('camp_id', $campId)
                ->where('company_id', $companyId)
                ->first();

            if ($reportRow && $userGroup) {
                // Count number of users in the group
                $no_of_users = WhatsappCampaign::where('user_group', $userGroup->users_group)->count();

                $isAssigned = (int) $reportRow->training_assigned > 0;
                $isCompleted = (int) $reportRow->training_completed > 0;

                $status = match ($reportRow->status) {
                    'completed' => 'Completed',
                    'pending' => 'Pending',
                    default => 'Running',
                };

                return response()->json([
                    'success' => true,
                    'message' => __('WhatsApp campaign training details fetched successfully'),
                    'data' => [
                        'campaign_name' => $reportRow->camp_name,
                        'campaign_status' => $status,
                        'total_users' => $no_of_users,
                        'training_assigned_count' => (int) $reportRow->training_assigned,
                        'training_completed_count' => (int) $reportRow->training_completed,
                        'training_assigned' => $isAssigned ? 'Yes' : 'No',
                        'training_completed' => $isCompleted ? 'Yes' : 'No',
                    ]
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No records found',
                    'data' => []
                ], 404);
            }
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


    public function aicallingfetchCampTrainingDetails(Request $request)
    {
        try {
            $campId = $request->route('campaignId');

            // Check if campaignId exists
            if (!$campId) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required.')
                ], 400);
            }
            $companyId = Auth::user()->company_id;

            // Fetch campaign report
            $reportRow = AiCallCampLive::where('campaign_id', $campId)
                ->where('company_id', $companyId)
                ->first();

            // Fetch user group
            $userGroup = AiCallCampaign::where('campaign_id', $campId)
                ->where('company_id', $companyId)
                ->first();

            if ($reportRow && $userGroup) {
                $no_of_users = Users::where('group_id', $userGroup->users_group)->count();

                $isAssigned = (int) $reportRow->training_assigned > 0 ? true : false;
                $isCompleted = (int) $reportRow->training_completed > 0 ? true : false;

                $status = match ($reportRow->status) {
                    'completed' => 'Completed',
                    'pending' => 'Pending',
                    default => 'Running',
                };

                return response()->json([
                    'success' => true,
                    'message' => __('AI Campaign training details fetched successfully'),
                    'data' => [
                        'campaign_name' => $reportRow->campaign_name,
                        'campaign_status' => $status,
                        'total_users' => $no_of_users,
                        'training_assigned_count' => (int) $reportRow->training_assigned,
                        'training_completed_count' => (int) $reportRow->training_completed,
                        'training_assigned' => $isAssigned ? 'Yes' : 'No',
                        'training_completed' => $isCompleted ? 'Yes' : 'No',
                    ]
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('No records found'),
                    'data' => []
                ], 404);
            }
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


    public function fetchCampTrainingDetailsIndividual(Request $request)
    {
        try {
            $campId = $request->route('campaignId');

            // Check if campaignId exists
            if (!$campId) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required.')
                ], 400);
            }
            $companyId = Auth::user()->company_id;

            // Fetch assigned training users
            $assignedUsers = TrainingAssignedUser::where('campaign_id', $campId)
                ->where('company_id', $companyId)
                ->get();

            if ($assignedUsers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => __('No records found'),
                    'data' => []
                ], 404);
            }

            $data = [];

            foreach ($assignedUsers as $assignedUser) {
                $trainingDetail = TrainingModule::find($assignedUser->training);
                if (!$trainingDetail) {
                    continue;
                }

                $today = new \DateTime(date('Y-m-d'));
                $dueDate = new \DateTime($assignedUser->training_due_date);

                if ($assignedUser->completed == 1) {
                    $statusText = 'Training Completed';
                    $statusClass = 'success';
                } else {
                    if ($dueDate > $today) {
                        $statusText = 'In training period';
                        $statusClass = 'success';
                    } else {
                        $days_difference = $today->diff($dueDate)->days;
                        $statusText = 'Overdue - ' . $days_difference . ' Days';
                        $statusClass = 'danger';
                    }
                }

                $data[] = [
                    'email' => $assignedUser->user_email,
                    'training_name' => $trainingDetail->name,
                    'assigned_date' => $assignedUser->assigned_date,
                    'personal_best' => $assignedUser->personal_best . '%',
                    'passing_score' => $trainingDetail->passing_score . '%',
                    'status_text' => $statusText,
                    'status_class' => $statusClass,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => __('Training assignment details fetched successfully'),
                'data' => $data
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


    public function whatsappfetchCampTrainingDetailsIndividual(Request $request)
    {
        try {
            $campId = $request->route('campaignId');

            // Check if campaignId exists
            if (!$campId) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required.')
                ], 400);
            }
            $companyId = Auth::user()->company_id;

            $assignedUsers = TrainingAssignedUser::where('campaign_id', $campId)
                ->where('company_id', $companyId)
                ->get();

            if ($assignedUsers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => __('No records found'),
                    'data' => []
                ], 404);
            }

            $data = [];
            $today = new \DateTime(date('Y-m-d'));

            foreach ($assignedUsers as $assignedUser) {
                $trainingDetail = TrainingModule::find($assignedUser->training);
                if (!$trainingDetail) {
                    continue;
                }

                $dueDate = new \DateTime($assignedUser->training_due_date);

                if ($dueDate > $today) {
                    $statusText = 'In training period';
                    $statusClass = 'success';
                } else {
                    $days_difference = $today->diff($dueDate)->days;
                    $statusText = 'Overdue - ' . $days_difference . ' Days';
                    $statusClass = 'danger';
                }

                $data[] = [
                    'email' => $assignedUser->user_email,
                    'training_name' => $trainingDetail->name,
                    'assigned_date' => $assignedUser->assigned_date,
                    'personal_best' => $assignedUser->personal_best . '%',
                    'passing_score' => $trainingDetail->passing_score . '%',
                    'status_text' => $statusText,
                    'status_class' => $statusClass,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => __('WhatsApp training assignment details fetched successfully'),
                'data' => $data
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


    public function aicallingfetchCampTrainingDetailsIndividual(Request $request)
    {
        try {
            $campId = $request->route('campaignId');

            // Check if campaignId exists
            if (!$campId) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign ID is required.')
                ], 400);
            }

            $companyId = Auth::user()->company_id;

            $assignedUsers = TrainingAssignedUser::where('campaign_id', $campId)
                ->where('company_id', $companyId)
                ->get();

            if ($assignedUsers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => __('No records found'),
                    'data' => []
                ], 404);
            }

            $data = [];
            $today = new \DateTime(date('Y-m-d'));

            foreach ($assignedUsers as $assignedUser) {
                $trainingDetail = TrainingModule::find($assignedUser->training);
                if (!$trainingDetail) {
                    continue;
                }

                $dueDate = new \DateTime($assignedUser->training_due_date);

                if ($dueDate > $today) {
                    $statusText = 'In training period';
                    $statusClass = 'success';
                } else {
                    $days_difference = $today->diff($dueDate)->days;
                    $statusText = 'Overdue - ' . $days_difference . ' Days';
                    $statusClass = 'danger';
                }

                $data[] = [
                    'email' => $assignedUser->user_email,
                    'training_name' => $trainingDetail->name,
                    'assigned_date' => $assignedUser->assigned_date,
                    'personal_best' => $assignedUser->personal_best . '%',
                    'passing_score' => $trainingDetail->passing_score . '%',
                    'status_text' => $statusText,
                    'status_class' => $statusClass,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => __('AI training assignment details fetched successfully'),
                'data' => $data
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

    public function fetchAwarenessEduReport()
    {
        try {
            $companyId = Auth::user()->company_id;
            $totalAssignedUsers = TrainingAssignedUser::where('company_id', $companyId)->count();
            $notStartedTraining = TrainingAssignedUser::where('company_id', $companyId)->where('training_started', 0)->count();

            $notStartedTrainingRate = $totalAssignedUsers > 0 ? round($notStartedTraining / $totalAssignedUsers * 100) : 0;

            $progressTraining = TrainingAssignedUser::where('company_id', $companyId)
                ->where('completed', 0)
                ->count();

            $progressTrainingRate = $totalAssignedUsers > 0 ? round($progressTraining / $totalAssignedUsers * 100) : 0;

            $completedTraining = TrainingAssignedUser::where('company_id', $companyId)
                ->where('completed', 1)
                ->count();

            $completedTrainingRate = $totalAssignedUsers > 0 ? round($completedTraining / $totalAssignedUsers * 100) : 0;

            $usersWhoScored = TrainingAssignedUser::where('company_id', $companyId)
                ->where('personal_best', '>=', 10)
                ->count();

            $educatedUserRate = $usersWhoScored / $totalAssignedUsers * 100;

            $companyLicense = CompanyLicense::where('company_id', $companyId)->first();
            $totalEmployees = $companyLicense->used_employees + $companyLicense->used_tprm_employees + $companyLicense->used_blue_collar_employees;

            $rolesResponsilbilityPercent = round($completedTraining  / $totalEmployees * 100);

            $certifiedUsersRate = TrainingAssignedUser::where('company_id', $companyId)
                ->where('certificate_id', '!=', null)->count() / $totalAssignedUsers * 100;

            $emailCampData = Campaign::where('company_id', $companyId)
                ->pluck('days_until_due');


            $quishCampData = QuishingCamp::where('company_id', $companyId)
                ->pluck('days_until_due');

            $waCampData = WaCampaign::where('company_id', $companyId)
                ->pluck('days_until_due');

            $merged = $emailCampData->merge($quishCampData)->merge($waCampData);
            $avgEducationDuration = round($merged->avg(), 2);

            $avgOverallDuration = $avgEducationDuration;

            $trainingAssignReminderDays = (int) CompanySettings::where('company_id', $companyId)
                ->value('training_assign_remind_freq_days');

            $onboardingTrainingDetails = [];

            $onboardingTrainings = TrainingAssignedUser::where('company_id', $companyId)
                ->take(5)
                ->get();
            foreach ($onboardingTrainings as $onboardingTraining) {
                $groupName = null;

                $userGroups = UsersGroup::where('company_id', $companyId)->get();

                foreach ($userGroups as $group) {
                    $users = json_decode($group->users, true);
                    if (!$users) {
                        continue;
                    }
                    foreach ($users as $user) {
                        if ($onboardingTraining->user_id == $user) {
                            $groupName = $group->group_name;
                            break 2; // Break out of both loops if a match is found
                        }
                    }
                }

                $onboardingTrainingDetails[] = [
                    'user_name' => $onboardingTraining->user_name,
                    'user_email' => $onboardingTraining->user_email,
                    'divison' => $groupName ?? null,
                    'assigned_date' => $onboardingTraining->assigned_date,
                    'completed_date' => $onboardingTraining->training_due_date,
                    'status' => $onboardingTraining->completed == 1 ? 'Complete' : 'In Progress',
                    'outstanding_training_count' => TrainingAssignedUser::where('user_email', $onboardingTraining->user_email)
                        ->where('personal_best', '>=', 70)->count(),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => __('Awareness and Education report fetched successfully'),
                'data' => [
                    'training_statistics' => [
                        'total_assigned_users' => $totalAssignedUsers,
                        'not_started_training' => $notStartedTraining,
                        'not_started_training_rate' => $notStartedTrainingRate,
                        'progress_training' => $progressTraining,
                        'progress_training_rate' => $progressTrainingRate,
                        'completed_training' => $completedTraining,
                        'completed_training_rate' => $completedTrainingRate
                    ],
                    'general_statistics' => [
                        'educated_user_percent' => round($educatedUserRate, 2),
                        'roles_responsibility_percent' => $rolesResponsilbilityPercent,
                        'certified_users_percent' => round($certifiedUsersRate)
                    ],
                    'education_duration_statistics' => [
                        'avg_education_duration' => round($avgEducationDuration),
                        'avg_overall_duration' => round($avgOverallDuration),
                        'training_assign_reminder_days' => $trainingAssignReminderDays
                    ],
                    'onboardingTrainingDetails' => $onboardingTrainingDetails
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function fetchAwarenessEduReporting()
    {
        try {
            $companyId = Auth::user()->company_id;
            $totalAssignedUsers = TrainingAssignedUser::where('company_id', $companyId)->count();
            $notStartedTraining = TrainingAssignedUser::where('company_id', $companyId)->where('training_started', 0)->count();

            $notStartedTrainingRate = $totalAssignedUsers > 0 ? round($notStartedTraining / $totalAssignedUsers * 100) : 0;

            $progressTraining = TrainingAssignedUser::where('company_id', $companyId)
                ->where('completed', 0)
                ->count();

            $progressTrainingRate = $totalAssignedUsers > 0 ? round($progressTraining / $totalAssignedUsers * 100) : 0;

            $completedTraining = TrainingAssignedUser::where('company_id', $companyId)
                ->where('completed', 1)
                ->count();

            $completedTrainingRate = $totalAssignedUsers > 0 ? round($completedTraining / $totalAssignedUsers * 100) : 0;

            $usersWhoScored = TrainingAssignedUser::where('company_id', $companyId)
                ->where('personal_best', '>=', 10)
                ->count();

            $educatedUserRate = $usersWhoScored / $totalAssignedUsers * 100;

            $companyLicense = CompanyLicense::where('company_id', $companyId)->first();
            $totalEmployees = $companyLicense->used_employees + $companyLicense->used_tprm_employees + $companyLicense->used_blue_collar_employees;

            $rolesResponsilbilityPercent = round($completedTraining  / $totalEmployees * 100);

            $certifiedUsersRate = TrainingAssignedUser::where('company_id', $companyId)
                ->where('certificate_id', '!=', null)->count() / $totalAssignedUsers * 100;

            $emailCampData = Campaign::where('company_id', $companyId)
                ->pluck('days_until_due');


            $quishCampData = QuishingCamp::where('company_id', $companyId)
                ->pluck('days_until_due');

            $waCampData = WaCampaign::where('company_id', $companyId)
                ->pluck('days_until_due');

            $merged = $emailCampData->merge($quishCampData)->merge($waCampData);
            $avgEducationDuration = round($merged->avg(), 2);

            $avgOverallDuration = $avgEducationDuration;

            $trainingAssignReminderFreqDays = (int) CompanySettings::where('company_id', $companyId)
                ->value('training_assign_remind_freq_days');

            $users = Users::where('company_id', $companyId)
                ->whereIn('user_email', function ($query) use ($companyId) {
                    $query->select('user_email')
                        ->from('training_assigned_users')
                        ->where('company_id', $companyId);
                })
                ->get();


            $onboardingTrainingDetails = [];

            foreach ($users as $user) {
                // Get group name
                $group = UsersGroup::whereJsonContains('users', $user->id)->first();
                $groupName = $group ? $group->group_name : null;

                // Get all trainings for this user
                $trainings = TrainingAssignedUser::where('company_id', $companyId)
                    ->where('user_email', $user->user_email)
                    ->get();

                $totalAssignedTrainings = $trainings->count();
                $totalCompletedTrainings = $trainings->where('completed', 1)->count();
                $totalCertificates = $trainings->whereNotNull('certificate_id')->count();
                $outstandingTrainingCount = $trainings->where('personal_best', '>=', 70)->count();

                $assignedTrainings = [];
                foreach ($trainings as $training) {
                    $assignedTrainings[] = [
                        'training_name' => $training->trainingData->name,
                        'training_type' => $training->training_type,
                        'assigned_date' => $training->assigned_date,
                         'training_started' => $training->training_started == 1 ? 'Yes' : 'No',
                         'passing_score' => $training->trainingData->passing_score,
                         'training_due_date' => $training->training_due_date,
                         'json_quiz' => $training->trainingData->json_quiz,
                         'is_completed' => $training->completed == 1 ? 'Yes' : 'No',
                        'completion_date' => $training->completion_date,
                        'personal_best' => $training->personal_best,
                        'certificate_id' => $training->certificate_id,
                        'last_reminder_date' => $training->last_reminder_date,
                        'status' => $training->completed == 1 ? 'Completed' : 'In Progress',
                    ];
                }

                $onboardingTrainingDetails[] = [
                    'user_name' => $user->user_name,
                    'user_email' => $user->user_email,
                    'division' => $groupName,
                    'total_assigned_trainings' => $totalAssignedTrainings,
                    'assigned_trainings' => $assignedTrainings,
                    'total_completed_trainings' => $totalCompletedTrainings,
                    'total_certificates' => $totalCertificates,
                    'count_of_outstanding_training' => $outstandingTrainingCount,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => __('Awareness and Education report fetched successfully'),
                'data' => [
                    'training_statistics' => [
                        'total_assigned_users' => $totalAssignedUsers,
                        'not_started_training' => $notStartedTraining,
                        'not_started_training_rate' => $notStartedTrainingRate,
                        'progress_training' => $progressTraining,
                        'progress_training_rate' => $progressTrainingRate,
                        'completed_training' => $completedTraining,
                        'completed_training_rate' => $completedTrainingRate
                    ],
                    'general_statistics' => [
                        'educated_user_percent' => round($educatedUserRate, 2),
                        'roles_responsibility_percent' => $rolesResponsilbilityPercent,
                        'certified_users_percent' => round($certifiedUsersRate)
                    ],
                    'education_duration_statistics' => [
                        'avg_education_duration' => round($avgEducationDuration),
                        'avg_overall_duration' => round($avgOverallDuration),
                        'training_assign_reminder_freq_days' => $trainingAssignReminderFreqDays
                    ],
                    'onboardingTrainingDetails' => $onboardingTrainingDetails
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function fetchDivisionUsersReport()
    {
        try {
            $companyId = Auth::user()->company_id;
            $userGroups = UsersGroup::where('company_id', $companyId)->get();

            $scoreRanges = [
                'poor' => [0, 20],
                'fair' => [21, 40],
                'good' => [41, 60],
                'verygood' => [61, 80],
                'excellent' => [81, 100],
            ];

            $divisionUserDetails = [];

            $totalUsers = \App\Models\Users::where('company_id', $companyId)->count();
            if ($totalUsers === 0) {
                return response()->json([
                    'success' => true,
                    'message' => __('No users found for this company'),
                    'data' => [
                        'total_division_users' => 0,
                        'division_user_details' => [],
                    ]
                ], 200);
            }

            foreach ($userGroups as $group) {
                $users = json_decode($group->users, true);
                if (!$users) {
                    continue;
                }

                foreach ($users as $userId) {
                    $user = Users::where('id', $userId)
                        ->where('company_id', $companyId)
                        ->first();

                    if (!$user) {
                        continue;
                    }

                    // Email simulation
                    $totalSimulations = \App\Models\CampaignLive::where('company_id', $companyId)
                        ->where('user_id', $user->id)
                        ->count();
                    $compromisedSimulations = \App\Models\CampaignLive::where('company_id', $companyId)
                        ->where('user_id', $user->id)
                        ->where('emp_compromised', 1)
                        ->count();

                    // Quishing simulation
                    $totalQuishing = \App\Models\QuishingLiveCamp::where('company_id', $companyId)
                        ->where('user_id', $user->id)
                        ->count();
                    $compromisedQuishing = \App\Models\QuishingLiveCamp::where('company_id', $companyId)
                        ->where('user_id', $user->id)
                        ->where('compromised', '1')
                        ->count();

                    // TPRM simulation
                    $totalTprm = \App\Models\TprmCampaignLive::where('company_id', $companyId)
                        ->where('user_id', $user->id)
                        ->count();
                    $compromisedTprm = \App\Models\TprmCampaignLive::where('company_id', $companyId)
                        ->where('user_id', $user->id)
                        ->where('emp_compromised', 1)
                        ->count();

                    // WhatsApp simulation
                    $totalWhatsapp = \App\Models\WaLiveCampaign::where('company_id', $companyId)
                        ->where('user_id', $user->id)
                        ->count();
                    $compromisedWhatsapp = \App\Models\WaLiveCampaign::where('company_id', $companyId)
                        ->where('user_id', $user->id)
                        ->where('compromised', 1)
                        ->count();

                    // Final counts
                    $totalAll = $totalSimulations + $totalQuishing + $totalTprm + $totalWhatsapp;
                    $compromisedAll = $compromisedSimulations + $compromisedQuishing + $compromisedTprm + $compromisedWhatsapp;

                    $riskScore = $totalAll > 0
                        ? 100 - round(($compromisedAll / $totalAll) * 100)
                        : 100; // If no simulations, assume excellent

                    // Determine risk level
                    $riskLevel = 'unknown';
                    foreach ($scoreRanges as $label => [$min, $max]) {
                        if ($riskScore >= $min && $riskScore <= $max) {
                            $riskLevel = $label;
                            break;
                        }
                    }

                    $divisionUserDetails[] = [
                        'user_name' => $user->user_name,
                        'user_email' => $user->user_email,
                        'division' => $group->group_name,
                        'user_job_title' => $user->user_job_title,
                        'whatsapp_no' => $user->whatsapp,
                        'risk_score' => $riskScore,
                        'risk_level' => $riskLevel,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => __('Division users report fetched successfully'),
                'data' => [
                    'total_division_users' => count($divisionUserDetails),
                    'division_user_details' => $divisionUserDetails
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function fetchDivisionUsersReporting()
    {
        try {
            $companyId = Auth::user()->company_id;
            $userGroups = UsersGroup::where('company_id', $companyId)->get();

            $scoreRanges = [
                'poor' => [0, 20],
                'fair' => [21, 40],
                'good' => [41, 60],
                'verygood' => [61, 80],
                'excellent' => [81, 100],
            ];

            $divisionUserDetails = [];

            $totalUsers = \App\Models\Users::where('company_id', $companyId)->count();
            if ($totalUsers === 0) {
                return response()->json([
                    'success' => true,
                    'message' => __('No users found for this company'),
                    'data' => [
                        'total_division_users' => 0,
                        'division_user_details' => [],
                    ]
                ], 200);
            }

            foreach ($userGroups as $group) {
                $users = json_decode($group->users, true);
                if (!$users) {
                    continue;
                }

                foreach ($users as $userId) {
                    $user = Users::where('id', $userId)
                        ->where('company_id', $companyId)
                        ->first();

                    if (!$user) {
                        continue;
                    }

                    // Email simulation
                    $totalSimulations = \App\Models\CampaignLive::where('company_id', $companyId)
                        ->where('user_id', $user->id)
                        ->count();
                    $compromisedSimulations = \App\Models\CampaignLive::where('company_id', $companyId)
                        ->where('user_id', $user->id)
                        ->where('emp_compromised', 1)
                        ->count();

                    // Quishing simulation
                    $totalQuishing = \App\Models\QuishingLiveCamp::where('company_id', $companyId)
                        ->where('user_id', $user->id)
                        ->count();
                    $compromisedQuishing = \App\Models\QuishingLiveCamp::where('company_id', $companyId)
                        ->where('user_id', $user->id)
                        ->where('compromised', '1')
                        ->count();

                    // TPRM simulation
                    $totalTprm = \App\Models\TprmCampaignLive::where('company_id', $companyId)
                        ->where('user_id', $user->id)
                        ->count();
                    $compromisedTprm = \App\Models\TprmCampaignLive::where('company_id', $companyId)
                        ->where('user_id', $user->id)
                        ->where('emp_compromised', 1)
                        ->count();

                    // WhatsApp simulation
                    $totalWhatsapp = \App\Models\WaLiveCampaign::where('company_id', $companyId)
                        ->where('user_id', $user->id)
                        ->count();
                    $compromisedWhatsapp = \App\Models\WaLiveCampaign::where('company_id', $companyId)
                        ->where('user_id', $user->id)
                        ->where('compromised', 1)
                        ->count();

                    // Final counts
                    $totalAll = $totalSimulations + $totalQuishing + $totalTprm + $totalWhatsapp;
                    $compromisedAll = $compromisedSimulations + $compromisedQuishing + $compromisedTprm + $compromisedWhatsapp;

                    $riskScore = $totalAll > 0
                        ? 100 - round(($compromisedAll / $totalAll) * 100)
                        : 100; // If no simulations, assume excellent

                    // Determine risk level
                    $riskLevel = 'unknown';
                    foreach ($scoreRanges as $label => [$min, $max]) {
                        if ($riskScore >= $min && $riskScore <= $max) {
                            $riskLevel = $label;
                            break;
                        }
                    }

                    $divisionUserDetails[] = [
                        'user_name' => $user->user_name,
                        'user_email' => $user->user_email,
                        'division' => $group->group_name,
                        'user_job_title' => $user->user_job_title,
                        'whatsapp_no' => $user->whatsapp,
                        'risk_score' => $riskScore,
                        'risk_level' => $riskLevel,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => __('Division users report fetched successfully'),
                'data' => [
                    'total_normal_divisions' => count($userGroups),
                    'total_blue_collar_divisions' => BlueCollarGroup::where('company_id', $companyId)->count(),
                    'total_normal_users' => count($divisionUserDetails),
                    'total_blue_collar_users' => BlueCollarEmployee::where('company_id', $companyId)->count(),
                    'division_user_details' => $divisionUserDetails
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }


    public function fetchUsersReport()
    {
        try {
            $companyId = Auth::user()->company_id;
            $allUsers = Users::where('company_id', $companyId)->get();
            $userDetails = [];
            $totalNormalUsers = count($allUsers);

            $riskScoreRanges = [
                'poor' => [0, 20],
                'fair' => [21, 40],
                'good' => [41, 60],
                'verygood' => [61, 80],
                'excellent' => [81, 100],
            ];


            foreach ($allUsers as $user) {

                // Email simulation
                $totalSimulations = CampaignLive::where('user_email', $user->user_email)
                    ->where('company_id', $companyId)
                    ->count();
                $compromisedSimulations = CampaignLive::where('user_email', $user->user_email)
                    ->where('company_id', $companyId)
                    ->where('emp_compromised', 1)
                    ->count();

                // Quishing simulation
                $totalQuishing = QuishingLiveCamp::where('user_email', $user->user_email)
                    ->where('company_id', $companyId)
                    ->count();
                $compromisedQuishing = QuishingLiveCamp::where('user_email', $user->user_email)
                    ->where('company_id', $companyId)
                    ->where('compromised', 1)
                    ->count();

                // TPRM simulation
                $totalTprm = TprmCampaignLive::where('user_email', $user->user_email)
                    ->where('company_id', $companyId)
                    ->count();
                $compromisedTprm = TprmCampaignLive::where('user_email', $user->user_email)
                    ->where('company_id', $companyId)
                    ->where('emp_compromised', 1)
                    ->count();

                // WhatsApp simulation
                $totalWhatsapp = WaLiveCampaign::where('company_id', $companyId)
                    ->where('user_id', $user->id)
                    ->count();
                $compromisedWhatsapp = WaLiveCampaign::where('company_id', $companyId)
                    ->where('user_id', $user->id)
                    ->where('compromised', 1)
                    ->count();

                $totalAll = $totalSimulations + $totalQuishing + $totalTprm + $totalWhatsapp;
                $compromisedAll = $compromisedSimulations + $compromisedQuishing + $compromisedTprm + $compromisedWhatsapp;

                $riskScore = $totalAll > 0
                    ? 100 - round(($compromisedAll / $totalAll) * 100)
                    : 100; // Assume excellent if no simulations

                // Determine risk level
                $riskLevel = 'unknown';
                foreach ($riskScoreRanges as $label => [$min, $max]) {
                    if ($riskScore >= $min && $riskScore <= $max) {
                        $riskLevel = $label;
                        break;
                    }
                }

                $userDetails[] = [
                    'user_name' => $user->user_name,
                    'user_email' => $user->user_email,
                    'whatsapp_no' => $user->whatsapp,
                    'user_type' => 'normal',
                    'division' => 'N/A',
                    'user_job_title' => $user->user_job_title,
                    'breach_scan_date' => $user->breach_scan_date ?? null,
                    'breach_scan_status' => $user->breach_scan_date ? 'Breached' : 'Not Breached',
                    'user_created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    'campaigns' => [
                        'email' => [
                            'totalCampaigns' => $totalSimulations,
                            'totalTrainings' => TrainingAssignedUser::where('user_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->count(),
                            'payload_clicked' => CampaignLive::where('user_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->where('payload_clicked', 1)
                                ->count(),
                            'compromised' => $compromisedSimulations,
                            'email_reported' => CampaignLive::where('user_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->where('email_reported', 1)
                                ->count(),
                        ],
                        'whatsapp' => [
                            'totalCampaigns' => 0,
                            'totalTrainings' => 0,
                            'payload_clicked' => 0,
                            'compromised' => 0,
                        ],
                        'ai_vishing' => [
                            'totalCampaigns' => AiCallCampLive::where('employee_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->count(),
                            'totalTrainings' => AiCallCampLive::where('employee_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->where('training_assigned', 1)
                                ->count(),
                            'call_send' => AiCallCampLive::where('employee_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->whereNotNull('call_send_response')
                                ->count(),
                            'call_reported' => AiCallCampLive::where('employee_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->whereNotNull('call_report')
                                ->count(),
                        ],
                        'quishing' => [
                            'totalCampaigns' => $totalQuishing,
                            'totalTrainings' => QuishingLiveCamp::where('user_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->where('training_assigned', 1)
                                ->count(),
                            'qr_scanned' => QuishingLiveCamp::where('user_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->where('qr_scanned', 1)
                                ->count(),
                            'compromised' => $compromisedQuishing,
                            'email_reported' => QuishingLiveCamp::where('user_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->where('email_reported', 1)
                                ->count(),
                        ],
                        'tprm' => [
                            'totalCampaigns' => $totalTprm,
                            'totalTrainings' => TprmCampaignLive::where('user_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->where('training_assigned', 1)
                                ->count(),
                            'payload_clicked' => TprmCampaignLive::where('user_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->where('payload_clicked', 1)
                                ->count(),
                            'compromised' => $compromisedTprm,
                        ],
                    ],
                    'risk_score' => $riskScore,
                    'risk_level' => $riskLevel,
                ];
            }

            $blueCollarUsers = BlueCollarEmployee::where('company_id', $companyId)->get();
            $totalBlueCollarUsers = count($blueCollarUsers);

            foreach ($blueCollarUsers as $user) {
                $userDetails[] = [
                    'user_name' => $user->user_name,
                    'user_email' => '',
                    'whatsapp_no' => $user->whatsapp,
                    'user_type' => 'blue-collar',
                    'division' => $user->blueCollarGroup->group_name,
                    'user_job_title' => $user->user_job_title,
                    'breach_scan_date' => $user->breach_scan_date ?? null,
                    'breach_scan_status' => $user->breach_scan_date ? 'Breached' : 'Not Breached',
                    'user_created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    'campaigns' => [
                        'email' => [
                            'totalCampaigns' => 0,
                            'totalTrainings' => 0,
                            'payload_clicked' => 0,
                            'compromised' => 0,
                            'email_reported' => 0,
                        ],
                        'whatsapp' => [
                            'totalCampaigns' => WaLiveCampaign::where('user_phone', $user->whatsapp)
                                ->where('company_id', $companyId)
                                ->count(),
                            'totalTrainings' => BlueCollarTrainingUser::where('user_whatsapp', $user->whatsapp)
                                ->where('company_id', $companyId)
                                ->count(),
                            'payload_clicked' => WaLiveCampaign::where('user_phone', $user->whatsapp)
                                ->where('company_id', $companyId)
                                ->where('payload_clicked', 1)
                                ->count(),
                            'compromised' => WaLiveCampaign::where('user_phone', $user->whatsapp)
                                ->where('company_id', $companyId)
                                ->where('compromised', 1)
                                ->count(),
                        ],
                        'ai_vishing' => [
                            'totalCampaigns' => 0,
                            'totalTrainings' => 0,
                            'call_send' => 0,
                            'call_reported' => 0,
                        ],
                        'quishing' => [
                            'totalCampaigns' => 0,
                            'totalTrainings' => 0,
                            'qr_scanned' => 0,
                            'compromised' => 0,
                            'email_reported' => 0,
                        ],
                        'tprm' => [
                            'totalCampaigns' => 0,
                            'totalTrainings' => 0,
                            'payload_clicked' => 0,
                            'compromised' => 0,
                        ],
                    ],
                    'risk_score' => $riskScore,
                    'risk_level' => $riskLevel,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => __('Users report fetched successfully'),
                'data' => [
                    'total_users' => $totalNormalUsers + $totalBlueCollarUsers,
                    'user_details' => $userDetails
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function fetchUsersReporting()
    {
        try {
            $companyId = Auth::user()->company_id;

            // Fetch all distinct users by email
            $allUsers = Users::where('company_id', $companyId)
                ->whereIn('id', function ($query) use ($companyId) {
                    $query->selectRaw('MIN(id)')
                        ->from('users')
                        ->where('company_id', $companyId)
                        ->groupBy('user_email');
                })
                ->get();

            // Fetch top 6 users for risk score calculation (latest users)
            $top6Users = Users::where('company_id', $companyId)
                ->whereIn('id', function ($query) use ($companyId) {
                    $query->selectRaw('MIN(id)')
                        ->from('users')
                        ->where('company_id', $companyId)
                        ->groupBy('user_email');
                })
                ->orderBy('created_at', 'desc')
                ->limit(6)
                ->get()
                ->keyBy('id'); // For quick lookup

            $riskScores = [];
            $riskScoreRanges = [
                'poor' => [0, 20],
                'fair' => [21, 40],
                'good' => [41, 60],
                'verygood' => [61, 80],
                'excellent' => [81, 100],
            ];

            $userDetails = [];

            foreach ($allUsers as $user) {
                // Group
                $group = UsersGroup::whereJsonContains('users', $user->id)->first();
                $groupName = $group ? $group->group_name : null;

                // Campaigns
                $emailCampaigns = CampaignLive::where('user_email', $user->user_email)
                    ->where('company_id', $companyId);

                $quishingCampaigns = QuishingLiveCamp::where('user_email', $user->user_email)
                    ->where('company_id', $companyId);

                $tprmCampaigns = TprmCampaignLive::where('user_email', $user->user_email)
                    ->where('company_id', $companyId);

                $whatsappCampaigns = WaLiveCampaign::where('user_id', $user->id)
                    ->where('company_id', $companyId);

                $totalSimulations = $emailCampaigns->count();
                $compromisedSimulations = (clone $emailCampaigns)->where('emp_compromised', 1)->count();

                $totalQuishing = $quishingCampaigns->count();
                $compromisedQuishing = (clone $quishingCampaigns)->where('compromised', 1)->count();

                $totalTprm = $tprmCampaigns->count();
                $compromisedTprm = (clone $tprmCampaigns)->where('emp_compromised', 1)->count();

                $totalWhatsapp = $whatsappCampaigns->count();
                $compromisedWhatsapp = (clone $whatsappCampaigns)->where('compromised', 1)->count();

                // Risk score calculation for top 6 users only
                $riskScore = null;
                $riskLevel = null;

                if ($top6Users->has($user->id)) {
                    $totalAll = $totalSimulations + $totalQuishing + $totalTprm + $totalWhatsapp;
                    $compromisedAll = $compromisedSimulations + $compromisedQuishing + $compromisedTprm + $compromisedWhatsapp;

                    $riskScore = $totalAll > 0 ? 100 - round(($compromisedAll / $totalAll) * 100) : 100;

                    // Determine risk level
                    foreach ($riskScoreRanges as $label => [$min, $max]) {
                        if ($riskScore >= $min && $riskScore <= $max) {
                            $riskLevel = $label;
                            break;
                        }
                    }

                    $riskScores[] = $riskScore;

                    $top6RiskUsers[] = [
                        'user_name' => $user->user_name,
                        'user_email' => $user->user_email,
                        'risk_score' => $riskScore,
                        'risk_level' => $riskLevel,
                    ];
                }

                $userDetails[] = [
                    'user_name' => $user->user_name,
                    'user_email' => $user->user_email,
                    'whatsapp_no' => $user->whatsapp,
                    'user_type' => 'normal',
                    'division' => $groupName,
                    'user_job_title' => $user->user_job_title,
                    'breach_scan_date' => $user->breach_scan_date ?? null,
                    'breach_scan_status' => $user->breach_scan_date ? 'Breached' : 'Not Breached',
                    'user_created_at' => $user->created_at->format('Y-m-d'),
                    'risk_score' => $riskScore,
                    'risk_level' => $riskLevel,
                    'campaigns' => [
                        'email' => [
                            'totalCampaigns' => $totalSimulations,
                            'totalTrainings' => TrainingAssignedUser::where('user_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->count(),
                            'payload_clicked' => CampaignLive::where('user_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->where('payload_clicked', 1)
                                ->count(),
                            'compromised' => $compromisedSimulations,
                            'email_reported' => CampaignLive::where('user_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->where('email_reported', 1)
                                ->count(),
                        ],
                        'whatsapp' => [
                            'totalCampaigns' => 0,
                            'totalTrainings' => 0,
                            'payload_clicked' => 0,
                            'compromised' => 0,
                        ],
                        'ai_vishing' => [
                            'totalCampaigns' => AiCallCampLive::where('employee_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->count(),
                            'totalTrainings' => AiCallCampLive::where('employee_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->where('training_assigned', 1)
                                ->count(),
                            'call_send' => AiCallCampLive::where('employee_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->whereNotNull('call_send_response')
                                ->count(),
                            'call_reported' => AiCallCampLive::where('employee_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->whereNotNull('call_report')
                                ->count(),
                        ],
                        'quishing' => [
                            'totalCampaigns' => $totalQuishing,
                            'totalTrainings' => QuishingLiveCamp::where('user_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->where('training_assigned', 1)
                                ->count(),
                            'qr_scanned' => QuishingLiveCamp::where('user_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->where('qr_scanned', 1)
                                ->count(),
                            'compromised' => $compromisedQuishing,
                            'email_reported' => QuishingLiveCamp::where('user_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->where('email_reported', 1)
                                ->count(),
                        ],
                        'tprm' => [
                            'totalCampaigns' => $totalTprm,
                            'totalTrainings' => TprmCampaignLive::where('user_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->where('training_assigned', 1)
                                ->count(),
                            'payload_clicked' => TprmCampaignLive::where('user_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->where('payload_clicked', 1)
                                ->count(),
                            'compromised' => $compromisedTprm,
                        ],
                    ],
                ];
            }

            // Blue Collar Users
            $blueCollarUsers = BlueCollarEmployee::where('company_id', $companyId)->get();
            foreach ($blueCollarUsers as $user) {
                $userDetails[] = [
                    'user_name' => $user->user_name,
                    'user_email' => 'N/A',
                    'whatsapp_no' => $user->whatsapp,
                    'user_type' => 'blue-collar',
                    'division' => $user->blueCollarGroup->group_name,
                    'user_job_title' => $user->user_job_title,
                    'breach_scan_date' => $user->breach_scan_date ?? null,
                    'breach_scan_status' => $user->breach_scan_date ? 'Breached' : 'Not Breached',
                    'user_created_at' => $user->created_at->format('Y-m-d'),
                    'campaigns' => [
                        'email' => [
                            'totalCampaigns' => 0,
                            'totalTrainings' => 0,
                            'payload_clicked' => 0,
                            'compromised' => 0,
                            'email_reported' => 0,
                        ],
                        'whatsapp' => [
                            'totalCampaigns' => WaLiveCampaign::where('user_phone', $user->whatsapp)
                                ->where('company_id', $companyId)
                                ->count(),
                            'totalTrainings' => BlueCollarTrainingUser::where('user_whatsapp', $user->whatsapp)
                                ->where('company_id', $companyId)
                                ->count(),
                            'payload_clicked' => WaLiveCampaign::where('user_phone', $user->whatsapp)
                                ->where('company_id', $companyId)
                                ->where('payload_clicked', 1)
                                ->count(),
                            'compromised' => WaLiveCampaign::where('user_phone', $user->whatsapp)
                                ->where('company_id', $companyId)
                                ->where('compromised', 1)
                                ->count(),
                        ],
                        'ai_vishing' => [
                            'totalCampaigns' => 0,
                            'totalTrainings' => 0,
                            'call_send' => 0,
                            'call_reported' => 0,
                        ],
                        'quishing' => [
                            'totalCampaigns' => 0,
                            'totalTrainings' => 0,
                            'qr_scanned' => 0,
                            'compromised' => 0,
                            'email_reported' => 0,
                        ],
                        'tprm' => [
                            'totalCampaigns' => 0,
                            'totalTrainings' => 0,
                            'payload_clicked' => 0,
                            'compromised' => 0,
                        ],
                    ],
                ];
            }

            return response()->json([
                'success' => true,
                'message' => __('Users report fetched successfully'),
                'data' => [
                    'total_users' => count($userDetails),
                    'user_details' => $userDetails,
                    'top_6_user_risk_scores' => $top6RiskUsers
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }


    public function fetchTrainingReport()
    {
        try {
            $companyId = Auth::user()->company_id;

            $trainingModules = TrainingModule::where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhere('company_id', 'default');
            })
                ->whereHas('trainingAssigned')
                ->get();
            $trainings = [];
            foreach ($trainingModules as $trainingModule) {
                $trainings[] = [
                    'name' => $trainingModule->name,
                    'total_assigned_trainings' => TrainingAssignedUser::where('training', $trainingModule->id)->count(),
                    'completed_trainings' => TrainingAssignedUser::where('training', $trainingModule->id)->where('completed', 1)->count(),
                    'in_progress_trainings' => TrainingAssignedUser::where('training', $trainingModule->id)->where('training_started', 1)->count(),
                    'total_no_of_simulations' => CampaignLive::where('training_module', $trainingModule->id)->count(),
                    'total_no_of_quish_camp' => QuishingLiveCamp::where('training_module', $trainingModule->id)->count(),
                    'total_no_of_ai_camp' => AiCallCampLive::where('training', $trainingModule->id)->count(),
                    'total_no_of_wa_camp' => WaLiveCampaign::where('training_module', $trainingModule->id)->count(),
                    'total_no_of_tprm' => TprmCampaignLive::where('training_module', $trainingModule->id)->count()

                ];
            }
            return response()->json([
                'seuccess' => true,
                'mssage' => __('Training report fetched successfully'),
                'data' => [
                    'trainings' => $trainings
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function fetchTrainingReporting()
    {
        try {
            $companyId = Auth::user()->company_id;

            $trainingModules = TrainingModule::where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhere('company_id', 'default');
            })
                ->whereHas('trainingAssigned')
                ->get();
            $trainings = [];
            foreach ($trainingModules as $trainingModule) {
                $trainings[] = [
                    'name' => $trainingModule->name,
                    'total_assigned_trainings' => TrainingAssignedUser::where('training', $trainingModule->id)->count(),
                    'completed_trainings' => TrainingAssignedUser::where('training', $trainingModule->id)->where('completed', 1)->count(),
                    'in_progress_trainings' => TrainingAssignedUser::where('training', $trainingModule->id)->where('training_started', 1)->count(),
                    'total_overdue_trainings' => TrainingAssignedUser::where('training', $trainingModule->id)
                        ->where('training_due_date', '<=', now())
                        ->count(),
                    'total_no_of_simulations' => CampaignLive::where('training_module', $trainingModule->id)->count(),
                    'total_no_of_quish_camp' => QuishingLiveCamp::where('training_module', $trainingModule->id)->count(),
                    'total_no_of_ai_camp' => AiCallCampLive::where('training', $trainingModule->id)->count(),
                    'total_no_of_wa_camp' => WaLiveCampaign::where('training_module', $trainingModule->id)->count(),
                    'total_no_of_tprm' => TprmCampaignLive::where('training_module', $trainingModule->id)->count(),
                    'assigned_date' => TrainingAssignedUser::where('training', $trainingModule->id)->value('assigned_date'),
                    'training_due_date' => TrainingAssignedUser::where('training', $trainingModule->id)->value('training_due_date'),
                    'training_started' => TrainingAssignedUser::where('training', $trainingModule->id)
                        ->where('training_started', 1) ? 'Yes' : 'No',
                    'personal_best' => TrainingAssignedUser::where('training', $trainingModule->id)
                        ->value('personal_best'),
                    'no_of_certificates' => TrainingAssignedUser::where('training', $trainingModule->id)
                        ->whereNotNull('certificate_id')
                        ->count(),

                ];
            }

            $latestTrainings = TrainingModule::where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhere('company_id', 'default');
            })
                ->whereHas('trainingAssigned')
                ->limit(6)
                ->get();

            foreach ($latestTrainings as $latestTraining) {
                $latestTrainingsAssigmentStatus[] = [
                    'training_name' => $latestTraining->name,
                    'total_assigned_trainings' => TrainingAssignedUser::where('training', $latestTraining->id)->count(),
                    'completed_trainings' => TrainingAssignedUser::where('training', $latestTraining->id)->where('completed', 1)->count(),
                    'in_progress_trainings' => TrainingAssignedUser::where('training', $latestTraining->id)->where('training_started', 1)->count(),
                    'total_overdue_trainings' => TrainingAssignedUser::where('training', $latestTraining->id)
                        ->where('training_due_date', '<=', now())
                        ->count(),
                ];

                $latestTrainingSimulationTypes[] = [
                    'total_no_of_simulations' => CampaignLive::where('training_module', $latestTraining->id)->count(),
                    'total_no_of_quish_camp' => QuishingLiveCamp::where('training_module', $latestTraining->id)->count(),
                    'total_no_of_ai_camp' => AiCallCampLive::where('training', $latestTraining->id)->count(),
                    'total_no_of_wa_camp' => WaLiveCampaign::where('training_module', $latestTraining->id)->count(),
                    'total_no_of_tprm' => TprmCampaignLive::where('training_module', $latestTraining->id)->count(),
                ];
            }


            return response()->json([
                'seuccess' => true,
                'mssage' => __('Training report fetched successfully'),
                'data' => [
                    'trainings' => $trainings,
                    'latest_trainings_assignment_status' => $latestTrainingsAssigmentStatus,
                    'latest_training_simulation_types' => $latestTrainingSimulationTypes
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function fetchGamesReport()
    {
        try {
            $companyId = Auth::user()->company_id;

            $trainingGames = TrainingGame::where('company_id', $companyId)->get();
            $games = [];
            foreach ($trainingGames as $trainingGame) {
                $games[] = [
                    'name' => $trainingGame->name,
                    'total_assigned_games' => TrainingAssignedUser::where('training_type', 'games')->where('training', $trainingGame->id)->count(),
                    'games_completed' => TrainingAssignedUser::where('training_type', 'games')->where('training', $trainingGame->id)->where('completed', 1)->count(),
                    'in_progress_games' => TrainingAssignedUser::where('training_type', 'games')->where('training', $trainingGame->id)->where('training_started', 1)->count(),
                    'total_no_of_simulations' => CampaignLive::where('training_type', 'games')->where('training_module', $trainingGame->id)->count(),
                    'total_no_of_quish_camp' => QuishingLiveCamp::where('training_type', 'games')->where('training_module', $trainingGame->id)->count(),
                    'total_no_of_ai_camp' => AiCallCampLive::where('training_type', 'games')->where('training', $trainingGame->id)->count(),
                    'total_no_of_wa_camp' => WaLiveCampaign::where('training_type', 'games')->where('training_module', $trainingGame->id)->count(),
                    'game_completion_time_in_seconds' => TrainingAssignedUser::where('training_type', 'games')->where('training', $trainingGame->id)->value('game_time') ?: 0,
                ];
            }
            return response()->json([
                'success' => true,
                'message' => __('Games report fetched successfully'),
                'data' => [
                    'games' => $games
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function fetchPoliciesReport()
    {
        try {
            $companyId = Auth::user()->company_id;

            $policies = Policy::where('company_id', $companyId)->get();

            if ($policies->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => __('No policies found for this company'),
                    'data' => []
                ], 404);
            }

            $policyDetails = [];
            foreach ($policies as $policy) {
                $policyDetails[] = [
                    'policy_name' => $policy->policy_name,
                    'policy_description' => $policy->policy_description,
                    'has_quiz' => $policy->has_quiz ? 'Yes' : 'No',
                    'created_at' => $policy->created_at->format('Y-m-d H:i:s'),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => __('Policies report fetched successfully'),
                'data' => [
                    'policies' => $policyDetails
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function fetchPoliciesReporting()
    {
        try {
            $companyId = Auth::user()->company_id;

            $policies = Policy::where('company_id', $companyId)->get();

            if ($policies->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => __('No policies found for this company'),
                    'data' => []
                ], 404);
            }

            $assignedPolicies = AssignedPolicy::where('company_id', $companyId)->get();
            $assignedPolicyDetails = [];
            foreach ($assignedPolicies as $assignedPolicy) {
                $assignedPolicyDetails[] = [
                    'policy_name' => Policy::find($assignedPolicy->policy)->policy_name,
                    'accepted' => $assignedPolicy->accepted == 1 ? 'Yes' : 'No',
                    'accepted_date' => $assignedPolicy->accepted_at
                        ? Carbon::parse($assignedPolicy->accepted_at)->format('Y-m-d')
                        : 'Not Accepted',
                    'user_email' => $assignedPolicy->user_email,
                    'user_name' => $assignedPolicy->user_name,
                    'json_quiz_response' => $assignedPolicy->json_quiz_response ? json_decode($assignedPolicy->json_quiz_response, true) : null,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => __('Policies report fetched successfully'),
                'data' => [
                    'assigned_policies' => $assignedPolicyDetails
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function fetchCourseSummaryReport()
    {
        try {
            $companyId = Auth::user()->company_id;

            $assignedCourses = TrainingAssignedUser::where('company_id', $companyId)->get();

            foreach ($assignedCourses as $course) {
                $trainingId = (int) $course->training;

                $training = TrainingModule::find($trainingId);
                $DueDateDetails = [
                    'count_of_training_due_date' => TrainingAssignedUser::where('training', $course->training)
                        ->where('training_started', 0)
                        ->where('training_due_date', '<', date('Y-m-d'))
                        ->where('company_id', $companyId)
                        ->count(),

                ];

                $courseDetails[] = [

                    'course_title' => $training->name ?? 'Anonymous Course',
                    'users_assigned' => TrainingAssignedUser::where('training', $course->training)
                        ->where('completed', 0)
                        ->where('company_id', $companyId)
                        ->count(),
                    'users_completed' => TrainingAssignedUser::where('training', $course->training)
                        ->where('completed', 1)
                        ->where('company_id', $companyId)
                        ->count(),
                    'users_in_progress' => TrainingAssignedUser::where('training', $course->training)
                        ->where('training_started', 1)
                        ->where('personal_best', '>', 0)
                        ->where('completed', 0)
                        ->where('company_id', $companyId)
                        ->count(),
                    'users_not_started' => TrainingAssignedUser::where('training', $course->training)
                        ->where('training_started', 0)
                        ->where('company_id', $companyId)
                        ->count(),
                    'avg_score' => round(TrainingAssignedUser::where('training', $course->training)
                        ->where('training_started', 1)
                        ->where('company_id', $companyId)
                        ->avg('personal_best') ?? 0),

                ];
            }

            return response()->json([
                'success' => true,
                'message' => __('Course summary report fetched successfully'),
                'data' => [
                    'courses' => $courseDetails,
                    'training_due_date_details' => $DueDateDetails
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }
}
