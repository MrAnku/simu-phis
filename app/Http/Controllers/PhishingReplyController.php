<?php

namespace App\Http\Controllers;

use App\Models\CampaignLive;
use App\Models\PhishingReply;
use App\Models\QuishingLiveCamp;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PhishingReplyController extends Controller
{
    public function phishingReply(Request $request)
    {
        $data = $request->all();

        // Extract basic fields
        $fromAddress = $data['from'] ?? null;
        $subject = $data['subject'] ?? null;
        $headers = null;
        if (!empty($data['raw'])) {
            $lines = preg_split('/\r\n|\n|\r/', $data['raw']);
            $headers = $lines[0] ?? null;
        }

        $body = $data['raw'] ?? null;
        $replyMsg = null;

        if ($body) {
            $body = quoted_printable_decode($body);

            // Extract text/plain part from raw email
            if (preg_match('/Content-Type:\s*text\/plain.*?(\r\n\r\n|\n\n)(.*?)(--[^\r\n]*)/is', $body, $matches)) {
                $fullText = trim($matches[2]);
                // Split at reply marker
                $replyMsg = preg_split('/\r?\nOn /', $fullText)[0];
                $replyMsg = trim($replyMsg);
            } else {
                $replyMsg = strtok($body, "\n");
            }

            $replyMsg = mb_convert_encoding($replyMsg, 'UTF-8', 'UTF-8');
        }
        if ($headers) {
            $headers = mb_convert_encoding($headers, 'UTF-8', 'UTF-8');
        }

        // Extract campaign_id and campaign_type from HTML body
        $campaignId = null;
        $campaignType = null;
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($body);
        libxml_clear_errors();
        foreach ($dom->getElementsByTagName('input') as $input) {
            $id = $input->getAttribute('id');
            if (strpos($id, 'campaign_id') !== false) {
                $campaignId = $input->getAttribute('value');
            }
            if (strpos($id, 'campaign_type') !== false) {
                $campaignType = $input->getAttribute('value');
            }
        }

        // Validate user exists
        $user = Users::where('user_email', $fromAddress)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 422);
        }
        $companyId = $user->company_id;

        // Validate campaign_id exists in correct table
        if ($campaignType === 'email') {
            $campaignExists = CampaignLive::where('campaign_id', $campaignId)->exists();
        } else {
            $campaignExists = QuishingLiveCamp::where('campaign_id', $campaignId)->exists();
        }
        if (!$campaignExists) {
            return response()->json(['success' => false, 'message' => 'Campaign not found.'], 422);
        }

        // Save to DB
        PhishingReply::create([
            'from_address'   => $fromAddress,
            'subject'        => $subject,
            'headers'        => $headers,
            'body'           => $replyMsg,
            'campaign_id'    => $campaignId,
            'campaign_type'  => $campaignType,
            'company_id'     => $companyId,
        ]);

        return response()->json(['success' => true, 'message' => 'Phishing reply saved.'], 201);
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
