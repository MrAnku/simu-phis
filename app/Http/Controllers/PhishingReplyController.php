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

        if (!$this->isValidCompany($companyId)) {
            \Log::info('Company is not valid');
            return response()->json(['success' => false, 'message' => 'Company is not valid'], 423);
        }

        if (!$this->isPhishReplyEnabled($companyId)) {
            \Log::info('Phishing reply is not enabled for this company');
            return response()->json(['success' => false, 'message' => 'Phishing reply is not enabled for this company'], 423);
        }

        if (!$this->hasCampaignIdAndCampaignType($originalMessage)) {
            \Log::info('Invalid campaign ID or message');
            return response()->json(['success' => false, 'message' => 'Invalid campaign ID or message'], 424);
        }

        if (!$this->isValidCampaignIdAndType()) {
            \Log::info('Invalid campaign ID or type');
            return response()->json(['success' => false, 'message' => 'Invalid campaign ID or type'], 425);
        }

        // filter the last message text from the start 
        $lastMsg = $this->extractReplyText($lastMsg);

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

       
    }
    private function extractReplyText($message)
    {
        // Remove quoted lines (multiple formats)
        $patterns = [
            '/^(>.*(\r\n|\n|\r))+/m',           // > quoted lines
            '/^On .* wrote:.*$/m',              // "On date, person wrote:"
            '/-----Original Message-----.*$/s', // Outlook format
            '/________________________________.*$/s', // Outlook separator
        ];

        foreach ($patterns as $pattern) {
            $message = preg_replace($pattern, '', $message);
        }

        // Remove signatures (common patterns)
        $message = preg_replace('/\n--\s*\n.*$/s', '', $message);

        return trim($message);
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
        if ($this->campaignType === 'email') {
            return CampaignLive::where('campaign_id', $this->campaignId)->exists();
        } elseif ($this->campaignType === 'quishing') {
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

    private function isPhishReplyEnabled($companyId): bool
    {
        $phishReplyEnabled = false;
        // Check if the Phishing Reply feature is enabled
        $isPhishReplyEnabled = CompanySettings::where('company_id', $companyId)->where('phish_reply', true)->exists();

        if ($isPhishReplyEnabled) {
            $phishReplyEnabled = true;
        }
        return $phishReplyEnabled;
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
