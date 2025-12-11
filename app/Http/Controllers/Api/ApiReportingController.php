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
use App\Models\ScormTraining;
use App\Models\AiCallCampaign;
use App\Models\AiCallCampLive;
use App\Models\AssignedPolicy;
use App\Models\CompanyLicense;
use App\Models\TrainingModule;
use App\Models\WaLiveCampaign;
use App\Models\BlueCollarGroup;
use App\Models\CompanySettings;
use App\Models\PhishingWebsite;
use App\Models\QuishingLiveCamp;
use App\Models\TprmCampaignLive;
use App\Models\WhatsappCampaign;
use App\Models\ScormAssignedUser;
use Illuminate\Http\JsonResponse;
use App\Models\BlueCollarEmployee;
use App\Models\TprmCampaignReport;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\TrainingAssignedUser;
use App\Models\WhatsAppCampaignUser;
use Illuminate\Support\Facades\Auth;
use App\Models\BlueCollarTrainingUser;
use App\Models\PolicyCampaignLive;
use App\Services\CompanyReport;
use App\Services\Reports\OverallNormalEmployeeReport;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class ApiReportingController extends Controller
{
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
                    'success' => false,
                    'message' => __('Campaign ID is required.')
                ], 400);
            }

            $companyId = Auth::user()->company_id;

            $response = TprmCampaign::with('phishingMaterial')
                ->where('company_id', $companyId)
                ->where('campaign_id', $campId)
                ->first();

            return response()->json([
                'success' => true,
                'message' => __('TPRM campaign report fetched successfully.'),
                'data' => $response
            ], 200);
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
                'message' => __('WhatsApp campaign report fetched successfully'),
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
                    'message' => __('No records found'),
                    'data' => []
                ], 404);
            }

            $data = [];

            foreach ($allUsers as $userReport) {
                $data[] = [
                    'user_name' => $userReport->user_name,
                    'mobile' => $userReport->to_mobile,
                    'call_time' => $userReport->created_at,
                    'email' => $userReport->user_email,
                    'status' => $userReport->status == 'completed' ? 'Completed' : ucfirst($userReport->status),
                    'training_assigned' => $userReport->training_assigned == '1' ? 'Yes' : 'No',
                ];
            }

            return response()->json([
                'success' => true,
                'message' => __('AI calling campaign report fetched successfully'),
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
                'message' => __('Something went wrong'),
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

    public function fetchAwarenessEduReport()    //not in used
    {

        try {
            $companyId = Auth::user()->company_id;
            $totalAssignedUsers = TrainingAssignedUser::where('company_id', $companyId)->count();

            // Handle case when there are no assigned users
            if ($totalAssignedUsers === 0) {
                return response()->json([
                    'success' => true,
                    'message' => __('No users assigned to training for this company'),
                    'data' => [
                        'training_statistics' => [
                            'total_assigned_users' => 0,
                            'not_started_training' => 0,
                            'not_started_training_rate' => 0,
                            'progress_training' => 0,
                            'progress_training_rate' => 0,
                            'completed_training' => 0,
                            'completed_training_rate' => 0
                        ],
                        'general_statistics' => [
                            'educated_user_percent' => 0,
                            'roles_responsibility_percent' => 0,
                            'certified_users_percent' => 0
                        ],
                        'education_duration_statistics' => [
                            'avg_education_duration' => 0,
                            'avg_overall_duration' => 0,
                            'training_assign_reminder_days' => 0
                        ],
                        'onboardingTrainingDetails' => []
                    ]
                ], 200);
            }

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
            $educatedUserRate = $totalAssignedUsers > 0 ? $usersWhoScored / $totalAssignedUsers * 100 : 0;

            $companyLicense = CompanyLicense::where('company_id', $companyId)->first();
            $totalEmployees = $companyLicense
                ? ($companyLicense->used_employees + $companyLicense->used_tprm_employees + $companyLicense->used_blue_collar_employees)
                : 0;
            $rolesResponsilbilityPercent = ($totalEmployees > 0) ? round($completedTraining / $totalEmployees * 100) : 0;

            $certifiedUsersRate = $totalAssignedUsers > 0
                ? TrainingAssignedUser::where('company_id', $companyId)
                ->where('certificate_id', '!=', null)->count() / $totalAssignedUsers * 100
                : 0;

            $emailCampData = Campaign::where('company_id', $companyId)
                ->pluck('days_until_due');
            $quishCampData = QuishingCamp::where('company_id', $companyId)
                ->pluck('days_until_due');
            $waCampData = WaCampaign::where('company_id', $companyId)
                ->pluck('days_until_due');
            $merged = $emailCampData->merge($quishCampData)->merge($waCampData);
            $avgEducationDuration = $merged->count() > 0 ? round($merged->avg(), 2) : 0;
            $avgOverallDuration = $avgEducationDuration;

            $trainingAssignReminderDays = (int) CompanySettings::where('company_id', $companyId)
                ->value('training_assign_remind_freq_days') ?? 0;

            $onboardingTrainingDetails = [];

            $users = Users::where('company_id', $companyId)
                ->where('company_id', $companyId)
                ->distinct('user_email')
                ->get();
            if ($users->isNotEmpty()) {
                foreach ($users as $user) {
                    $trainingAssigned = $user->assignedTrainingsNew();
                    if ($trainingAssigned->isEmpty()) {
                        continue;
                    }

                    $onboardingTrainingDetails[] = [
                        'user_name' => $trainingAssigned->user_name,
                        'user_email' => $trainingAssigned->user_email,
                        'assigned_date' => $trainingAssigned->assigned_date,
                        'completed_date' => $trainingAssigned->training_due_date,
                        'status' => $trainingAssigned->completed == 1 ? 'Completed' : 'In Progress',
                        'outstanding_training_count' => TrainingAssignedUser::where('user_email', $trainingAssigned->user_email)
                            ->where('personal_best', '>=', 70)->count(),
                    ];
                }
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
            $companyId = Auth::user()->company_id ?? null;
            if (!$companyId) {
                return response()->json([
                    'success' => false,
                    'message' => __('Company ID not found'),
                    'data' => []
                ], 400);
            }

            $totalAssignedUsers = TrainingAssignedUser::where('company_id', $companyId)->count();
            $notStartedTraining = TrainingAssignedUser::where('company_id', $companyId)->where('training_started', 0)->count();

            $notStartedTrainingRate = $totalAssignedUsers > 0 ? round($notStartedTraining / $totalAssignedUsers * 100) : 0;

            $progressTraining = TrainingAssignedUser::where('company_id', $companyId)
                ->where('training_started', 1)
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

            $educatedUserRate = $totalAssignedUsers > 0 ? ($usersWhoScored / $totalAssignedUsers * 100) : 0;

            $companyLicense = CompanyLicense::where('company_id', $companyId)->first();
            $totalEmployees = $companyLicense
                ? ((int)($companyLicense->used_employees ?? 0) + (int)($companyLicense->used_tprm_employees ?? 0) + (int)($companyLicense->used_blue_collar_employees ?? 0))
                : 0;

            $rolesResponsilbilityPercent = ($totalEmployees > 0) ? round($completedTraining / $totalEmployees * 100) : 0;

            $certifiedUsersRate = $totalAssignedUsers > 0
                ? (TrainingAssignedUser::where('company_id', $companyId)
                    ->whereNotNull('certificate_id')->count() / $totalAssignedUsers * 100)
                : 0;

            $emailCampData = Campaign::where('company_id', $companyId)
                ->pluck('days_until_due') ?? collect();

            $quishCampData = QuishingCamp::where('company_id', $companyId)
                ->pluck('days_until_due') ?? collect();

            $waCampData = WaCampaign::where('company_id', $companyId)
                ->pluck('days_until_due') ?? collect();

            $merged = $emailCampData->merge($quishCampData)->merge($waCampData);
            $avgEducationDuration = $merged->count() > 0 ? round($merged->avg(), 2) : 0;

            $avgOverallDuration = $avgEducationDuration;

            $trainingAssignReminderFreqDays = (int) (CompanySettings::where('company_id', $companyId)
                ->value('training_assign_remind_freq_days') ?? 0);

            $users = Users::where('company_id', $companyId)
                ->select('id', 'user_name', 'user_email')
                ->distinct()
                ->get();

            $onboardingTrainingDetails = [];

            if ($users && $users->isNotEmpty()) {
                foreach ($users as $user) {
                    // Get group name
                    $group = UsersGroup::whereJsonContains('users', $user->id)->first();
                    $groupName = $group ? $group->group_name : null;

                    // Get all trainings for this user
                    $trainings = TrainingAssignedUser::where('company_id', $companyId)
                        ->where('user_email', $user->user_email)
                        ->get();

                    $totalAssignedTrainings = $trainings ? $trainings->count() : 0;
                    $totalCompletedTrainings = $trainings ? $trainings->where('completed', 1)->count() : 0;
                    $totalCertificates = $trainings ? $trainings->whereNotNull('certificate_id')->count() : 0;
                    $outstandingTrainingCount = $trainings ? $trainings->where('personal_best', '>=', 70)->count() : 0;

                    $assignedTrainings = [];
                    if ($trainings && $trainings->isNotEmpty()) {
                        foreach ($trainings as $training) {
                            $trainingData = $training->trainingData ?? null;
                            $assignedTrainings[] = [
                                'training_name' => $trainingData ? $trainingData->name : 'N/A',
                                'training_type' => $training->training_type ?? 'N/A',
                                'assigned_date' => $training->assigned_date ?? null,
                                'training_started' => isset($training->training_started) && $training->training_started == 1 ? 'Yes' : 'No',
                                'passing_score' => $trainingData ? $trainingData->passing_score : null,
                                'training_due_date' => $training->training_due_date ?? null,
                                'is_completed' => isset($training->completed) && $training->completed == 1 ? 'Yes' : 'No',
                                'completion_date' => $training->completion_date ?? null,
                                'personal_best' => $training->personal_best ?? null,
                                'certificate_id' => $training->certificate_id ?? null,
                                'last_reminder_date' => $training->last_reminder_date ?? null,
                                'status' => isset($training->completed) && $training->completed == 1 ? 'Completed' : 'In Progress',
                            ];
                        }
                    }

                    $onboardingTrainingDetails[] = [
                        'user_name' => $user->user_name,
                        'user_email' => $user->user_email,
                        // 'division' => $groupName,
                        'total_assigned_trainings' => $totalAssignedTrainings,
                        'assigned_trainings' => $assignedTrainings,
                        'total_completed_trainings' => $totalCompletedTrainings,
                        'total_certificates' => $totalCertificates,
                        'count_of_outstanding_training' => $outstandingTrainingCount,
                    ];
                }
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
                'message' => __('Error: ') . $e->getMessage(),
                'data' => []
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
                'Poor' => [0, 20],
                'Fair' => [21, 40],
                'Good' => [41, 60],
                'Very Good' => [61, 80],
                'Excellent' => [81, 100],
            ];

            $divisionGroupDetails = [];

            $totalUsers = \App\Models\Users::where('company_id', $companyId)->count();
            if ($totalUsers === 0) {
                return response()->json([
                    'success' => true,
                    'message' => __('No users found for this company'),
                    'data' => [
                        'total_divisions' => 0,
                        'division_group_details' => [],
                        'campaigns_last_7_days' => [],
                    ]
                ], 200);
            }

            foreach ($userGroups as $group) {
                $users = json_decode($group->users, true);
                if (!$users) {
                    continue;
                }

                $groupTotalUsers = 0;
                $groupTotalSimulations = 0;
                $groupTotalCompromisedSimulations = 0;

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

                    // WhatsApp simulation
                    $totalWhatsapp = \App\Models\WaLiveCampaign::where('company_id', $companyId)
                        ->where('user_id', $user->id)
                        ->count();
                    $compromisedWhatsapp = \App\Models\WaLiveCampaign::where('company_id', $companyId)
                        ->where('user_id', $user->id)
                        ->where('compromised', 1)
                        ->count();

                    // Final counts (Tprm removed)
                    $totalAll = $totalSimulations + $totalQuishing + $totalWhatsapp;
                    $compromisedAll = $compromisedSimulations + $compromisedQuishing + $compromisedWhatsapp;

                    // For group stats
                    $groupTotalUsers++;
                    $groupTotalSimulations += $totalAll;
                    $groupTotalCompromisedSimulations += $compromisedAll;
                }

                // Campaigns ran in this group (by group id in campaigns)
                $campaignsRan = \App\Models\Campaign::where('users_group', $group->group_id)
                    ->where('company_id', $companyId)
                    ->count();

                // Compromised rate and performance score
                $compromisedRate = $groupTotalSimulations > 0
                    ? round(($groupTotalCompromisedSimulations / $groupTotalSimulations) * 100, 2)
                    : 0;

                $performanceScore = $groupTotalSimulations > 0
                    ? 100 - $compromisedRate
                    : 100;

                // Group risk score (average of user risk scores in group)
                $groupRiskScore = $groupTotalUsers > 0
                    ? round($groupTotalSimulations > 0 ? 100 - (($groupTotalCompromisedSimulations / $groupTotalSimulations) * 100) : 100, 2)
                    : 100;

                // Group risk level
                $groupRiskLevel = 'unknown';
                foreach ($scoreRanges as $label => [$min, $max]) {
                    if ($groupRiskScore >= $min && $groupRiskScore <= $max) {
                        $groupRiskLevel = $label;
                        break;
                    }
                }

                $divisionGroupDetails[] = [
                    'group_name' => $group->group_name,
                    'group_id' => $group->group_id,
                    'total_users' => $groupTotalUsers,
                    'total_campaigns' => $campaignsRan,
                    'total_simulations' => $groupTotalSimulations,
                    'total_compromised' => $groupTotalCompromisedSimulations,
                    'compromised_rate' => $compromisedRate,
                    'performance_score' => $performanceScore,
                    'risk_score' => $groupRiskScore,
                    'risk_level' => $groupRiskLevel,
                ];
            }

            // Campaigns in last 7 days with type and compromised rate
            $sevenDaysAgo = now()->subDays(7)->startOfDay();
            $campaignsLast7Days = [];

            // Email campaigns
            $emailCampaigns = \App\Models\Campaign::where('company_id', $companyId)
                ->whereDate('created_at', '>=', $sevenDaysAgo)
                ->get();

            foreach ($emailCampaigns as $camp) {
                $total = \App\Models\CampaignLive::where('campaign_id', $camp->campaign_id)
                    ->where('company_id', $companyId)
                    ->count();
                $compromised = \App\Models\CampaignLive::where('campaign_id', $camp->campaign_id)
                    ->where('company_id', $companyId)
                    ->where('emp_compromised', 1)
                    ->count();
                $rate = $total > 0 ? round(($compromised / $total) * 100, 2) : 0;
                $campaignsLast7Days[] = [
                    'campaign_id' => $camp->campaign_id,
                    'campaign_name' => $camp->campaign_name,
                    'campaign_type' => 'email',
                    'total_users' => $total,
                    'compromised_users' => $compromised,
                    'compromised_rate' => $rate,
                    'created_at' => $camp->created_at->format('Y-m-d H:i:s'),
                ];
            }

            // Quishing campaigns
            $quishingCampaigns = \App\Models\QuishingCamp::where('company_id', $companyId)
                ->whereDate('created_at', '>=', $sevenDaysAgo)
                ->get();

            foreach ($quishingCampaigns as $camp) {
                $total = \App\Models\QuishingLiveCamp::where('campaign_id', $camp->campaign_id)
                    ->where('company_id', $companyId)
                    ->count();
                $compromised = \App\Models\QuishingLiveCamp::where('campaign_id', $camp->campaign_id)
                    ->where('company_id', $companyId)
                    ->where('compromised', '1')
                    ->count();
                $rate = $total > 0 ? round(($compromised / $total) * 100, 2) : 0;
                $campaignsLast7Days[] = [
                    'campaign_id' => $camp->campaign_id,
                    'campaign_name' => $camp->campaign_name,
                    'campaign_type' => 'quishing',
                    'total_users' => $total,
                    'compromised_users' => $compromised,
                    'compromised_rate' => $rate,
                    'created_at' => $camp->created_at->format('Y-m-d H:i:s'),
                ];
            }

            // WhatsApp campaigns
            $waCampaigns = \App\Models\WaCampaign::where('company_id', $companyId)
                ->whereDate('created_at', '>=', $sevenDaysAgo)
                ->get();

            foreach ($waCampaigns as $camp) {
                $total = \App\Models\WaLiveCampaign::where('campaign_id', $camp->campaign_id)
                    ->where('company_id', $companyId)
                    ->count();
                $compromised = \App\Models\WaLiveCampaign::where('campaign_id', $camp->campaign_id)
                    ->where('company_id', $companyId)
                    ->where('compromised', 1)
                    ->count();
                $rate = $total > 0 ? round(($compromised / $total) * 100, 2) : 0;
                $campaignsLast7Days[] = [
                    'campaign_id' => $camp->campaign_id,
                    'campaign_name' => $camp->campaign_name,
                    'campaign_type' => 'whatsapp',
                    'total_users' => $total,
                    'compromised_users' => $compromised,
                    'compromised_rate' => $rate,
                    'created_at' => $camp->created_at->format('Y-m-d H:i:s'),
                ];
            }

            // TPRM campaigns
            $tprmCampaigns = \App\Models\TprmCampaign::where('company_id', $companyId)
                ->whereDate('created_at', '>=', $sevenDaysAgo)
                ->get();

            foreach ($tprmCampaigns as $camp) {
                $total = \App\Models\TprmCampaignLive::where('campaign_id', $camp->campaign_id)
                    ->where('company_id', $companyId)
                    ->count();
                $compromised = \App\Models\TprmCampaignLive::where('campaign_id', $camp->campaign_id)
                    ->where('company_id', $companyId)
                    ->where('emp_compromised', 1)
                    ->count();
                $rate = $total > 0 ? round(($compromised / $total) * 100, 2) : 0;
                $campaignsLast7Days[] = [
                    'campaign_id' => $camp->campaign_id,
                    'campaign_name' => $camp->campaign_name,
                    'campaign_type' => 'tprm',
                    'total_users' => $total,
                    'compromised_users' => $compromised,
                    'compromised_rate' => $rate,
                    'created_at' => $camp->created_at->format('Y-m-d H:i:s'),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => __('Division users report fetched successfully'),
                'data' => [
                    'total_divisions' => count($userGroups),
                    'division_group_details' => $divisionGroupDetails,
                    'campaigns_last_7_days' => $campaignsLast7Days,
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
            $usersReport = new OverallNormalEmployeeReport($companyId);
            $reportData = $usersReport->generateReport();
            return response()->json([
                'success' => true,
                'message' => __('Users report fetched successfully'),
                'data' => $reportData
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
                            'totalCampaigns' => AiCallCampLive::where('user_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->count(),
                            'totalTrainings' => AiCallCampLive::where('user_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->where('training_assigned', 1)
                                ->count(),
                            'call_send' => AiCallCampLive::where('user_email', $user->user_email)
                                ->where('company_id', $companyId)
                                ->whereNotNull('call_send_response')
                                ->count(),
                            'call_reported' => AiCallCampLive::where('user_email', $user->user_email)
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

            // Get all training modules
            $trainingModules = TrainingModule::where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhere('company_id', 'default');
            })
                ->whereHas('trainingAssigned')
                ->get();

            // Get all training games
            $trainingGames = TrainingGame::get();

            $trainings = [];
            $overallStats = [
                'total_assigned_trainings' => 0,
                'total_completed_trainings' => 0,
                'total_in_progress_trainings' => 0,
                'total_not_started_trainings' => 0,
                'total_overdue_trainings' => 0,
                'total_certified_users' => 0,
                'users_scored_above_40' => 0,
                'users_scored_above_60' => 0,
                'users_scored_above_80' => 0,
                'overall_completion_rate' => 0,
                'overall_progress_rate' => 0,
                'static_training_users' => 0,
                'scorm_training_users' => 0,
                'gamified_training_users' => 0,
                'ai_training_users' => 0,
                'game_training_users' => 0
            ];

            // Process training modules
            foreach ($trainingModules as $trainingModule) {
                $assignedTrainings = TrainingAssignedUser::where('training', $trainingModule->id)
                    ->where('company_id', $companyId);

                $totalAssigned = $assignedTrainings->count();
                $completedTrainings = (clone $assignedTrainings)->where('completed', 1)->count();
                $inProgressTrainings = (clone $assignedTrainings)->where('training_started', 1)->where('completed', 0)->count();
                $notStartedTrainings = (clone $assignedTrainings)->where('training_started', 0)->count();
                $overdueTrainings = (clone $assignedTrainings)
                    ->where('training_due_date', '<', now())
                    ->where('completed', 0)
                    ->count();
                $certifiedUsers = (clone $assignedTrainings)->whereNotNull('certificate_id')->count();
                $usersScored40Plus = (clone $assignedTrainings)->where('personal_best', '>=', 40)->count();
                $usersScored60Plus = (clone $assignedTrainings)->where('personal_best', '>=', 60)->count();
                $usersScored80Plus = (clone $assignedTrainings)->where('personal_best', '>=', 80)->count();

                $completionRate = $totalAssigned > 0 ? round(($completedTrainings / $totalAssigned) * 100, 2) : 0;
                $progressRate = $totalAssigned > 0 ? round((($inProgressTrainings + $completedTrainings) / $totalAssigned) * 100, 2) : 0;

                // Determine training type based on module properties
                $trainingType = 'static_training';
                if ($trainingModule->training_type == 'gamified') {
                    $trainingType = 'gamified';
                    $overallStats['gamified_training_users'] += $totalAssigned;
                } else if ($trainingModule->training_type == 'static_training') {
                    $overallStats['static_training_users'] += $totalAssigned;
                }

                $trainings[] = [
                    'training_id' => $trainingModule->id,
                    'name' => $trainingModule->name,
                    'training_type' => $trainingType,
                    'description' => $trainingModule->description ?? '',
                    'passing_score' => $trainingModule->passing_score ?? 0,
                    'total_assigned_trainings' => $totalAssigned,
                    'completed_trainings' => $completedTrainings,
                    'in_progress_trainings' => $inProgressTrainings,
                    'not_started_trainings' => $notStartedTrainings,
                    'overdue_trainings' => $overdueTrainings,
                    'certified_users' => $certifiedUsers,
                    'users_scored_40_plus' => $usersScored40Plus,
                    'users_scored_60_plus' => $usersScored60Plus,
                    'users_scored_80_plus' => $usersScored80Plus,
                    'completion_rate' => $completionRate,
                    'progress_rate' => $progressRate,
                    'average_score' => round((clone $assignedTrainings)->avg('personal_best') ?? 0, 2),
                    'simulation_counts' => [
                        'email_simulations' => CampaignLive::where('training_module', $trainingModule->id)
                            ->where('company_id', $companyId)
                            ->count(),
                        'quishing_simulations' => QuishingLiveCamp::where('training_module', $trainingModule->id)
                            ->where('company_id', $companyId)
                            ->count(),
                        'ai_call_simulations' => AiCallCampLive::where('training_module', $trainingModule->id)
                            ->where('company_id', $companyId)
                            ->count(),
                        'whatsapp_simulations' => WaLiveCampaign::where('training_module', $trainingModule->id)
                            ->where('company_id', $companyId)
                            ->count()
                    ],
                    // 'created_at' => $trainingModule->created_at->format('Y-m-d H:i:s'),
                ];

                // Update overall stats
                $overallStats['total_assigned_trainings'] += $totalAssigned;
                $overallStats['total_completed_trainings'] += $completedTrainings;
                $overallStats['total_in_progress_trainings'] += $inProgressTrainings;
                $overallStats['total_not_started_trainings'] += $notStartedTrainings;
                $overallStats['total_overdue_trainings'] += $overdueTrainings;
                $overallStats['total_certified_users'] += $certifiedUsers;
                $overallStats['users_scored_above_40'] += $usersScored40Plus;
                $overallStats['users_scored_above_60'] += $usersScored60Plus;
                $overallStats['users_scored_above_80'] += $usersScored80Plus;
            }

          

            //process scorm trainings
            $assignedScorm = ScormAssignedUser::where('company_id', $companyId)->count();
            $overallStats['scorm_training_users'] = $assignedScorm;

            //ai training
            $assignedAiTraining = TrainingAssignedUser::where('training_type', 'ai_training')
                ->where('company_id', $companyId)
                ->count();
            $overallStats['ai_training_users'] = $assignedAiTraining;

            // Process training games
            $gameTrainings = [];
            foreach ($trainingGames as $game) {
                $assignedGames = TrainingAssignedUser::where('training', $game->id)
                    ->where('training_type', 'games')
                    ->where('company_id', $companyId);

                $totalAssigned = $assignedGames->count();
                $completedGames = (clone $assignedGames)->where('completed', 1)->count();
                $inProgressGames = (clone $assignedGames)->where('training_started', 1)->where('completed', 0)->count();
                $notStartedGames = (clone $assignedGames)->where('training_started', 0)->count();

                $completionRate = $totalAssigned > 0 ? round(($completedGames / $totalAssigned) * 100, 2) : 0;
                $averageGameTime = (clone $assignedGames)->avg('game_time') ?? 0;

                $gameTrainings[] = [
                    'game_id' => $game->id,
                    'name' => $game->name,
                    'description' => $game->description ?? '',
                    'total_assigned_games' => $totalAssigned,
                    'completed_games' => $completedGames,
                    'in_progress_games' => $inProgressGames,
                    'not_started_games' => $notStartedGames,
                    'completion_rate' => $completionRate,
                    'average_game_time_seconds' => round($averageGameTime, 2),
                    'simulation_counts' => [
                        'email_simulations' => CampaignLive::where('training_type', 'games')
                            ->where('training_module', $game->id)
                            ->where('company_id', $companyId)
                            ->count(),
                        'quishing_simulations' => QuishingLiveCamp::where('training_type', 'games')
                            ->where('training_module', $game->id)
                            ->where('company_id', $companyId)
                            ->count(),
                        'ai_call_simulations' => AiCallCampLive::where('training_type', 'games')
                            ->where('training_module', $game->id)
                            ->where('company_id', $companyId)
                            ->count(),
                        'whatsapp_simulations' => WaLiveCampaign::where('training_type', 'games')
                            ->where('training_module', $game->id)
                            ->where('company_id', $companyId)
                            ->count()
                    ],
                    'created_at' => $game->created_at->format('Y-m-d H:i:s'),
                ];

                $overallStats['game_training_users'] += $totalAssigned;
                $overallStats['total_assigned_trainings'] += $totalAssigned;
                $overallStats['total_completed_trainings'] += $completedGames;
                $overallStats['total_in_progress_trainings'] += $inProgressGames;
                $overallStats['total_not_started_trainings'] += $notStartedGames;
            }

            // Calculate overall rates
            $overallStats['overall_completion_rate'] = $overallStats['total_assigned_trainings'] > 0
                ? round(($overallStats['total_completed_trainings'] / $overallStats['total_assigned_trainings']) * 100, 2)
                : 0;

            $overallStats['overall_progress_rate'] = $overallStats['total_assigned_trainings'] > 0
                ? round((($overallStats['total_in_progress_trainings'] + $overallStats['total_completed_trainings']) / $overallStats['total_assigned_trainings']) * 100, 2)
                : 0;

            // Get recent training activities (last 30 days)
            $recentActivities = TrainingAssignedUser::where('company_id', $companyId)
                ->where('created_at', '>=', now()->subDays(30))
                ->with('trainingData')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($activity) {
                    return [
                        'user_email' => $activity->user_email,
                        'training_name' => $activity->trainingData->name ?? 'N/A',
                        'training_type' => $activity->training_type,
                        'assigned_date' => $activity->assigned_date,
                        'completion_status' => $activity->completed ? 'Completed' : ($activity->training_started ? 'In Progress' : 'Not Started'),
                        'personal_best' => $activity->personal_best,
                        'certificate_id' => $activity->certificate_id
                    ];
                });


                  // Separate survey responses   
         $surveyResponses = TrainingAssignedUser::where('company_id', $companyId)
            ->whereNotNull('survey_response')
            ->with('trainingData')
            ->get()
            ->map(function ($item) {
                return [
                    'user_name' => $item->user_name,
                    'user_email' => $item->user_email,
                    'training' => $item->trainingData->name ?? 'N/A',
                    'survey_response' => $item->survey_response
                ];
            });

            return response()->json([
                'success' => true,
                'message' => __('Advanced training report fetched successfully'),
                'data' => [
                    'overall_statistics' => $overallStats,
                    'training_modules' => $trainings,
                    'game_trainings' => $gameTrainings,
                    'recent_activities' => $recentActivities,
                    'survey_responses' => $surveyResponses,
                    'summary' => [
                        'total_training_modules' => count($trainings),
                        'total_game_modules' => count($gameTrainings),
                        'most_popular_training' => collect($trainings)->sortByDesc('total_assigned_trainings')->first()['name'] ?? 'N/A',
                        'highest_completion_rate' => collect($trainings)->max('completion_rate') ?? 0,
                        'lowest_completion_rate' => collect($trainings)->min('completion_rate') ?? 0
                    ]
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
                    'total_no_of_ai_camp' => AiCallCampLive::where('training_module', $trainingModule->id)->count(),
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
                    'total_no_of_ai_camp' => AiCallCampLive::where('training_module', $latestTraining->id)->count(),
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
                    'total_no_of_ai_camp' => AiCallCampLive::where('training_type', 'games')->where('training_module', $trainingGame->id)->count(),
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

            // Total policies available
            $totalPolicies = Policy::where('company_id', $companyId)->count();

            // Policies assigned (sent to users)
            $assignedPolicies = AssignedPolicy::where('company_id', $companyId)->get();
            $totalAssignedPolicies = $assignedPolicies->count();

            // Policies sent using campaign (assuming policies assigned via campaign have campaign_id set)
            $policiesSentViaCampaign = AssignedPolicy::where('company_id', $companyId)
                ->whereNotNull('campaign_id')
                ->count();

            // Total campaigns ran for policies (distinct campaign_id in assigned_policies)
            $totalPolicyCampaigns = AssignedPolicy::where('company_id', $companyId)
                ->whereNotNull('campaign_id')
                ->distinct('campaign_id')
                ->count('campaign_id');

            // Users who responded for quiz (json_quiz_response not null)
            $usersRespondedQuiz = AssignedPolicy::where('company_id', $companyId)
                ->whereNotNull('json_quiz_response')
                ->count();

            // Users who accepted policy
            $usersAccepted = AssignedPolicy::where('company_id', $companyId)
                ->where('accepted', 1)
                ->count();

            // Users who did not accept policy
            $usersNotAccepted = AssignedPolicy::where('company_id', $companyId)
                ->where('accepted', 0)
                ->count();

            //average time taken by users to read and accept the policy
            $averageTimeToAccept = AssignedPolicy::where('company_id', $companyId)
                ->whereNotNull('reading_time') // time storing in seconds
                ->get()
                ->avg('reading_time');

            // Details for each assigned policy
            $assignedPolicyDetails = [];
            foreach ($assignedPolicies as $assignedPolicy) {
                $policy = Policy::find($assignedPolicy->policy);
                $assignedPolicyDetails[] = [
                    'policy_name' => $policy ? $policy->policy_name : 'N/A',
                    'accepted' => $assignedPolicy->accepted == 1 ? 'Yes' : 'No',
                    'accepted_date' => $assignedPolicy->accepted_at
                        ? Carbon::parse($assignedPolicy->accepted_at)->format('Y-m-d')
                        : 'Not Accepted',
                    'reading_time' => $assignedPolicy->reading_time
                        ? ($assignedPolicy->reading_time < 60
                            ? round($assignedPolicy->reading_time, 2) . ' seconds'
                            : round($assignedPolicy->reading_time / 60, 2) . ' minutes')
                        : 'N/A',
                    'user_email' => $assignedPolicy->user_email,
                    'user_name' => $assignedPolicy->user_name,
                    'json_quiz_response' => $assignedPolicy->json_quiz_response ? json_decode($assignedPolicy->json_quiz_response, true) : null,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => __('Policies report fetched successfully'),
                'data' => [
                    'total_policies' => $totalPolicies,
                    'total_policies_sent_via_campaign' => $policiesSentViaCampaign,
                    'total_assigned_policies' => $totalAssignedPolicies,
                    'total_policy_campaigns' => $totalPolicyCampaigns,
                    'users_responded_quiz' => $usersRespondedQuiz,
                    'users_accepted_policy' => $usersAccepted,
                    'users_not_accepted_policy' => $usersNotAccepted,
                    'assigned_policies' => $assignedPolicyDetails,
                    'average_time_to_accept' => $averageTimeToAccept
                        ? ($averageTimeToAccept < 60
                            ? round($averageTimeToAccept, 2) . ' seconds'
                            : round($averageTimeToAccept / 60, 2) . ' minutes')
                        : 'N/A',
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

            $totalCourses = TrainingModule::where('company_id', $companyId)
                ->orWhere('company_id', 'default')
                ->count();

            $totalScormCourses = ScormTraining::where('company_id', $companyId)
                ->count();

            //  Get assigned courses and scorm courses
            $assignedCourses = [
                'assigned_courses' => TrainingAssignedUser::where('company_id', $companyId)
                    ->where('completed', 0)
                    ->count(),
                'assigned_scorm_courses' => ScormAssignedUser::where('company_id', $companyId)
                    ->where('completed', 0)
                    ->count()
            ];

            $mostAssignedCourses = TrainingAssignedUser::where('company_id', $companyId)
                ->select('training', DB::raw('COUNT(*) as assignment_count'))
                ->groupBy('training')
                ->having('assignment_count', '>', 4)
                ->orderBy('assignment_count', 'desc')
                ->get()
                ->map(function ($item) {
                    $training = TrainingModule::find($item->training);
                    return [
                        'training_id' => $item->training,
                        'training_name' => $training ? $training->name : 'Anonymous Course',
                        'assignment_count' => $item->assignment_count
                    ];
                });

            $courseDetails = TrainingAssignedUser::where('company_id', $companyId)
                ->select(
                    'training',
                    DB::raw('SUM(CASE WHEN completed = 0 THEN 1 ELSE 0 END) as total_assigned'),
                    DB::raw('SUM(CASE WHEN training_started = 1 AND completed = 0 THEN 1 ELSE 0 END) as total_in_progress'),
                    DB::raw('SUM(CASE WHEN training_started = 0 AND completed = 0 THEN 1 ELSE 0 END) as total_not_started'),
                    DB::raw('SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as total_completed'),
                    DB::raw('AVG(CASE WHEN personal_best > 0 THEN personal_best ELSE NULL END) as avg_score')
                )
                ->groupBy('training')
                ->get()
                ->map(function ($item) {
                    $training = TrainingModule::find($item->training);
                    return [
                        'training_id' => $item->training,
                        'training_name' => $training ? $training->name : 'Anonymous Course',
                        'total_assigned' => $item->total_assigned,
                        'total_in_progress' => $item->total_in_progress,
                        'total_not_started' => $item->total_not_started,
                        'total_completed' => $item->total_completed,
                        'average_score' => round($item->avg_score ?? 0, 2)
                    ];
                });

            $totalOverDueCourses = TrainingAssignedUser::where('company_id', $companyId)
                ->where('completed', 0)
                ->where('training_due_date', '<', date('Y-m-d'))
                ->count();

            $totalComplete = TrainingAssignedUser::where('company_id', $companyId)
                ->where('completed', 1)
                ->count();

            $companyReport = new CompanyReport($companyId);

            $completionRate =  $companyReport->trainingCompletionRate();

            $completionTrendOverTime = $companyReport->getTrainingCompletionTrend();

            $topPerformedCourses = TrainingAssignedUser::where('company_id', $companyId)
                ->where('completed', 1)
                ->where('personal_best', '>', 90)
                ->select('training', DB::raw('AVG(personal_best) as average_score'))
                ->groupBy('training')
                ->having('average_score', '>=', 90)
                ->orderBy('average_score', 'desc')
                ->get()
                ->map(function ($item) {
                    $training = TrainingModule::find($item->training);
                    return [
                        'training_id' => $item->training,
                        'training_name' => $training ? $training->name : 'Anonymous Course',
                        'average_score' => round($item->average_score ?? 0, 2)
                    ];
                });

            $worstPerformedCourses = TrainingAssignedUser::where('company_id', $companyId)
                ->where('personal_best', '<', 30)
                ->select('training', DB::raw('AVG(personal_best) as average_score'))
                ->groupBy('training')
                ->having('average_score', '<=', 30)
                ->orderBy('average_score', 'asc')
                ->get()
                ->map(function ($item) {
                    $training = TrainingModule::find($item->training);
                    return [
                        'training_id' => $item->training,
                        'training_name' => $training ? $training->name : 'Anonymous Course',
                        'average_score' => round($item->average_score ?? 0, 2)
                    ];
                });

            $quicklyCompletedCourses = TrainingAssignedUser::where('company_id', $companyId)
                ->where('completed', 1)
                ->select('training', DB::raw('AVG(TIMESTAMPDIFF(SECOND, assigned_date, completion_date)) as avg_completion_time'))
                ->groupBy('training')
                ->orderBy('avg_completion_time', 'asc')
                ->get()
                ->map(function ($item) {
                    $training = TrainingModule::find($item->training);
                    return [
                        'training_id' => $item->training,
                        'training_name' => $training ? $training->name : 'Anonymous Course'
                    ];
                });

            $certificatesAwarded = TrainingAssignedUser::where('company_id', $companyId)
                ->whereNotNull('certificate_id')
                ->whereNotNull('certificate_path')
                ->select('training', DB::raw('COUNT(*) as certificate_count'))
                ->groupBy('training')
                ->orderBy('certificate_count', 'desc')
                ->get()
                ->map(function ($item) {
                    $training = TrainingModule::find($item->training);
                    return [
                        'training_id' => $item->training,
                        'training_name' => $training ? $training->name : 'Anonymous Course',
                        'certificate_count' => $item->certificate_count
                    ];
                });

            $badgesAwarded = TrainingAssignedUser::where('company_id', $companyId)
                ->whereNotNull('badge')
                ->whereNotIn('badge', ['', '[]', 'null'])
                ->get()
                ->groupBy('training')
                ->map(function ($rows, $trainingId) {

                    $uniqueBadges = $rows
                        ->flatMap(fn($row) => json_decode($row->badge, true) ?? [])
                        ->unique();

                    $training = TrainingModule::find($trainingId);

                    return [
                        'training_id'   => $trainingId,
                        'training_name' => $training ? $training->name : 'Anonymous Course',
                        'badge_count'   => $uniqueBadges->count(),
                    ];
                })
                ->sortByDesc('badge_count')
                ->values();

            return response()->json([
                'success' => true,
                'message' => __('Course summary report fetched successfully'),
                'data' => [
                    'total_courses' => $totalCourses,
                    'total_scorm_courses' => $totalScormCourses,
                    'assigned_courses' => $assignedCourses,
                    'most_assigned_courses' => $mostAssignedCourses,
                    'course_details' => $courseDetails,
                    'total_overdue_courses' => $totalOverDueCourses,
                    'total_completed' => $totalComplete,
                    'completion_rate' => $completionRate,
                    'completion_trend_over_time' => $completionTrendOverTime,
                    'top_performed_courses' => $topPerformedCourses,
                    'worst_performed_courses' => $worstPerformedCourses,
                    'quickly_completed_courses' => $quicklyCompletedCourses,
                    'certificates_awarded' => $certificatesAwarded,
                    'badges_awarded' => $badgesAwarded
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function fetchDomainWiseReport(Request $request)
    {
        try {
            $domain = $request->route('domain');
            if (!$domain) {
                return response()->json(['success' => false, 'message' => __('Domain is required')], 422);
            }
            $companyId = Auth::user()->company_id;

            $userDetails = Users::where('company_id', $companyId)
                ->where('user_email', 'LIKE', '%' . $domain)
                ->select(['user_name', 'user_email', 'whatsapp'])
                ->get()->unique('user_email');

            $companyReport = new CompanyReport($companyId);

            $emailCamp = $companyReport->getDomainWiseEmailCamp($domain);
            $quishCamp = $companyReport->getDomainWiseQuishCamp($domain);
            $aiCamp = $companyReport->getDomainWiseAiCamp($domain);
            $waCamp = $companyReport->getDomainWiseWaCamp($domain);
            $trainings = $companyReport->getDomainWiseTrainings($domain);
            $policies = $companyReport->getDomainWisePolicies($domain);

            return response()->json([
                'success' => true,
                'message' => __('Domain wise report fetched successfully'),
                'data' => [
                    'user_details' => $userDetails->values(),
                    'total_users' => $userDetails->count(),
                    'email_campaigns' => $emailCamp,
                    'quishing_campaigns' => $quishCamp,
                    'ai_vishing_campaigns' => $aiCamp,
                    'whatsapp_campaigns' => $waCamp,
                    'trainings' => $trainings,
                    'policies' => $policies,
                    'domain_risk_score' => $companyReport->calDomainRiskScore($domain),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }
}
