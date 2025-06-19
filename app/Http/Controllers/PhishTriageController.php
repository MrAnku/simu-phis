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
use Illuminate\Support\Facades\Cache;

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
                ->where('status', 'reported')
                ->get();
            return response()->json($logdata, 200);
        }
        $logdata = PhishTriageReportLog::where('company_id', $companyId)
            ->where('status', 'reported')->get();
        return response()->json($logdata, 200);
    }

    public function emailsResolved(Request $request)
    {
        $companyId = Auth::user()->company_id;
        if ($request->query('email')) {
            $email = $request->query('email');
            $logdata = PhishTriageReportLog::where('company_id', $companyId)
                ->where('user_email', $email)
                ->where('status', '!=', 'reported')
                ->get();
            return response()->json($logdata, 200);
        }
        $logdata = PhishTriageReportLog::where('company_id', $companyId)
            ->where('status', '!=', 'reported')->get();
        return response()->json($logdata, 200);
    }

    public function aiAnalysis(Request $request)
    {
        $id = base64_decode($request->query('encoded_id'));
        $phishTriageReportLog = PhishTriageReportLog::find($id);

        if ($phishTriageReportLog->ai_analysis !== null) {
            return response()->json([
                'success' => true,
                'data' => json_decode($phishTriageReportLog->ai_analysis),
                'message' => 'AI analysis already completed for this log.'
            ], 200);
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
            $response = Http::withToken(env('OPENAI_API_KEY'))->withoutVerifying()->post('https://api.openai.com/v1/chat/completions', [
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

    public function domainAnalysis(Request $request)
    {
        $domain = $request->route('domain');
        if (!$domain) {
            return response()->json(['error' => 'Domain is required'], 400);
        }

        // SPF Check
        $spfRecord = dns_get_record($domain, DNS_TXT);
        $spfStatus = 'NOT FOUND';
        foreach ($spfRecord as $record) {
            if (isset($record['txt']) && stripos($record['txt'], 'v=spf1') === 0) {
                $spfStatus = 'FOUND';
                break;
            }
        }

        // DKIM Check (look for default._domainkey)
        $dkimSelector = 'default._domainkey.' . $domain;
        $dkimRecord = dns_get_record($dkimSelector, DNS_TXT);
        $dkimStatus = 'NOT FOUND';
        foreach ($dkimRecord as $record) {
            if (isset($record['txt']) && stripos($record['txt'], 'v=DKIM1') === 0) {
                $dkimStatus = 'FOUND';
                break;
            }
        }

        // DMARC Check (_dmarc)
        $dmarcSelector = '_dmarc.' . $domain;
        $dmarcRecord = dns_get_record($dmarcSelector, DNS_TXT);
        $dmarcStatus = 'NOT FOUND';
        $dmarcPolicy = null;
        foreach ($dmarcRecord as $record) {
            if (isset($record['txt']) && stripos($record['txt'], 'v=DMARC1') === 0) {
                $dmarcStatus = 'FOUND';
                if (preg_match('/p=([a-zA-Z]+)/', $record['txt'], $matches)) {
                    $dmarcPolicy = strtoupper($matches[1]);
                }
                break;
            }
        }

        // Map to more descriptive statuses
        $spfResult = $spfStatus === 'FOUND' ? 'PASS' : 'FAIL';
        $dkimResult = $dkimStatus === 'FOUND' ? 'PASS' : 'FAIL';
        $dmarcResult = 'NOT FOUND';
        if ($dmarcStatus === 'FOUND') {
            if ($dmarcPolicy === 'NONE') {
                $dmarcResult = 'NONE';
            } elseif ($dmarcPolicy === 'QUARANTINE') {
                $dmarcResult = 'QUARANTINE';
            } elseif ($dmarcPolicy === 'REJECT') {
                $dmarcResult = 'REJECT';
            } else {
                $dmarcResult = 'UNKNOWN';
            }
        }

        $result = [
            'SPF' => $spfResult,
            'DKIM' => $dkimResult,
            'DMARC' => $dmarcResult
        ];

        return response()->json([
            'domain' => $domain,
            'analysis' => $result
        ], 200);
    }


    //phish triage actions

    public function redirect()
    {
        $cache = Cache::get("outlook_token_" . Auth::user()->company_id);
        if ($cache) {
            return response()->json(['auth_url' => null], 200);
        }
        $params = [
            'client_id' => env('PT_MS_CLIENT_ID'),
            'response_type' => 'code',
            'redirect_uri' => env('PT_MS_REDIRECT_URI'),
            'response_mode' => 'query',
            'scope' => 'offline_access Mail.ReadWrite Mail.Send MailboxSettings.Read User.Read',
        ];

        $url = "https://login.microsoftonline.com/" . env('PT_MS_TENANT_ID') . "/oauth2/v2.0/authorize?" . http_build_query($params);
        return response()->json(['auth_url' => $url]);
    }

    public function callback(Request $request)
    {
        $code = $request->code;
        $companyId = Auth::user()->company_id; // Pass user ID to associate tokens

        $response = Http::asForm()->post("https://login.microsoftonline.com/" . env('PT_MS_TENANT_ID') . "/oauth2/v2.0/token", [
            'client_id' => env('PT_MS_CLIENT_ID'),
            'scope' => 'offline_access Mail.ReadWrite Mail.Send MailboxSettings.Read User.Read',
            'code' => $code,
            'redirect_uri' => env('PT_MS_REDIRECT_URI'),
            'grant_type' => 'authorization_code',
            'client_secret' => env('PT_MS_CLIENT_SECRET'),
        ]);

        $data = $response->json();

        // Save access token for user (use DB in production)
        Cache::put("outlook_token_$companyId", $data['access_token'], now()->addMinutes(55));
        Cache::put("outlook_refresh_$companyId", $data['refresh_token'], now()->addDays(30));

        return response()->json(['success' => true, 'message' => 'Token stored']);
    }

    private function getToken()
    {
        $companyId = Auth::user()->company_id;
        return Cache::get("outlook_token_$companyId");
    }

    public function listEmails(Request $request)
    {
        $token = $this->getToken();

        $response = Http::withToken($token)->get('https://graph.microsoft.com/v1.0/me/messages?$top=10');

        return response()->json($response->json());
    }

    public function performAction(Request $request)
    {
        $action = $request->action;
        $id = $request->id;

        \Log::info('Performing action', ['action' => $action, 'id' => $id]);

        $reportedEmail = PhishTriageReportLog::find($id);
        if (!$reportedEmail) {
            \Log::error('Report not found', ['id' => $id]);
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }

        $headerString = $reportedEmail->headers;
        \Log::info('Email headers', ['headers' => $headerString]);

        // Extract header Message-Id
        preg_match('/Message-ID:\s*<([^>]+)>/i', $headerString, $matches);
        if (empty($matches[1])) {
            \Log::error('Message ID not found in headers', ['headers' => $headerString]);
            return response()->json(['success' => false, 'message' => 'Message ID not found in headers'], 404);
        }
        $internetMessageId = "<{$matches[1]}>"; // Ensure angle brackets
        \Log::info('Internet Message ID', ['internetMessageId' => $internetMessageId]);

        $token = $this->getToken();
        \Log::info('Token', ['token' => $token ?: 'null']);

        if (!$token) {
            \Log::error('Token not retrieved');
            return response()->json(['success' => false, 'message' => 'Authentication failed'], 401);
        }

        // Find Graph API message ID
        try {
            $response = Http::withToken($token)->get("https://graph.microsoft.com/v1.0/me/messages", [
                '$filter' => "internetMessageId eq '$internetMessageId'"
            ])->throw();
            $graphMessage = $response->json()['value'][0] ?? null;
            if (!$graphMessage) {
                \Log::error('Graph message not found', ['internetMessageId' => $internetMessageId]);
                return response()->json(['success' => false, 'message' => 'Graph message not found'], 404);
            }
            $messageId = $graphMessage['id'];
            \Log::info('Graph Message ID', ['messageId' => $messageId]);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch Graph message', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to fetch message: ' . $e->getMessage()], 500);
        }

        try {
            switch ($action) {
                case 'mark_safe':
                    $response = Http::withToken($token)->post("https://graph.microsoft.com/v1.0/me/messages/$messageId/move", [
                        'destinationId' => 'inbox'
                    ])->throw();
                    \Log::info('Move to inbox response', ['status' => $response->status(), 'body' => $response->json()]);
                    $reportedEmail->update(['status' => 'safe']);
                    break;

                case 'move_spam':
                    $response = Http::withToken($token)->post("https://graph.microsoft.com/v1.0/me/messages/$messageId/move", [
                        'destinationId' => 'junkemail'
                    ])->throw();
                    \Log::info('Move to spam response', ['status' => $response->status(), 'body' => $response->json()]);
                    $reportedEmail->update(['status' => 'spam']);
                    break;

                case 'block_delete':

                    $response = Http::withToken($token)->post("https://graph.microsoft.com/v1.0/me/messages/$messageId/move", [
                        'destinationId' => 'deleteditems'
                    ])->throw();
                    \Log::info('Move to deleted response', ['status' => $response->status(), 'body' => $response->json()]);
                    $reportedEmail->update(['status' => 'blocked']);
                    break;


                default:
                    \Log::error('Invalid action', ['action' => $action]);
                    return response()->json(['success' => false, 'message' => 'Invalid action'], 400);
            }
        } catch (\Exception $e) {
            \Log::error('API or database error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Action failed: ' . $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'message' => 'Action performed successfully']);
    }

    public function findMessageId(Request $request)
    {

        $headerString = $request->header;
        preg_match('/Message-Id:\s*<([^>]+)>/i', $headerString, $matches);
        $messageId = $matches[1] ?? null;

        return response()->json(['message_id' => $messageId]);
    }
}
