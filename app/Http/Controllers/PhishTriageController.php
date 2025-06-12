<?php

namespace App\Http\Controllers;

use App\Mail\PhishTriageReportMail;
use App\Models\Company;
use App\Models\DomainVerified;
use App\Models\Users;
use Illuminate\Http\Request;
use App\Models\PhishTriageReportLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class PhishTriageController extends Controller
{
    public function logReport(Request $request)
    {
        // if ($request->header('X-SIMUPHISH') !== 'phishReportButton') {
        //     return response()->json(['message' => 'Unauthorized'], 401);
        // }
        $userEmail = $request->input('to')[0];
        //convert to lowercase
        $userEmail = strtolower($userEmail);

        //extract the domain from the email
        $domain = substr(strrchr($userEmail, "@"), 1);

        //check the domain exists in verified domains
        $domainExists = DomainVerified::where('domain', $domain)->first();
        $isEmployee = Users::where('user_email', $userEmail)->first();
        if ($domainExists) {
            $companyId = $domainExists->company_id;
        } else if ($isEmployee) {
            $companyId = $isEmployee->company_id;
        } else {
            $companyId = "unknown";
        }
        // $isEmployee = Users::where('user_email', $userEmail)->first();
        // if (!$isEmployee) {
        //     $companyId = "unknown";
        // }else{
        //     $companyId = $isEmployee->company_id;
        // }
        $PhishTriageReportLog = PhishTriageReportLog::create([
            'user_email' => $request->input('to')[0],
            'reported_email' => $request->input('from'),
            'subject' => $request->input('subject'),
            'headers' => $request->input('headers'),
            'body' => $request->input('body'),
            'company_id' => $companyId
        ]);

        $company = Company::where('company_id', $companyId)->first();

        $mailData = [
            'reported_by' => $request->input('to')[0],
            'from' => $request->input('from'),
            'subject' => $request->input('subject'),
            'company_name' => $company->company_name,
            'reported_at' => $PhishTriageReportLog->created_at,
        ];

        Mail::to($company->email)->send(new PhishTriageReportMail($mailData));

        return response()->json(['message' => 'Report logged successfully'], 200);
    }

    public function emailsReported(Request $request)
    {
        $companyId = Auth::user()->company_id;
        if ($request->query('email')) {
            $email = $request->query('email');
            $logdata = PhishTriageReportLog::where('company_id', $companyId)
                ->where('user_email', $email)
                ->get();
            return response()->json($logdata, 200);
        }
        $logdata = PhishTriageReportLog::where('company_id', $companyId)->get();
        return response()->json($logdata, 200);
    }
}
