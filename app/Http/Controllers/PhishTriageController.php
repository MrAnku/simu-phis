<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\Company;
use Illuminate\Http\Request;
use App\Models\DomainVerified;
use App\Mail\PhishTriageReportMail;
use App\Models\PhishTriageReportLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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

    public function aiAnalysis(Request $request)
    {
        $id = base64_decode($request->query('encoded_id'));
        $phishTriageReportLog = PhishTriageReportLog::find($id);

        if ($phishTriageReportLog->ai_analysis !== null) {
            return response()->json([
                'success' => false,
                'data' => json_decode($phishTriageReportLog->ai_analysis),
                'message' => 'AI analysis already completed for this log.'
            ], 422);
        }

        try {
            $headers = $phishTriageReportLog->headers;
            $body = $phishTriageReportLog->body;

            // Compose the prompt
            $prompt = <<<EOT
                 You are a cybersecurity expert specializing in email threat detection. You will be provided with the raw email headers and the email body content. Analyze the content for any signs of phishing, fraud, or malicious behavior.

                Return the output strictly in plain JSON format only (do not use markdown or triple backticks). Use this exact format:

                {
                "Risk Indicators Found": [
                "First risk indicator",
                "Second risk indicator",
                "More if applicable"
                ]
                }

                If there are no suspicious indicators, return:

                {
                "Risk Indicators Found": [
                "No suspicious indicators found"
                ]
                }

                Email Headers:
                $headers

                Email Body:
                $body
                EOT;



            // Call OpenAI API
            $response = Http::withToken(env('OPENAI_API_KEY'))->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.2,
            ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content') ?? null;

                if ($content) {
                    $decoded = json_decode($content, true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        // Save to DB
                        $phishTriageReportLog->update([
                            'ai_analysis' => json_encode($decoded),
                        ]);

                        return response()->json([
                            'success' => true,
                            'data' => $decoded,
                            'message' => 'AI analysis completed successfully.',
                        ], 200);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid JSON format returned from AI.',
                            'raw' => $content
                        ], 422);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Empty AI response for this log.'
                    ], 422);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "OpenAI API error: " . $response->body()
                ], 422);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => "Exception during AI analysis: " . $e->getMessage()
            ], 500);
        }
    }
}
