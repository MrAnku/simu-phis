<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

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
            'mfa_id' => 'required|integer', // ✅ now expect mfa_id from frontend
        ]);

        $user = Company::find($request->mfa_id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $settings = Settings::where('company_id', $user->company_id)->first();

        // return response()->json([
        //     'message' => 'Logged in successfully',
        //     'user' => decrypt(
        //         $settings->mfa_secret
        //     ),
        // ], 200);

        if (!$settings || !$settings->mfa_secret) {
            return response()->json([
                'success' => false,
                'message' => 'MFA settings not found',
            ], 400);
        }

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey(decrypt($settings->mfa_secret), $request->otp);

        // return response()->json([
        //     'message' => 'Logged in successfully',
        //     'user' =>   $valid,
        // ], 200);

        if ($valid) {
            // Auth::login($user);
            return response()->json([
                'success' => true,
                'message' => 'Logged in successfully',
                'user' => $user,
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid OTP',
        ], 401);
    }
}
