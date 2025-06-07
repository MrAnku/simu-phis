<?php

namespace App\Http\Controllers;

use App\Models\Users;
use Illuminate\Http\Request;
use App\Models\PhishTriageReportLog;
use Illuminate\Support\Facades\Auth;

class PhishTriageController extends Controller
{
    public function logReport(Request $request)
    {
        if ($request->header('X-SIMUPHISH') !== 'phishReportButton') {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $userEmail = $request->input('to')[0];
        $isEmployee = Users::where('user_email', $userEmail)->first();
        if (!$isEmployee) {
            $companyId = "unknown";
        }else{
            $companyId = $isEmployee->company_id;
        }
        PhishTriageReportLog::create([
            'user_email' => $request->input('to')[0],
            'reported_email' => $request->input('from'),
            'subject' => $request->input('subject'),
            'headers' => $request->input('headers'),
            'body' => $request->input('body'),
            'company_id' => $companyId
        ]);
        return response()->json(['message' => 'Report logged successfully'], 200);
    }

    public function emailsReported(Request $request)
    {
        $companyId = Auth::user()->company_id;
        if($request->query('email')){
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
