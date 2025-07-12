<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Models\CompanySettings;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class MFAController extends Controller
{
    //
    public function showEnterOTPForm()
    {
        return view('auth.mfaLogin');
    }

    public function verifyOTP(Request $request)
    {
        $request->validate([
            'otp' => 'required|string',
            'email' => 'required|email', // ✅ now expect mfa_id from frontend
        ]);

        $company = Company::where('email', $request->email)->first();

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again',
            ], 404);
        }

        $settings = CompanySettings::where('email', $company->email)->first();

        if (!$settings || !$settings->mfa_secret) {
            return response()->json([
                'success' => false,
                'message' => 'MFA settings not found',
            ], 400);
        }

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey(decrypt($settings->mfa_secret), $request->otp);

        if ($valid) {
            Auth::login($company);
            $token = JWTAuth::fromUser($company);
            

            $enabledFeatures = Company::where('company_id', $company->company_id)->value('enabled_feature');
            if (!$enabledFeatures) {
                $enabledFeatures = 'null'; // Default to null if no features are enabled
            }
            
            $cookie = cookie('jwt', $token, env('JWT_TTL', 1440));
            $enabledFeatureCookie = cookie('enabled_feature', $enabledFeatures, env('JWT_TTL', 1440));

            return response()->json([
                'token' => $token,
                'success' => true,
                'company' => $company,
                'message' => 'Logged in successfully',
            ])->withCookie($cookie)
              ->withCookie($enabledFeatureCookie);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid OTP',
        ], 401);
    }
}
