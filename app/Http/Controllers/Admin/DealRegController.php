<?php

namespace App\Http\Controllers\Admin;

use App\Models\Company;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\Admin\DealReject;
use App\Mail\Admin\DealApprove;
use App\Models\CompanySettings;
use App\Models\DealRegistration;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class DealRegController extends Controller
{
    public function index()
    {
        $registrations = DealRegistration::all();

        return view('admin.deal_reg', compact('registrations'));
    }

    public function approveDeal(Request $request)
    {
        $deal = DealRegistration::find($request->id);

        if (!$deal) {
            return response()->json(['status' => 0, 'msg' => 'Deal not found']);
        }

        $company = Company::where('email', $deal->email)->first();

        if ($company) {
            return response()->json(['status' => 0, 'msg' => 'Company already registered with this email']);
        }

        $company_id = sprintf(
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

        $password = Str::random(16);

        $company = Company::create([
            'email' => $deal->email,
            'full_name' => $deal->first_name . ' ' . $deal->last_name,
            'company_name' => $deal->company,
            'company_id' => $company_id,
            'partner_id' => $deal->partner_id,
            'employees' => 100,
            'storage_region' => 'USA',
            'approved' => 1,
            'service_status' => 1,
            'password' => bcrypt($password),
            'created_at' => now(),
            'approve_date' => now(),
        ]);

        CompanySettings::create([
            'company_id' => $company_id,
            'country' => 'IN',
            'time_zone' => 'Pacific/Midway',
            'date_format' => 'dd/MM/yyyy',
            'mfa' => '0',
            'mfa_secret' => '',
            'default_phishing_email_lang' => 'en',
            'default_training_lang' => 'en',
            'default_notifications_lang' => 'en',
            'phish_redirect' => 'simuEducation',
            'phish_redirect_url' => '',
            'phish_reporting' => '0',
            'training_assign_remind_freq_days' => '1',
        ]);
        $deal->status = 'approved';
        $deal->save();

        Mail::to($deal->email)->send(new DealApprove($deal, $password));

        return response()->json(['status' => 1, 'msg' => 'Deal approved successfully']);
    }

    public function rejectDeal(Request $request)
    {
        $deal = DealRegistration::find($request->id);
        $deal->status = 'rejected';
        $deal->save();

        Mail::to($deal->partner->email)->send(new DealReject($deal));

        return response()->json(['status' => 1, 'msg' => 'Deal rejected successfully']);
    }
}
