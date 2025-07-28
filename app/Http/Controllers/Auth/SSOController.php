<?php

namespace App\Http\Controllers\Auth;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Models\CompanyLicense;
use App\Http\Controllers\Controller;
use App\Models\CompanySettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class SSOController extends Controller
{
    public function ssoValidate(Request $request)
    {
        // Validate the SSO request
        $accessToken = $request->query('access_token');
        $provider = $request->query('provider');

        if ($provider == 'google') {
            return $this->validateToken($accessToken, 'google');
        }

        if ($provider == 'outlook') {
            return $this->validateToken($accessToken, 'outlook');
        }
        return response()->json([
            'success' => false,
            'message' => 'Unsupported provider'
        ], 400);
    }

    private function validateToken($accessToken, $provider)
    {
        // Validate the access token with Google
        if ($provider == 'google') {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get('https://www.googleapis.com/oauth2/v3/userinfo');
        }

        // Validate the access token with Outlook
        if ($provider == 'outlook') {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get('https://graph.microsoft.com/v1.0/me');
        }

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid access token'
            ], 401);
        }

        $payload = $response->json();


        if($provider == 'google') {
            $email = $payload['email'] ?? null;
        } elseif ($provider == 'outlook') {
            $email = $payload['mail'] ?? null; // Outlook uses 'mail' for email
        }

        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => 'Email not found in token'
            ], 400);
        }

        // Check if this email exists in company table
        $company = Company::where('email', $email)->first();
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found'
            ], 404);
        }

        Auth::login($company);
        $token = JWTAuth::fromUser($company);

        // Check if the user is approved
        if (!$this->isApproved()) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Your account is not approved yet. Please contact your service provider.',
            ], 422);
        }

        // Check if the service status is approved
        if (!$this->isServiceStatusApproved()) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Your services are on hold. Please contact your service provider.',
            ], 422);
        }

        // Check License Expiry
        if ($this->checkLicenseExpiry()) {
            return response()->json([
                'success' => false,
                'message' => 'Your License has been Expired'
            ], 422);
        }

        // Check MFA
        if ($this->checkMfa()) {
            Auth::logout(Auth::user());
            return response()->json([
                "mfa" => true,
                'company' => Auth::user(),
                "success" => true
            ]);
        }

        // Get enabled features
        $enabledFeatures = Company::where('company_id', $company->company_id)->value('enabled_feature') ?? 'null';

        $cookie = cookie('jwt', $token, env('JWT_TTL', 1440));
        $enabledFeatureCookie = cookie('enabled_feature', $enabledFeatures, env('JWT_TTL', 1440));

        return response()->json([
            'token' => $token,
            'success' => true,
            'company' => $company,
            'message' => 'Logged in successfully',
        ])->withCookie($cookie)->withCookie($enabledFeatureCookie);
    }

    private function checkMfa()
    {
        $user = Auth::user();
        $company_settings = CompanySettings::where('email', $user->email)->first();
        if ($company_settings->mfa == 1) {
            // Store the user ID in the session and logout
            // Auth::logout($user);
            return true;
        }
    }


    private function checkLicenseExpiry()
    {
        $user = Auth::user();
        $company_license = CompanyLicense::where('company_id', $user->company_id)->first();

        // Check License Expiry
        if (now()->toDateString() > $company_license->expiry) {
            Auth::logout($user);
            return true;
        }
    }

    private function isApproved()
    {
        $user = Auth::user();
        if ($user->approved == 1) {
            return true;
        } else {
            return false;
        }
    }

    private function isServiceStatusApproved()
    {
        $user = Auth::user();
        if ($user->service_status == 1) {
            return true;
        } else {
            return false;
        }
    }

   
}
