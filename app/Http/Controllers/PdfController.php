<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignLive;
use App\Models\CampaignReport;
use App\Models\TprmCampaign;
use App\Models\TprmCampaignLive;
use App\Models\TprmCampaignReport;
use App\Models\Users;
use App\Models\WhatsappCampaign;
use App\Models\WhatsAppCampaignUser;
use Barryvdh\DomPDF\Facade\Pdf;  // Ensure this is properly imported
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
 $tprmcamps = TprmCampaignLive::where('company_id', $companyId)->get();

      $training_assigned =  $tprmcamps->sum('training_assigned');
      $emp_compromised_reported =  $tprmcamps->sum('emp_compromised');
      $payloads_clicked_reported =  $tprmcamps->sum('payload_clicked');
// return  $payloads_clicked_reported;
// return  $camp_live;
   $Arraydetails = [];
$ArrayCount = [];
// Assign values safely, ensuring no undefined property errors
    $Arraydetails['Payload Clicked'] = $payloads_clicked_reported ?? 0;
    $Arraydetails['Emp Compromised'] = $emp_compromised_reported ?? 0;
    $Arraydetails['Training Assigned'] =    $training_assigned ?? 0;
$ArrayData_labels = [
    ["labels" => "Payload Clicked"],
    ["labels" => "Emp Compromised"],
    ["labels" => "Training Assigned"],
];
$ArrayCount['array_count'] = $training_assigned + $payloads_clicked_reported + $emp_compromised_reported;
return view('tprm-template', compact('Arraydetails', 'camp_live','ArrayData_labels','ArrayCount'));
}

public function tprm_campaigns_wise (Request $request)
{
 $campId = $request->query('campaignId'); // or 
       
$companyId = Auth::user()->company_id;
 $camp_live = TprmCampaignLive::where('company_id', $companyId)->where('campaign_id',  $campId)->get();
// return $camp_live;
 $tprmcamps = TprmCampaignLive::where('company_id', $companyId)->get();

      $training_assigned =  $tprmcamps->sum('training_assigned');
      $emp_compromised_reported =  $tprmcamps->sum('emp_compromised');
      $payloads_clicked_reported =  $tprmcamps->sum('payloads_clicked');
   $Arraydetails = [];
$ArrayCount = [];
// Assign values safely, ensuring no undefined property errors
    $Arraydetails['Payload Clicked'] = $payloads_clicked_reported ?? 0;
    $Arraydetails['Emp Compromised'] = $emp_compromised_reported ?? 0;
    $Arraydetails['Training Assigned'] =    $training_assigned ?? 0;
$ArrayData_labels = [
    ["labels" => "Payload Clicked"],
    ["labels" => "Emp Compromised"],
    ["labels" => "Training Assigned"],
];
$ArrayCount['array_count'] = $training_assigned + $payloads_clicked_reported + $emp_compromised_reported;
return view('tprm-template', compact('Arraydetails', 'camp_live','ArrayData_labels','ArrayCount'));



}


public function whatsapp_campaigns_wise (Request $request)
{
 $campId = $request->query('campaignId');
// return  $campId;
    $companyId = Auth::user()->company_id;
//  $reportRow = WhatsAppCampaignUser::where('campaign_id', $campId)->where('company_id', $companyId)->first();
$TrainingreportRow = WhatsAppCampaignUser::where('camp_id', $campId)
    ->where('company_id', $companyId)
    ->sum('training_assigned');

// return $TrainingreportRow;

 $LinkreportRow = WhatsAppCampaignUser::where('camp_id', $campId)->where('company_id', $companyId)->sum('link_clicked');
 $EmpreportRow = WhatsAppCampaignUser::where('camp_id', $campId)->where('company_id', $companyId)->sum('emp_compromised');


// return $reportRow;
    $camp_live = WhatsAppCampaignUser::where('company_id', $companyId)->get();
//  return $reportRow->training_assigned;
$ArrayCount = [];
 $Arraydetails = [];
    $Arraydetails['link_clicked'] = $LinkreportRow ?? 0;
    $Arraydetails['emp_compromised'] =  $EmpreportRow ?? 0;
    $Arraydetails['training_assigned'] =  $TrainingreportRow ?? 0;

$ArrayCount['array_count'] = $TrainingreportRow + $LinkreportRow + $EmpreportRow;

$ArrayData_labels = [
    ["labels" => "Link Clicked"],
    ["labels" => "Emp Compromised"],
    ["labels" => "Training Assigned"],
];

return view('whatsapp-template', compact('Arraydetails', 'camp_live', 'ArrayData_labels', 'ArrayCount'));
}

