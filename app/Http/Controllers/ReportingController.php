<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignLive;
use App\Models\CampaignReport;
use App\Models\TrainingAssignedUser;
use App\Models\TrainingModule;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportingController extends Controller
{
    //
    public function index(){

        $camps = CampaignReport::all();

        $emails_delivered = $camps->sum('emails_delivered');
        $training_assigned = $camps->sum('training_assigned');
       
        return view('reporting', compact('camps', 'emails_delivered', 'training_assigned'));
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
