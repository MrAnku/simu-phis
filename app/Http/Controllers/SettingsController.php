<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    //
    public function index()
    {
        $companyId = auth()->user()->company_id;

        $all_settings = Company::where('company_id', $companyId)->with('company_settings')->first();

        return view('settings', compact('all_settings'));
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'country' => 'required|string|max:255',
            'timeZone' => 'required|string|max:255',
            'dateFormat' => 'required|string|max:255',
        ]);

        $companyId = auth()->user()->company_id;

        // Update the company settings
        $isUpdated = DB::table('company_settings')
            ->where('company_id', $companyId)
            ->update([
                'country' => $request->input('country'),
                'time_zone' => $request->input('timeZone'),
                'date_format' => $request->input('dateFormat'),
            ]);

        if ($isUpdated) {
            return response()->json(['status' => 1, 'msg' => 'Profile Updated']);
        } else {
            return response()->json(['status' => 0, 'msg' => 'Failed to update profile']);
        }
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {

        $validator = Validator::make($request->all(), [
            'currentPassword' => 'required|string',
            'newPassword' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'msg' => $validator->errors()->first(),
            ]);
        }

        $user = Auth::user();

        if (!Hash::check($request->currentPassword, $user->password)) {
            return response()->json(['status' => 0, 'msg' => 'Entered current password is wrong']);
        }

        if (!$this->isStrongPassword($request->newPassword)) {
            return response()->json(['status' => 0, 'msg' => 'Please set a strong password']);
        }

        $user->password = Hash::make($request->newPassword);
        $user->save();

        return response()->json(['status' => 1, 'msg' => 'Password Updated']);
    }

    private function isStrongPassword($password)
    {
        // Minimum password length
        $minLength = 8;

        // Regular expressions to check for various criteria
        $hasUppercase = preg_match('/[A-Z]/', $password);
        $hasLowercase = preg_match('/[a-z]/', $password);
        $hasNumber = preg_match('/\d/', $password);
        $hasSpecialChar = preg_match('/[^A-Za-z0-9]/', $password);

        // Check if all criteria are met
        if (strlen($password) >= $minLength && $hasUppercase && $hasLowercase && $hasNumber && $hasSpecialChar) {
            return true;
        } else {
            return false;
        }
    }

    public function updateMFA(Request $request)
    {
        $status = $request->input('status');

        $company_id = Auth::user()->company_id; // Assuming company_id is stored in session or retrieved from Auth

        if ($status == '1') {
            $isUpdated = DB::table('company_settings')
                ->where('company_id', $company_id)
                ->update(['mfa' => 1]);

            if ($isUpdated) {
                return response()->json(['status' => 1, 'msg' => 'Multi-Factor Authentication is enabled']);
            } else {
                return response()->json(['status' => 0, 'msg' => 'Failed to enable MFA']);
            }
        } else {
            $isUpdated = DB::table('company_settings')
                ->where('company_id', $company_id)
                ->update(['mfa' => 0, 'mfa_secret' => '']);

            if ($isUpdated) {
                return response()->json(['status' => 1, 'msg' => 'Multi-Factor Authentication is disabled']);
            } else {
                return response()->json(['status' => 0, 'msg' => 'Failed to disable MFA']);
            }
        }
    }

    public function updateLang(Request $request)
    {
        $default_phish_lang = $request->input('default_phish_lang');
        $default_train_lang = $request->input('default_train_lang');
        $default_notifi_lang = $request->input('default_notifi_lang');

        $company_id = Auth::user()->company_id; // Assuming company_id is stored in session or retrieved from Auth

        $isUpdated = DB::table('company_settings')
                        ->where('company_id', $company_id)
                        ->update([
                            'default_phishing_email_lang' => $default_phish_lang,
                            'default_training_lang' => $default_train_lang,
                            'default_notifications_lang' => $default_notifi_lang,
                        ]);

        if ($isUpdated) {
            return response()->json(['status' => 1, 'msg' => 'Language Updated']);
        } else {
            return response()->json(['status' => 0, 'msg' => 'Failed to update language']);
        }
    }

    public function updatePhishingEdu(Request $request)
    {
        $redirect_url = $request->input('redirect_url');
        $redirect_type = $request->input('redirect_type');

        $company_id = Auth::user()->company_id; // Assuming company_id is stored in session or retrieved from Auth

        $isUpdated = DB::table('company_settings')
                        ->where('company_id', $company_id)
                        ->update([
                            'phish_redirect' => $redirect_type,
                            'phish_redirect_url' => $redirect_url,
                        ]);

        if ($isUpdated) {
            return response()->json(['status' => 1, 'msg' => 'Phishing Education Settings Updated']);
        } else {
            return response()->json(['status' => 0, 'msg' => 'Failed to update phishing education settings']);
        }
    }

    public function updateTrainFreq(Request $request)
    {
        $days = $request->input('days');

        $company_id = Auth::user()->company_id; // Assuming company_id is stored in session or retrieved from Auth

        $isUpdated = DB::table('company_settings')
                        ->where('company_id', $company_id)
                        ->update(['training_assign_remind_freq_days' => $days]);

        if ($isUpdated) {
            return response()->json(['status' => 1, 'msg' => 'Training notification frequency updated']);
        } else {
            return response()->json(['status' => 0, 'msg' => 'Failed to update training notification frequency']);
        }
    }

    public function updateReporting(Request $request)
    {
        $status = $request->input('status');

        $company_id = Auth::user()->company_id; // Assuming company_id is stored in session or retrieved from Auth

        if ($status == '1') {
            $isUpdated = DB::table('company_settings')
                            ->where('company_id', $company_id)
                            ->update(['phish_reporting' => 1]);

            if ($isUpdated) {
                return response()->json(['status' => 1, 'msg' => 'Phish Reporting using Gmail, Outlook and Office365 is enabled!']);
            } else {
                return response()->json(['status' => 0, 'msg' => 'Failed to enable Phish Reporting', 'error' => DB::connection()->getPdo()->errorInfo()]);
            }
        } else {
            $isUpdated = DB::table('company_settings')
                            ->where('company_id', $company_id)
                            ->update(['phish_reporting' => 0, 'mfa_secret' => '']);

            if ($isUpdated) {
                return response()->json(['status' => 1, 'msg' => 'Phish Reporting using Gmail, Outlook and Office365 is disabled!']);
            } else {
                return response()->json(['status' => 0, 'msg' => 'Failed to disable Phish Reporting', 'error' => DB::connection()->getPdo()->errorInfo()]);
            }
        }
    }

    public function deactivateAccount(Request $request)
    {
        $company_id = Auth::user()->company_id; // Assuming company_id is stored in session or retrieved from Auth

        $isUpdated = DB::table('company')
                        ->where('company_id', $company_id)
                        ->update(['service_status' => 0]);

        if ($isUpdated) {
            return response()->json(['status' => 1, 'msg' => 'Your Account has been Deactivated']);
        } else {
            return response()->json(['status' => 0, 'msg' => 'Failed to deactivate account']);
        }
    }
}
