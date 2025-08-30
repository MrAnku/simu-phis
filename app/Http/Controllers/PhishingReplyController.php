<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\Company;
use App\Models\CampaignLive;
use App\Models\CompanyLicense;
use App\Models\CompanySettings;
use Illuminate\Http\Request;
use App\Models\PhishingReply;
use App\Models\QuishingLiveCamp;
use Illuminate\Validation\ValidationException;

class PhishingReplyController extends Controller
{
    public $campaignId;
    public $campaignType;

    public function phishingReply(Request $request)
    {
        $data = $request->all();

        $fromAddress = $data['from'] ?? null;
        $toAddress = $data['to'] ?? null;
        $subject = $data['subject'] ?? null;
        $lastMsg = $data['lastMsg'] ?? null;
        $originalMessage = $data['originalMessage'] ?? null;

        \Log::info('request received');

        $user = Users::where('user_email', $fromAddress)->first();
        if (!$user) {
            \Log::info('User not found in platform');
            return response()->json(['success' => false, 'message' => 'User not found in platform'], 422);
        }
        $companyId = $user->company_id;

        if(!$this->isValidCompany($companyId)) {
            \Log::info('Company is not valid');
            return response()->json(['success' => false, 'message' => 'Company is not valid'], 423);
        }

        if(!$this->hasCampaignIdAndCampaignType($originalMessage)) {
            \Log::info('Invalid campaign ID or message');
            return response()->json(['success' => false, 'message' => 'Invalid campaign ID or message'], 424);
        }

        if(!$this->isValidCampaignIdAndType()) {
            \Log::info('Invalid campaign ID or type');
            return response()->json(['success' => false, 'message' => 'Invalid campaign ID or type'], 425);
        }

         PhishingReply::create([
            'from_address'   => $fromAddress,
            'subject'        => $subject,
            'headers'        => "This is the header",
            'body'           => $lastMsg,
            'campaign_id'    => $this->campaignId,
            'campaign_type'  => $this->campaignType,
            'company_id'     => $companyId,
        ]);
        \Log::info('Phishing reply saved.');

        return response()->json(['success' => true, 'message' => 'Phishing reply saved.'], 201);

        // // Extract basic fields
        // // $fromAddress = $data['from'] ?? null;
        // // $subject = $data['subject'] ?? null;
        // // $headers = null;
        // // if (!empty($data['raw'])) {
        // //     $lines = preg_split('/\r\n|\n|\r/', $data['raw']);
        // //     $headers = $lines[0] ?? null;
        // // }

        // // $body = $data['raw'] ?? null;
        // // $replyMsg = null;

        // // if ($body) {
        // //     $body = quoted_printable_decode($body);

        // //     // Extract text/plain part from raw email
        // //     if (preg_match('/Content-Type:\s*text\/plain.*?(\r\n\r\n|\n\n)(.*?)(--[^\r\n]*)/is', $body, $matches)) {
        // //         $fullText = trim($matches[2]);
        // //         // Split at reply marker
        // //         $replyMsg = preg_split('/\r?\nOn /', $fullText)[0];
        // //         $replyMsg = trim($replyMsg);
        // //     } else {
        // //         $replyMsg = strtok($body, "\n");
        // //     }

        // //     $replyMsg = mb_convert_encoding($replyMsg, 'UTF-8', 'UTF-8');
        // // }
        // // if ($headers) {
        // //     $headers = mb_convert_encoding($headers, 'UTF-8', 'UTF-8');
        // // }

        // // // Extract campaign_id and campaign_type from HTML body
        // // $campaignId = null;
        // // $campaignType = null;
        // // libxml_use_internal_errors(true);
        // // $dom = new \DOMDocument();
        // // $dom->loadHTML($body);
        // // libxml_clear_errors();
        // // foreach ($dom->getElementsByTagName('input') as $input) {
        // //     $id = $input->getAttribute('id');
        // //     if (strpos($id, 'campaign_id') !== false) {
        // //         $campaignId = $input->getAttribute('value');
        // //     }
        // //     if (strpos($id, 'campaign_type') !== false) {
        // //         $campaignType = $input->getAttribute('value');
        // //     }
        // // }

        // // Validate user exists
        // $user = Users::where('user_email', $fromAddress)->first();
        // if (!$user) {
        //     return response()->json(['success' => false, 'message' => 'User not found.'], 422);
        // }
        // $companyId = $user->company_id;

        // // Validate campaign_id exists in correct table
        // if ($campaignType === 'email') {
        //     $campaignExists = CampaignLive::where('campaign_id', $campaignId)->exists();
        // } else {
        //     $campaignExists = QuishingLiveCamp::where('campaign_id', $campaignId)->exists();
        // }
        // if (!$campaignExists) {
        //     return response()->json(['success' => false, 'message' => 'Campaign not found.'], 422);
        // }

        // // Save to DB
        // PhishingReply::create([
        //     'from_address'   => $fromAddress,
        //     'subject'        => $subject,
        //     'headers'        => $headers,
        //     'body'           => $replyMsg,
        //     'campaign_id'    => $campaignId,
        //     'campaign_type'  => $campaignType,
        //     'company_id'     => $companyId,
        // ]);

        // return response()->json(['success' => true, 'message' => 'Phishing reply saved.'], 201);
    }

