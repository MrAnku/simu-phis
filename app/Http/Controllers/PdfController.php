<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Barryvdh\DomPDF\Facade\Pdf;  // Ensure this is properly imported
use Illuminate\Http\Request;
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


}
