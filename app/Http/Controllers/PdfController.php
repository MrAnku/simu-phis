<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\TprmCampaign;
use App\Models\TprmCampaignLive;
use App\Models\TprmCampaignReport;
use App\Models\Users;
use Barryvdh\DomPDF\Facade\Pdf;  // Ensure this is properly imported
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class PdfController extends Controller
{
 public function downloadPdf()
{
    $id = Session::get('camp_id');

    if (!$id) {
        return back()->with('error', 'Campaign ID not found in session.');
    }

    // Get the campaign details
    $detail = Campaign::with(['campLive', 'campReport', 'trainingAssignedUsers'])
        ->where('campaign_id', $id)
        ->first();

    if (!$detail) {
        return back()->with('error', 'Campaign not found.');
    }
// return  $detail->campLive;
    // Extract only the `camp_live` data
    $camp_live = $detail->campLive;

    return view('pdf-template', compact('camp_live'));
}
 public function tprmdownloadPdf()
{

    $companyId = Auth::user()->company_id;
 $camp_live = TprmCampaignReport::where('company_id', $companyId)->get();


    return view('tprm-template', compact('camp_live'));
}

public function tprm_full_report()
{
   $companyId = Auth::user()->company_id;
 $camp_live = TprmCampaignLive::where('company_id', $companyId)->get();
// return $camp_live;
 $tprmcamps = TprmCampaignReport::where('company_id', $companyId)->get();
$tprmemails_delivered =  $tprmcamps->sum('emails_delivered');
      $tprmemails_reported =  $tprmcamps->sum('email_reported');
      $emp_compromised_reported =  $tprmcamps->sum('emp_compromised');
      $payloads_clicked_reported =  $tprmcamps->sum('payloads_clicked');
   $Arraydetails = [];
// Assign values safely, ensuring no undefined property errors
    $Arraydetails[' Emails Delivered'] = $tprmemails_delivered ?? 0;
    $Arraydetails['TPRM Email Report'] =    $tprmemails_reported ?? 0;
    $Arraydetails['Emp Compromised'] = $emp_compromised_reported ?? 0;
    $Arraydetails['Payload Clicked'] = $payloads_clicked_reported ?? 0;
return view('tprm-template', compact('Arraydetails', 'camp_live'));
}

public function tprm_campaigns_wise (Request $request)
{
 $campId = $request->query('campaignId'); // or $request->input('campaignId');
// return $campId;

        $request->validate([
            'campaignId' => 'required|string',
        ]);
        $companyId = Auth::user()->company_id;
        $camp_live = TprmCampaignLive::where('company_id', $companyId)->get();
        $reportRow = TprmCampaignReport::where('campaign_id', $campId)->where('company_id', $companyId)->first();
// return $reportRow;
        // Fetch ser group ID
        $userGroup = TprmCampaign::where('campaign_id', $campId)->where('company_id', $companyId)->first();
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
    $Arraydetails = [];
    $Arraydetails['emails_delivered'] = $reportRow->emails_delivered ?? 10;
    $Arraydetails['emails_viewed'] = $reportRow->emails_viewed ?? 10;
    $Arraydetails['email_reported'] = $reportRow->email_reported ?? 10;
    $Arraydetails['emp_compromised'] =  $reportRow->emp_compromised ?? 10;

// $ArrayData_labels = [
//     ["labels" => "Emails Delivered"],
//     ["labels" => "Emails Viewed"],
//     ["labels" => "Email Reported"],
//     ["labels" => "Emp Compromised"],
// ];

return view('tprm-template', compact('Arraydetails', 'camp_live'));

        } else {
            return response()->json(['error' => 'Campaign report or user group not found'], 404);
        }
}


}
