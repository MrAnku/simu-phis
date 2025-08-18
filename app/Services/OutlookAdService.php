<?php

namespace App\Services;

use App\Models\OutlookAdToken;
use Illuminate\Support\Facades\Http;

class OutlookAdService
{
    protected $companyId;

    //constructor
    public function __construct($companyId)
    {
        $this->companyId = $companyId;
        setCompanyTimezone($companyId);
        // Initialization code here
    }

    public static function authenticateUrl(): string
    {
        $authUrl = env('MS_AUTHORITY') . "authorize?" . http_build_query([
            "client_id" => env('MS_CLIENT_ID'),
            "response_type" => "code",
            "redirect_uri" => env('MS_REDIRECT_URI'),
            "response_mode" => "query",
            "scope" => "offline_access openid profile email User.Read Directory.Read.All",
            "state" => csrf_token() // Use CSRF token for security
        ]);

        return $authUrl;
    }

    public function hasToken(): bool
    {
        // Check if the token exists for the company
        $token = OutlookAdToken::where('company_id', $this->companyId)->first();
        return $token !== null;
    }

    public function isTokenValid(): bool
    {
        $token = OutlookAdToken::where('company_id', $this->companyId)->first();
        if (!$token) {
            return false;
        }
        return $token->expires_at > now();
    }

    public function getAccessToken($code)
    {
        try {
            $tokenUrl = env('MS_AUTHORITY') . "token";

            $response = Http::asForm()->post($tokenUrl, [
                "client_id" => env('MS_CLIENT_ID'),
                "client_secret" => env('MS_CLIENT_SECRET'),
                "code" => $code,
                "redirect_uri" => env('MS_REDIRECT_URI'),
                "grant_type" => "authorization_code",
            ]);

            $tokenData = $response->json();

            if (!isset($tokenData['access_token'])) {
                return false;
            }

            $accessToken  = $tokenData['access_token'];
            $refreshToken = $tokenData['refresh_token'] ?? null;
            $expiresIn    = $tokenData['expires_in'] ?? 3600; // default 1h
            $expiresAt    = now()->addSeconds($expiresIn);

            // Store the token in Laravel storage
            OutlookAdToken::updateOrCreate(
                ['company_id' => $this->companyId],
                [
                    'access_token'  => $accessToken,
                    'refresh_token' => $refreshToken,
                    'expires_at'    => $expiresAt,
                ]
            );
            return true;
        } catch (\Exception $e) {
            // Handle the exception, log it or return an error message
            return false;
        }
    }

    public function refreshAccessToken()
    {
        try {
            $token = OutlookAdToken::where('company_id', $this->companyId)->first();
            if (!$token) {
                return false;
            }
            $tokenUrl = env('MS_AUTHORITY') . "/token";
            $response = Http::asForm()->post($tokenUrl, [
                'client_id' => env('MS_CLIENT_ID'),
                'client_secret' => env('MS_CLIENT_SECRET'),
                'refresh_token' => $token->refresh_token,
                'grant_type' => 'refresh_token',
            ]);

            $tokenData = $response->json();

            if (isset($tokenData['access_token'])) {
                $token->update([
                    'access_token' => $tokenData['access_token'],
                    'expires_at' => now()->addSeconds($tokenData['expires_in']),
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            // Handle the exception, log it or return an error message
            return false;
        }
    }

    public function fetchGroups(): array
    {
        $token = OutlookAdToken::where('company_id', $this->companyId)->first();
        if (!$token) {
            return [];
        }

        $response = Http::withToken($token->access_token)->get(env('MS_GRAPH_API_URL') . "groups");
        if ($response->failed()) {
            return [];
        }

        return $response->json()['value'] ?? [];
    }

    public function fetchGroupMembers(string $groupId): array
    {
        $token = OutlookAdToken::where('company_id', $this->companyId)->first();
        if (!$token) {
            return [];
        }

        // Initialize variables for pagination
        $allUsers = [];
        $nextLink = env('MS_GRAPH_API_URL') . "groups/{$groupId}/members?\$top=999";

        // Fetch all users using pagination
        do {
            $response = Http::withToken($token->access_token)->get($nextLink);

            if ($response->failed()) {
                return [];
            }

            $users = $response->json();

            // Add users to the collection
            if (isset($users['value'])) {
                $allUsers = array_merge($allUsers, $users['value']);
            }

            // Check for next page
            $nextLink = $users['@odata.nextLink'] ?? null;
        } while ($nextLink);

        return $allUsers;
    }
}