    private function hasCampaignIdAndCampaignType($originalMessage): bool
    {
        if (!$originalMessage) {
            return false;
        }
        
        // Decode the message if it's URL/quoted-printable encoded
        $decodedMessage = quoted_printable_decode($originalMessage);
        
        $campaignId = null;
        $campaignType = null;
        
        // Try to extract from decoded message first
        if (preg_match('/id="[^"]*campaign_id"[^>]*value="([^"]+)"/', $decodedMessage, $idMatch)) {
            $campaignId = $idMatch[1];
        }
        if (preg_match('/id="[^"]*campaign_type"[^>]*value="([^"]+)"/', $decodedMessage, $typeMatch)) {
            $campaignType = $typeMatch[1];
        }
        
        // If not found in decoded message, try original message
        if (!$campaignId || !$campaignType) {
            if (preg_match('/id="[^"]*campaign_id"[^>]*value="([^"]+)"/', $originalMessage, $idMatch)) {
                $campaignId = $idMatch[1];
            }
            if (preg_match('/id="[^"]*campaign_type"[^>]*value="([^"]+)"/', $originalMessage, $typeMatch)) {
                $campaignType = $typeMatch[1];
            }
        }
        
        if ($campaignId && $campaignType) {
            $this->campaignId = $campaignId;
            $this->campaignType = $campaignType;
            return true;
        }
        
        return false;
    }

    private function isValidCampaignIdAndType(): bool
    {
        if($this->campaignType === 'email') {
            return CampaignLive::where('campaign_id', $this->campaignId)->exists();
        } elseif($this->campaignType === 'quishing') {
            return QuishingLiveCamp::where('campaign_id', $this->campaignId)->exists();
        }
        return false;
    }

    private function isValidCompany($companyId): bool
    {
        $validCompany = false;
        // Check if the company is on hold
        $onHold = Company::where('company_id', $companyId)->where('approved', 1)->where('service_status', 1)->exists();
        $isLicenseValid = CompanyLicense::where('company_id', $companyId)->where('expiry', '>', now())->exists();
        if ($onHold) {
            $validCompany = true;
        }
        if ($isLicenseValid) {
            $validCompany = true;
        }
        return $validCompany;
    }

    public function fetchPhishingReplies(Request $request)
    {
        try {
            $campaignId = $request->input('campaign_id');
            $campaignType = $request->input('campaign_type');

            if (!$campaignId || !$campaignType) {
                return response()->json([
                    'success' => false,
                    'message' => 'campaign_id and campaign_type are required.'
                ], 422);
            }

            $replies = PhishingReply::where('campaign_id', $campaignId)
                ->where('campaign_type', $campaignType)
                ->select('body', 'from_address', 'created_at')
                ->get()
                ->map(function ($item) {
                    return [
                        'reply' => $item->body,
                        'from_address' => $item->from_address,
                        'created_at' => $item->created_at
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $replies,
                'message' => 'Phishing replies fetched successfully.'
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
