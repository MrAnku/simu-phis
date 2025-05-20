<?php

namespace App\Http\Controllers\Admin;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Mail\Admin\MailAfterCompanyApproval;
use Illuminate\Support\Facades\Mail;

class CompanyController extends Controller
{
    public function index(){
        $all_companies = Company::with('partner')->get();

        //  return $all_companies;

        return view('admin.companies', compact('all_companies'));
    }

    public function approveCompany(Request $request)
    {
        $companyId = $request->input('companyId');
        $today = now();

        $company = Company::findOrFail($companyId);
        $company->approved = 1;
        $company->service_status = 1;
        $company->approve_date = $today;
        $company->save();

        if($company->after_approval_mail !== null){
            $issent = Mail::to($company->email)->send(new MailAfterCompanyApproval($company->after_approval_mail));
        }

        // Optionally, send an email or perform other actions

        return response()->json(['status' => 1, 'msg' => 'Company approved.']);
    }

    public function rejectApproval(Request $request)
    {
        $companyId = $request->input('companyId');
        
        $company = Company::findOrFail($companyId);
        $company->delete();

        // Optionally, send an email or perform other actions

        return response()->json(['status' => 1, 'msg' => 'Company rejected and deleted.']);
    }

    public function deleteCompany(Request $request)
    {
        $companyId = $request->input('companyId');

        $company = Company::findOrFail($companyId);
        $company->delete();

        return response()->json(['status' => 1, 'msg' => 'Company deleted.']);
    }
}
