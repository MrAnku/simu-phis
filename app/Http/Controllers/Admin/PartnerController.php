<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\Admin\PartnerApprove;
use App\Mail\Admin\PartnerCreated;
use App\Mail\Admin\PartnerHold;
use App\Mail\Admin\PartnerReject;
use App\Mail\Admin\PartnerResume;
use App\Models\AdminNoticeToPartner;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PartnerController extends Controller
{
    //
    public function index(){
        $all_partners = Partner::with('notices')->get();

        $notices = AdminNoticeToPartner::with('partner')->get();

        return view('admin.partners', compact('all_partners', 'notices'));
        // return $notices;
    }

    // Check if email exists
    private function emailExists($email)
    {
        return Partner::where('email', $email)->exists();
    }

    public function createPartner(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'full_name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'service_status' => 'required|integer',
            'add_info' => 'nullable|string',
        ]);

        $email = $request->input('email');
        $full_name = $request->input('full_name');
        $company_name = $request->input('company_name');
        $service_status = $request->input('service_status');
        $add_info = $request->input('add_info');

        if (!$this->emailExists($email)) {
            $pass = generateRandom(16);
            $partner_id = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff)
            );

            $partner = new Partner();
            $partner->partner_id = $partner_id;
            $partner->full_name = $full_name;
            $partner->email = $email;
            $partner->company = $company_name;
            $partner->additional_info = $add_info;
            $partner->approved = 1;
            $partner->service_status = $service_status;
            $partner->username = $email;
            $partner->password = $pass; //bcrypt($pass);
            $partner->save();

            if ($service_status == 1) {
                // $this->sendPartnerCreateMail($email, $email, $pass);
                Mail::to($email)->send(new PartnerCreated($email, $email, $pass));
            }

            // return response()->json(['status' => 1, 'message' => 'Partner has been added successfully.']);
            return redirect()->back()->with(['success' => 'Partner has been added successfully.']);
        } else {
            return redirect()->back()->with(['error' => 'Entered email already exists!']);
            // return response()->json(['status' => 0, 'message' => 'Entered email already exists!']);
        }
    }

    // Approve Partner
    public function approvePartner(Request $request)
    {
        $partnerId = $request->input('partnerId');
        $pass = generateRandom(16);

        $partner = Partner::findOrFail($partnerId);
        $partner->approved = 1;
        $partner->service_status = 1;
        $partner->password = $pass; //bcrypt($pass);
        $partner->save();

        // $this->sendPartnerApproveConfirmMail($partner->email, $partner->username, $pass);
        Mail::to($partner->email)->send(new PartnerApprove($partner->email, $partner->username, $pass));

        return response()->json(['status' => 1, 'msg' => 'Partner approved and notified.']);
    }

    // Hold Service
    public function holdService(Request $request)
    {
        $partnerId = $request->input('partnerId');

        $partner = Partner::findOrFail($partnerId);
        $partner->service_status = 0;
        $partner->save();

        // $this->sendPartnerHoldMail($partner->email);
        Mail::to($partner->email)->send(new PartnerHold($partner->email));

        return response()->json(['status' => 1, 'msg' => 'Service held and partner notified.']);
    }

    // Start Service
    public function startService(Request $request)
    {
        $partnerId = $request->input('partnerId');

        $partner = Partner::findOrFail($partnerId);
        $partner->service_status = 1;
        $partner->save();

        // $this->sendPartnerServiceStartMail($partner->email);
        Mail::to($partner->email)->send(new PartnerResume($partner->email));

        return response()->json(['status' => 1, 'msg' => 'Service started and partner notified.']);
    }

    // Reject Approval
    public function rejectApproval(Request $request)
    {
        $partnerId = $request->input('partnerId');

        $partner = Partner::findOrFail($partnerId);
        // $this->sendPartnerRejectionMail($partner->email);
        Mail::to($partner->email)->send(new PartnerReject($partner->email));
        $partner->delete();

        return response()->json(['status' => 1, 'msg' => 'Partner approval rejected and record deleted.']);
    }

    // Delete Partner
    public function deletePartner(Request $request)
    {
        $partnerId = $request->input('partnerId');

        $partner = Partner::findOrFail($partnerId);
        $partner->delete();

        return response()->json(['status' => 1, 'msg' => 'Partner deleted.']);
    }

    public function deleteNotice(Request $request){

        $request->validate([
            'noticeid' => 'required|integer'
        ]);

        $notice = AdminNoticeToPartner::find($request->noticeid);

        if($notice){
            $notice->delete();
            return response()->json(['status' => 1, 'msg' => 'Notice Deleted Successfully']);
        }

        return response()->json(['status' => 0, 'msg' => 'Something went wrong']);
    }
    public function addNotice(Request $request)
    {
        $request->validate([
            'partner' => 'required|exists:partners,partner_id',
            'title' => 'required|string|max:255',
            'msg' => 'required|string',
        ]);

        $notice = new AdminNoticeToPartner();
        $notice->partner_id = $request->input('partner');
        $notice->notice_title = $request->input('title');
        $notice->notice_msg = $request->input('msg');
        $notice->date = date('Y-m-d');

        $notice->save();

        return redirect()->back()->with('success', 'Notice added successfully');
    }

    
}