public function whatsapp_full_report()
{
$companyId = Auth::user()->company_id;


$wtraining_assigned = DB::table('whatsapp_camp_users')->where('company_id', $companyId)->sum('training_assigned');
$link_clicked = DB::table('whatsapp_camp_users')->where('company_id', $companyId)->sum('link_clicked');
$emp_compromised = DB::table('whatsapp_camp_users')->where('company_id', $companyId)->sum('emp_compromised');
// return $link_clicked;
    $Arraydetails = [];
$ArrayCount = [];
    $Arraydetails['Link Clicked'] =  $link_clicked;
    $Arraydetails['
Emp Compromised'] = $emp_compromised;
    $Arraydetails['
Training_Assigned'] = $wtraining_assigned;
 $ArrayCount['array_count'] = $wtraining_assigned + $link_clicked + $emp_compromised;
    // return $Arraydetails;
$ArrayData_labels = [
    ["labels" => "Link Clicked"],
    ["labels" => "
Emp Compromised"],
    ["labels" => "
Training_Assigned"],
];

    $camp_live = WhatsAppCampaignUser::where('company_id', $companyId)->get();
// return $camp_live;
// return $camp_live;
return view('whatsapp-template', compact('Arraydetails', 'camp_live', 'ArrayData_labels', 'ArrayCount'));
}

public function  email_full_report() 
{
    $companyId = Auth::user()->company_id;
    $payload_clicked = CampaignLive::where('company_id', $companyId)->get()->sum('payload_clicked');
// return $emails_delivered;
    $training_assigned = CampaignLive::where('company_id', $companyId)->get()->sum('training_assigned');
    $emp_compromised = CampaignLive::where('company_id', $companyId)->get()->sum('emp_compromised');
    $Arraydetails = [];
$ArrayCount = [];
    $Arraydetails['Payload Clicked'] = $payload_clicked ?? 0;
    $Arraydetails['Emp Compromised'] =   $emp_compromised  ?? 0;
    $Arraydetails['Training Assigned'] = $training_assigned ?? 0;
    $ArrayCount['array_count'] = $training_assigned + $payload_clicked + $emp_compromised;
// return $Arraydetails;
    $ArrayData_labels = [
        ["labels" => "Payload Clicked"],
        ["labels" => "Emp Compromised"],
        ["labels" => "Training Assigned"],
    ];
    $camp_live = CampaignLive::where('company_id', $companyId)->get();

// return $camp_live;
    return view('email-template', compact('Arraydetails', 'camp_live', 'ArrayData_labels', 'ArrayCount'));
}

public function email_campaigns_wise (Request $request)
{
 $campId = $request->query('campaignId');
  $companyId = Auth::user()->company_id;

    $payload_clicked = CampaignLive::where('campaign_id', $campId)
    ->where('company_id', $companyId)->sum('payload_clicked');
// return $emails_delivered;
    $training_assigned = CampaignLive::where('campaign_id', $campId)
    ->where('company_id', $companyId)->sum('training_assigned');
    $emp_compromised = CampaignLive::where('campaign_id', $campId)
    ->where('company_id', $companyId)->sum('emp_compromised');
    $Arraydetails = [];
$ArrayCount = [];
    $Arraydetails['Payload Clicked'] = $payload_clicked ?? 0;
    $Arraydetails['Emp Compromised'] =   $emp_compromised  ?? 0;
    $Arraydetails['Training Assigned'] = $training_assigned ?? 0;
$ArrayCount['array_count'] = $training_assigned + $payload_clicked + $emp_compromised;
    $ArrayData_labels = [
        ["labels" => "Payload Clicked"],
        ["labels" => "Emp Compromised"],
        ["labels" => "Training Assigned"],
    ];
    $camp_live = CampaignLive::where('company_id', $companyId)->get();

// return $camp_live;
    return view('email-template', compact('Arraydetails', 'camp_live', 'ArrayData_labels', 'ArrayCount'));
}

}
