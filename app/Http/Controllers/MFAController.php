<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Services\AuthService;
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
            $authService = new AuthService($request->email);
            return $authService->loginCompany('mfa');
          
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid OTP',
        ], 401);
    }
}
