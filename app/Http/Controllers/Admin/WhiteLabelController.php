<?php

namespace App\Http\Controllers\Admin;

use App\Models\WhiteLabel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Mail\Admin\PartnerStopWhitelabelMail;
use App\Mail\Admin\PartnerRejectWhitelabelMail;
use App\Mail\Admin\PartnerApproveWhitelabelMail;
use Illuminate\Support\Facades\DB;

class WhiteLabelController extends Controller
{
    public function index()
    {
        $allpartners = WhiteLabel::all();

        return view('admin.whiteLabel', compact('allpartners'));
    }

    public function approveWhitelabel(Request $request)
    {
        $rowId = $request->input('rowId');

        DB::transaction(function () use ($rowId) {
            $whiteLabelPartner = WhiteLabel::findOrFail($rowId);
            $whiteLabelPartner->approved_by_admin = 1;
            $whiteLabelPartner->save();

            // Mail::to($whiteLabelPartner->partner_email)->send(new PartnerApproveWhitelabelMail($whiteLabelPartner));
            // $this->addDomain($whiteLabelPartner->domain);
            // $this->addLearnDomain($whiteLabelPartner->learn_domain);
        });

        return response()->json(['status' => 1, 'msg'=> 'Whitelabel request approved.']);
    }

    public function stopWhitelabel(Request $request)
    {
        $rowId = $request->input('rowId');

        DB::transaction(function () use ($rowId) {
            $whiteLabelPartner = WhiteLabel::findOrFail($rowId);
            $whiteLabelPartner->approved_by_admin = 0;
            $whiteLabelPartner->save();

            // Mail::to($whiteLabelPartner->partner_email)->send(new PartnerStopWhitelabelMail($whiteLabelPartner));
        });

        return response()->json(['status' => 1, 'msg'=> 'Whitelabelling is stoped for this partner.']);
    }

    public function rejectWhitelabel(Request $request)
    {
        $rowId = $request->input('rowId');

        DB::transaction(function () use ($rowId) {
            $whiteLabelPartner = WhiteLabel::findOrFail($rowId);
            // Mail::to($whiteLabelPartner->partner_email)->send(new PartnerRejectWhitelabelMail($whiteLabelPartner));
            $whiteLabelPartner->delete();
        });

        return response()->json(['status' => 1, 'msg'=> 'Whitelabel request rejected']);
    }

    private function addDomain($domain)
    {
        // Logic to add domain
    }

    private function addLearnDomain($learnDomain)
    {
        // Logic to add learn domain
    }
}
