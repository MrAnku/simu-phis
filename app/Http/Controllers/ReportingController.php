<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Users;
use App\Models\TprmUsers;
use App\Models\Campaign;
use App\Models\TprmCampaign;
use App\Models\TprmCampaignReport;
use App\Models\CampaignLive;
use App\Models\TprmCampaignLive;
use Illuminate\Http\Request;
use App\Models\CampaignReport;
use App\Models\TrainingModule;
use Illuminate\Support\Facades\DB;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\Auth;

class ReportingController extends Controller
{
    //
    public function index()
    {
        $companyId = Auth::user()->company_id;
        $camps = CampaignReport::where('company_id', $companyId)->get();

        $emails_delivered = $camps->sum('emails_delivered');
        $training_assigned = $camps->sum('training_assigned');

        return view('reporting', compact('camps', 'emails_delivered', 'training_assigned'));
    }

    public function getChartData()
    {
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

        // Initialize dates array for the last 12 days
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

        return response()->json($formattedData);
    }



    public function fetchCampaignReport(Request $request)
    {
        $request->validate([
            'campaignId' => 'required|string',
        ]);

        $campId = $request->input('campaignId');
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

            return response()->json($response);
        } else {
            return response()->json(['error' => 'Campaign report or user group not found'], 404);
        }
    }
    public function tfetchCampaignReport(Request $request)
    {
        $request->validate([
            'campaignId' => 'required|string',
        ]);

        $campId = $request->input('campaignId');
        $companyId = Auth::user()->company_id;

        // Fetch campaign report
        $reportRow = TprmCampaignReport::where('campaign_id', $campId)
            ->where('company_id', $companyId)
            ->first();

        // Fetch user group ID
        $userGroup = TprmCampaign::where('campaign_id', $campId)
            ->where('company_id', $companyId)
            ->first();

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
                'status' => $reportRow->status,
                'no_of_users' => $no_of_users,
            ];

            return response()->json($response);
        } else {
            return response()->json(['error' => 'Campaign report or user group not found'], 404);
        }
    }

    public function fetchCampReportByUsers(Request $request)
    {
        $request->validate([
            'campaignId' => 'required|string',
        ]);

        $campId = $request->input('campaignId');
        $companyId = Auth::user()->company_id;

        $allUsers = CampaignLive::where('campaign_id', $campId)
            ->where('company_id', $companyId)
            ->get();

        if ($allUsers->isEmpty()) {
            return response()->json([
                'html' => '
                    <tr>
                        <td colspan="7" class="text-center"> No records found</td>
                    </tr>'
            ]);
        }

        $responseHtml = '';
        foreach ($allUsers as $userReport) {
            $isSent = $userReport->sent == '1' ? '<span class="badge bg-success-transparent">Success</span>' : '<span class="badge bg-warning-transparent">Pending</span>';
            $isViewed = $userReport->mail_open == '1' ? '<span class="badge bg-success-transparent">Yes</span>' : '<span class="badge bg-danger-transparent">No</span>';
            $isPayloadClicked = $userReport->payload_clicked == '1' ? '<span class="badge bg-success-transparent">Yes</span>' : '<span class="badge bg-danger-transparent">No</span>';
            $isEmailCompromised = $userReport->emp_compromised == '1' ? '<span class="badge bg-success-transparent">Yes</span>' : '<span class="badge bg-danger-transparent">No</span>';
            $isEmailReported = $userReport->email_reported == '1' ? '<span class="badge bg-success-transparent">Yes</span>' : '<span class="badge bg-danger-transparent">No</span>';

            $responseHtml .= '<tr>
                <td>' . $userReport->user_name . '</td>
                <td>' . $userReport->user_email . '</td>
                <td>' . $isSent . '</td>
                <td>' . $isViewed . '</td>
                <td>' . $isPayloadClicked . '</td>
                <td>' . $isEmailCompromised . '</td>
                <td>' . $isEmailReported . '</td>
            </tr>';
        }

        return response()->json(['html' => $responseHtml]);
    }
    public function tfetchCampReportByUsers(Request $request)
{
    // Log when the function is invoked
    \Log::info('Fetching campaign report by users', ['campaignId' => $request->input('campaignId')]);

    $request->validate([
        'campaignId' => 'required|string',
    ]);

    $campId = $request->input('campaignId');
    $companyId = Auth::user()->company_id;

    // Log the campaign ID and company ID for debugging
    \Log::info('Campaign and Company IDs', ['campaignId' => $campId, 'companyId' => $companyId]);

    $allUsers = TprmCampaignLive::where('campaign_id', $campId)
        ->where('company_id', $companyId)
        ->get();

    // Log the number of users retrieved
    \Log::info('Number of users retrieved', ['count' => $allUsers->count()]);

    if ($allUsers->isEmpty()) {
        // Log a warning if no records are found
        \Log::warning('No records found for campaign', ['campaignId' => $campId]);

        return response()->json([
            'html' => '
                <tr>
                    <td colspan="7" class="text-center"> No records found</td>
                </tr>'
        ]);
    }

    $responseHtml = '';
    foreach ($allUsers as $userReport) {
        $isSent = $userReport->sent == '1' ? '<span class="badge bg-success-transparent">Success</span>' : '<span class="badge bg-warning-transparent">Pending</span>';
        $isViewed = $userReport->mail_open == '1' ? '<span class="badge bg-success-transparent">Yes</span>' : '<span class="badge bg-danger-transparent">No</span>';
        $isPayloadClicked = $userReport->payload_clicked == '1' ? '<span class="badge bg-success-transparent">Yes</span>' : '<span class="badge bg-danger-transparent">No</span>';
        $isEmailCompromised = $userReport->emp_compromised == '1' ? '<span class="badge bg-success-transparent">Yes</span>' : '<span class="badge bg-danger-transparent">No</span>';
        $isEmailReported = $userReport->email_reported == '1' ? '<span class="badge bg-success-transparent">Yes</span>' : '<span class="badge bg-danger-transparent">No</span>';

        // Log details of each user report for debugging purposes
        \Log::info('User report details', [
            'user_name' => $userReport->user_name,
            'user_email' => $userReport->user_email,
            'sent_status' => $userReport->sent,
            'mail_open_status' => $userReport->mail_open,
            'payload_clicked_status' => $userReport->payload_clicked,
            'email_compromised_status' => $userReport->emp_compromised,
            'email_reported_status' => $userReport->email_reported
        ]);

        $responseHtml .= '<tr>
            <td>' . $userReport->user_name . '</td>
            <td>' . $userReport->user_email . '</td>
            <td>' . $isSent . '</td>
            <td>' . $isViewed . '</td>
            <td>' . $isPayloadClicked . '</td>
            <td>' . $isEmailCompromised . '</td>
            <td>' . $isEmailReported . '</td>
        </tr>';
    }

    // Log when the response is successfully generated
    \Log::info('Response HTML generated successfully', ['responseHtmlLength' => strlen($responseHtml)]);

    return response()->json(['html' => $responseHtml]);
}


    public function fetchCampTrainingDetails(Request $request)
    {
        $request->validate([
            'campaignId' => 'required|string',
        ]);

        $campId = $request->input('campaignId');
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
            // Count the number of users in the group
            $no_of_users = Users::where('group_id', $userGroup->users_group)->count();

            // Determine training assigned and completed status
            $isAssigned = (int)$reportRow->training_assigned > 0 ? '<i class="bx bx-check-circle text-success fs-25"></i>' : '<i class="bx bx-check-circle text-danger fs-25"></i>';
            $isCompleted = (int)$reportRow->training_completed > 0 ? '<i class="bx bx-check-circle text-success fs-25"></i>' : '<i class="bx bx-check-circle text-danger fs-25"></i>';

            // Determine campaign status
            if ($reportRow->status == 'completed') {
                $status = '<span class="badge bg-success">Completed</span>';
            } elseif ($reportRow->status == 'pending') {
                $status = '<span class="badge bg-warning">Pending</span>';
            } else {
                $status = '<span class="badge bg-success">Running</span>';
            }

            $responseHtml = '<tr>
                <th scope="row">' . $reportRow->campaign_name . '</th>
                <td>' . $status . '</td>
                <td>' . $no_of_users . '</td>
                <td>
                    <div class="d-flex align-items-center">
                        <span class="mx-1">' . $reportRow->training_assigned . '</span>
                        ' . $isAssigned . '
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <span class="mx-1">' . $reportRow->training_completed . '</span>
                        ' . $isCompleted . '
                    </div>
                </td>
            </tr>';

            return response()->json(['html' => $responseHtml]);
        } else {
            return response()->json(['html' => '
                <tr>
                    <td colspan="5" class="text-center"> No records found</td>
                </tr>']);
        }
    }

    public function fetchCampTrainingDetailsIndividual(Request $request)
    {
        $request->validate([
            'campaignId' => 'required|string',
        ]);

        $campId = $request->input('campaignId');
        $companyId = Auth::user()->company_id;

        // Fetch assigned training users
        $assignedUsers = TrainingAssignedUser::where('campaign_id', $campId)
            ->where('company_id', $companyId)
            ->get();

        if ($assignedUsers->isEmpty()) {
            return response()->json(['html' => '
                <tr>
                    <td colspan="6" class="text-center"> No records found</td>
                </tr>']);
        }

        $responseHtml = '';
        foreach ($assignedUsers as $assignedUser) {
            $trainingDetail = TrainingModule::find($assignedUser->training);

            $today = new \DateTime(date('Y-m-d'));
            $dueDate = new \DateTime($assignedUser->training_due_date);

            if ($dueDate > $today) {
                $status = "<span class='text-success'><strong>In training period</strong></span>";
            } else {
                $days_difference = $today->diff($dueDate)->days;
                $status = "<span class='text-danger'><strong>Overdue - " . $days_difference . " Days</strong></span>";
            }

            $responseHtml .= '
                <tr>
                    <td>' . $assignedUser->user_email . '</td>
                    <td>' . $trainingDetail->name . '</td>
                    <td>' . $assignedUser->assigned_date . '</td>
                    <td>' . $assignedUser->personal_best . '%</td>
                    <td>' . $trainingDetail->passing_score . '%</td>
                    <td>' . $status . '</td>
                </tr>';
        }

        return response()->json(['html' => $responseHtml]);
    }
}
