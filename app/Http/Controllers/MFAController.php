<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;
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
            'company_id' => 'required', // ✅ now expect mfa_id from frontend
        ]);

        $company = Company::where('company_id', $request->company_id)->first();

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again',
            ], 404);
        }

        $settings = Settings::where('company_id', $company->company_id)->first();

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
            $cookie = cookie('jwt', $token, env('JWT_TTL', 1440));

            return response()->json([
                'token' => $token,
                'success' => true,
                'company' => $company,
                'message' => 'Logged in successfully',
            ])->withCookie($cookie);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid OTP',
        ], 401);
    }
}
