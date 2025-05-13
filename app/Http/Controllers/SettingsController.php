<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Models\Company;
use App\Models\Settings;
use App\Models\SiemProvider;
use App\Models\WhiteLabelledCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PragmaRX\Google2FA\Google2FA;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\ErrorCorrectionLevel;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    //
    public function index()
    {
        $companyId = Auth::user()->company_id;

        $all_settings = Company::where('company_id', $companyId)->with('company_settings')->first();

        $siemSettings = SiemProvider::where('company_id', $companyId)->first();
        if ($siemSettings) {
            $all_settings->siemSettings = $siemSettings;
        } else {
            $all_settings->siemSettings = null;
        }

        $whiteLabel = WhiteLabelledCompany::where('company_id', Auth::user()->company_id)->first();

        return view('settings', compact('all_settings', 'whiteLabel'));
    }

    public function updateProfile(Request $request)
    {
        //xss check start

        $input = $request->all();
        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return response()->json(['status' => 0, 'msg' => __('Invalid input detected.')]);
            }
        }
        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);

        //xss check end

        $request->validate([
            'country' => 'required|string|max:255',
            'timeZone' => 'required|string|max:255',
            'dateFormat' => 'required|string|max:255',
        ]);

        $companyId = Auth::user()->company_id;

        // Update the company settings
        $isUpdated = DB::table('company_settings')
            ->where('company_id', $companyId)
            ->update([
                'country' => $request->input('country'),
                'time_zone' => $request->input('timeZone'),
                'date_format' => $request->input('dateFormat'),
            ]);

        if ($isUpdated) {

            log_action("Profile updated");
            return response()->json(['status' => 1, 'msg' => __('Profile updated')]);
        } else {
            log_action("Failed to update profile");
            return response()->json(['status' => 0, 'msg' => __('Failed to update profile')]);
        }
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        //xss check start

        $input = $request->all();
        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return response()->json(['status' => 0, 'msg' => __('Invalid input detected.')]);
            }
        }
        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);

        //xss check end

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
            return response()->json(['status' => 0, 'msg' => __('Entered current password is wrong')]);
        }

        if (!$this->isStrongPassword($request->newPassword)) {
            return response()->json(['status' => 0, 'msg' => __('Please set a strong password')]);
        }

        $user->password = Hash::make($request->newPassword);
        $user->save();

        log_action("Password updated");
        return response()->json(['status' => 1, 'msg' => __('Password Updated')]);
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

        $user = Auth::user(); // Assuming company_id is stored in session or retrieved from Auth

        if ($status == '1') {

            $google2fa = new Google2FA();

            // Generate a secret key for the user
            $secretKey = $google2fa->generateSecretKey();

            // Generate the QR code URL
            $QR_URL = $google2fa->getQRCodeUrl(
                env('APP_NAME'),
                $user->email,
                $secretKey
            );

            // Generate the QR code image
            // $qrCode = QrCode::create($QR_URL);
            $qrCode = new QrCode(
                data: $QR_URL,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Low,
                size: 300,
                margin: 10,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
                foregroundColor: new Color(0, 0, 0),
                backgroundColor: new Color(255, 255, 255)
            );
            $writer = new PngWriter();
            $QR_Image = $writer->write($qrCode)->getDataUri();

            // Save the secret key to the user
            // $user->mfa_secret = $secretKey;
            $isUpdated = DB::table('company_settings')
                ->where('company_id', $user->company_id)
                ->update(['mfa_secret' => encrypt($secretKey)]);

            // $user->save();

            // return view('mfa.enable', ['QR_Image' => $QR_Image, 'secretKey' => $secretKey]);
            if ($isUpdated) {


                return response()->json(['status' => 1, 'QR_Image' => $QR_Image, 'secretKey' => encrypt($secretKey)]);
            } else {


                return response()->json(['status' => 0, 'msg' => __('Failed to enable MFA')]);
            }


            // $isUpdated = DB::table('company_settings')
            //     ->where('company_id', $company_id)
            //     ->update(['mfa' => 1]);

            // if ($isUpdated) {
            //     return response()->json(['status' => 1, 'msg' => 'Multi-Factor Authentication is enabled']);
            // } else {
            //     return response()->json(['status' => 0, 'msg' => 'Failed to enable MFA']);
            // }
        } else {
            $isUpdated = DB::table('company_settings')
                ->where('company_id', $user->company_id)
                ->update(['mfa' => 0, 'mfa_secret' => '']);

            if ($isUpdated) {

                log_action("Multi-Factor Authentication is disabled");

                return response()->json(['status' => 1, 'msg' => __('Multi-Factor Authentication is disabled')]);
            } else {

                log_action("Failed to disable MFA");
                return response()->json(['status' => 0, 'msg' => __('Failed to disable MFA')]);
            }
        }
    }

    public function verifyMFA(Request $request)
    {
        $request->validate([
            'totp_code' => 'required|string',
        ]);

        // Get the authenticated user's company ID
        $companyId = Auth::user()->company_id;

        // Retrieve user settings for the company
        $user_settings = Settings::where('company_id', $companyId)->first();

        if (!$user_settings) {
            return response()->json(['status' => 0, 'msg' => __('User settings not found')]);
        }

        // Decrypt the stored MFA secret
        $db_secret = decrypt($user_settings->mfa_secret);

        // Initialize Google2FA
        $google2fa = new Google2FA();

        // Verify the provided TOTP code against the stored secret
        $valid = $google2fa->verifyKey($db_secret, $request->totp_code);

        if ($valid) {
            // Update user settings to enable MFA
            $user_settings->mfa = 1;
            $user_settings->save();

            log_action("Multi-Factor Authentication is enabled");
            return redirect()->route('settings.index')->with(['success' => __('Multi Factor Authentication is enabled')]);

            // return response()->json(['status' => 1, 'msg' => 'Multi Factor Authentication is enabled']);
        } else {
            log_action("Entered invalid TOTP code to enable MFA");
            return redirect()->route('settings.index')->with(['error' => __('Invalid TOTP code')]);
        }
    }


    public function updateLang(Request $request)
    {
        //xss check start

        $input = $request->all();
        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return response()->json(['status' => 0, 'msg' => __('Invalid input detected.')]);
            }
        }
        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);

        //xss check end

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
            log_action("Default language changed to Phishing: {$default_phish_lang} , Training: {$default_train_lang} and Notification: {$default_notifi_lang}");
            return response()->json(['status' => 1, 'msg' => __('Language Updated')]);
        } else {

            log_action("Failed to update default language to Phishing: {$default_phish_lang} , Training: {$default_train_lang} and Notification: {$default_notifi_lang}");
            return response()->json(['status' => 0, 'msg' => __('Failed to update language')]);
        }
    }

    public function updatePhishingEdu(Request $request)
    {
        //xss check start

        $input = $request->all();
        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return response()->json(['status' => 0, 'msg' => __('Invalid input detected.')]);
            }
        }
        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);

        //xss check end

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
            log_action('Website to redirect after felling into simulation updated');
            return response()->json(['status' => 1, 'msg' => __('Phishing Education Settings Updated')]);
        } else {
            log_action('Failed to update website to redirect after felling into simulation');
            return response()->json(['status' => 0, 'msg' => __('Failed to update phishing education settings')]);
        }
    }

    public function updateTrainFreq(Request $request)
    {
        //xss check start

        $input = $request->all();
        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return response()->json(['status' => 0, 'msg' => __('Invalid input detected.')]);
            }
        }
        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);

        //xss check end

        $days = $request->input('days');

        $company_id = Auth::user()->company_id; // Assuming company_id is stored in session or retrieved from Auth

        $isUpdated = DB::table('company_settings')
            ->where('company_id', $company_id)
            ->update(['training_assign_remind_freq_days' => $days]);

        if ($isUpdated) {
            log_action('Training notification frequency updated');
            return response()->json(['status' => 1, 'msg' => __('Training notification frequency updated')]);
        } else {
            log_action('Failed to update training notification frequency');
            return response()->json(['status' => 0, 'msg' => __('Failed to update training notification frequency')]);
        }
    }

    public function updateReporting(Request $request)
    {
        $status = $request->input('status');

        $company_id = Auth::user()->company_id; // Assuming company_id is stored in session or retrieved from Auth

        $isUpdated = DB::table('company_settings')
            ->where('company_id', $company_id)
            ->update(['phish_reporting' => (int)$status]);

        if ($isUpdated) {
            log_action('Phish Reporting using Gmail, Outlook and Office365 is ' . ($status == '1' ? "enabled" : "disabled") . '!');
            return response()->json([
                'status' => 1,
                'msg' => __('Phish Reporting using Gmail, Outlook and Office365 is ') . ($status == '1' ? __("enabled") : __("disabled")) . '!'
            ]);
        } else {
            log_action('Failed to ' . ($status == '1' ? "enable" : "disable") . ' Phish Reporting');
            return response()->json([
                'status' => 0,
                'msg' => __('Failed to ') . ($status == '1' ? __("enable") : __("disable")) . __('Phish Reporting'),
                'error' => DB::connection()->getPdo()->errorInfo()
            ]);
        }
    }

    public function deactivateAccount(Request $request)
    {
        $company_id = Auth::user()->company_id; // Assuming company_id is stored in session or retrieved from Auth

        $isUpdated = DB::table('company')
            ->where('company_id', $company_id)
            ->update(['service_status' => 0]);

        if ($isUpdated) {

            log_action("Account has deactivated");
            return response()->json(['status' => 1, 'msg' => __('Your Account has been Deactivated')]);
        } else {
            log_action("Failed to deactivate account");
            return response()->json(['status' => 0, 'msg' => __('Failed to deactivate account')]);
        }
    }

    public function updateSiem(Request $request)
    {
        try {
            $request->validate([
                'provider' => 'required|string|in:webhook,splunk',
                'provider_url' => 'required|string',
                'auth_token' => 'nullable|string',
            ]);

            //xss check start

            $input = $request->all();

            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json(['status' => 0, 'msg' => __('Invalid input detected.')]);
                }
            }
            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);
            //xss check end

            $companyId = Auth::user()->company_id;
            $siemSettings = SiemProvider::where('company_id', $companyId)->first();
            if ($siemSettings) {
                $siemSettings->update([
                    'provider_name' => $request->input('provider'),
                    'url' => $request->input('provider_url'),
                    'status' => $request->input('status'),
                    'token' => $request->input('auth_token') == '' ? null : $request->input('auth_token'),
                ]);
                return response()->json(['status' => 1, 'msg' => __('SIEM settings updated')]);
            } else {
                SiemProvider::create([
                    'company_id' => $companyId,
                    'provider_name' => $request->input('provider'),
                    'url' => $request->input('provider_url'),
                    'status' => $request->input('status'),
                    'token' => $request->input('auth_token') == '' ? null : $request->input('auth_token'),
                ]);

                return response()->json(['status' => 1, 'msg' => __('SIEM settings created')]);
            }

        } catch (ValidationException $e) {
            return response()->json(['status' => 0, 'msg' => $e->validator->errors()->first()]);
        } catch (\Exception $e) {
            return response()->json(['status' => 0, 'msg' => $e->getMessage()]);
        }
    }
}
