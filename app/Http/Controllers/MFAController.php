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
        ]);

        $userId = session('mfa_user_id');
        $user = Company::find($userId);        

        if (!$user) {
            return redirect()->route('login')->withErrors(['email' => 'User not found']);
        }
        $settings = Settings::where('company_id', $user->company_id)->first();

        $google2fa = new Google2FA();

        $valid = $google2fa->verifyKey(decrypt($settings->mfa_secret), $request->otp);

        if ($valid) {
            Auth::login($user);
            session()->forget('mfa_user_id');
            return redirect()->route('dashboard')->with('success', 'Logged in successfully');
        }

        return back()->withErrors(['otp' => 'Invalid OTP']);
    }
}
