<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use App\Models\Settings;
use Endroid\QrCode\QrCode;
use App\Models\SiemProvider;
use Illuminate\Http\Request;
use Endroid\QrCode\Color\Color;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\ErrorCorrectionLevel;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Validation\ValidationException;

class ApiSettingsController extends Controller
{
    //
    public function index()
    {
        try {
            $companyId = Auth::user()->company_id;

            $all_settings = Company::where('company_id', $companyId)->with('company_settings')->with('company_whiteLabel')->first();

            if (!$all_settings) {
                return response()->json([
                    'success' => false,
                    'message' => __('Settings not found')
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $all_settings
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred while fetching settings.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            // XSS Check Start
            $input = $request->all();
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json([
                        'success' => false,
                        'messsage' => __('Invalid input detected.')
                    ], 400);
                }
            }

            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);
            // XSS Check End

            // Validation
            $validated = $request->validate([
                'country' => 'required|string|max:255',
                'timeZone' => 'required|string|max:255',
                'dateFormat' => 'required|string|max:255',
            ]);

            $companyId = Auth::user()->company_id;

            // Update the company settings
            $isUpdated = DB::table('company_settings')
                ->where('company_id', $companyId)
                ->update([
                    'country' => $validated['country'],
                    'time_zone' => $validated['timeZone'],
                    'date_format' => $validated['dateFormat'],
                ]);

            if ($isUpdated) {
                log_action("Profile updated");
                return response()->json([
                    'success' => true,
                    'messsage' => __('Profile updated')
                ], 200);
            } else {
                log_action("No changes made or record not found");
                return response()->json([
                    'success' => false,
                    'messsage' => __('No changes made or record not found')
                ], 404);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'messsage' => __('Validation failed.'),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            log_action("Exception while updating profile: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'messsage' => __('An error occurred while updating the profile.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function updatePassword(UpdatePasswordRequest $request)
    {
        try {
            // XSS check start
            $input = $request->all();
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json([
                        'success' => false,
                        'messsage' => __('Invalid input detected.')
                    ], 400);
                }
            }

            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);
            // XSS check end

            // Validation
            $validator = Validator::make($request->all(), [
                'currentPassword' => 'required|string',
                'newPassword' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'messsage' => __("Error: ") . $validator->errors()->first(),
                ], 422);
            }

            $user = Auth::user();

            // Check current password
            if (!Hash::check($request->currentPassword, $user->password)) {
                return response()->json([
                    'success' => false,
                    'messsage' => __('Entered current password is wrong')
                ], 401); // 401 Unauthorized
            }

            // Custom strong password check
            if (!$this->isStrongPassword($request->newPassword)) {
                return response()->json([
                    'success' => false,
                    'messsage' => __('Please set a strong password')
                ], 400);
            }

            // Update password
            $user->password = Hash::make($request->newPassword);
            $user->save();

            log_action("Password updated");

            return response()->json([
                'success' => true,
                'messsage' => __('Password updated successfully')
            ], 200);
        } catch (\Exception $e) {
            log_action("Password update failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'messsage' => __('An error occurred while updating the password.'),
                'error' => $e->getMessage()
            ], 500);
        }
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
        try {
            $status = $request->input('status');
            $user = Auth::user();

            if ($status == '1') {
                $google2fa = new Google2FA();

                // Generate secret and QR code URL
                $secretKey = $google2fa->generateSecretKey();
                $QR_URL = $google2fa->getQRCodeUrl(
                    env('APP_NAME'),
                    $user->email,
                    $secretKey
                );

                // Create QR code image
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

                // Save encrypted secret to company_settings
                $isUpdated = DB::table('company_settings')
                    ->where('company_id', $user->company_id)
                    ->update(['mfa_secret' => encrypt($secretKey)]);

                if ($isUpdated) {
                    log_action("Multi-Factor Authentication is enabled");

                    return response()->json([
                        'success' => true,
                        'QR_Image' => $QR_Image,
                        'secretKey' => encrypt($secretKey)
                    ], 200);
                } else {
                    return response()->json([
                        'success' => false,
                        'msg' => __('Failed to enable MFA.')
                    ], 500);
                }
            } else {
                // Disable MFA
                $isUpdated = DB::table('company_settings')
                    ->where('company_id', $user->company_id)
                    ->update([
                        'mfa' => 0,
                        'mfa_secret' => ''
                    ]);

                if ($isUpdated) {
                    log_action("Multi-Factor Authentication is disabled");

                    return response()->json([
                        'success' => true,
                        'msg' => __('Multi-Factor Authentication is disabled.')
                    ], 200);
                } else {
                    log_action("Failed to disable MFA");

                    return response()->json([
                        'success' => false,
                        'msg' => __('Failed to disable MFA.')
                    ], 500);
                }
            }
        } catch (\Exception $e) {
            log_action("MFA update failed: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'msg' => __('An error occurred while updating MFA.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function verifyMFA(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'totp_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __("Error: ") .  $validator->errors()->first()  // return first error message
            ], 422);
        }

        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => __('Unauthorized')
                ], 401);
            }

            $userSettings = Settings::where('company_id', $user->company_id)->first();

            if (!$userSettings || !$userSettings->mfa_secret) {
                return response()->json([
                    'success' => false,
                    'message' => __('MFA settings not found')
                ], 404);
            }

            $secret = decrypt($userSettings->mfa_secret);
            $google2fa = new Google2FA();

            if ($google2fa->verifyKey($secret, $request->totp_code)) {
                $userSettings->mfa = 1;
                $userSettings->save();

                log_action("MFA verified and enabled for company_id: {$user->company_id}");

                return response()->json([
                    'success' => true,
                    'message' => __('Multi-Factor Authentication enabled')
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => __('Invalid TOTP code')
            ], 400);
        } catch (\Exception $e) {
            log_action("MFA verification failed: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Something went wrong, please try again'),
                'error' => $e->getMessage() // optional for debugging
            ], 500);
        }
    }

    public function updateLang(Request $request)
    {
        try {
            // Step 1: Validate input fields
            $validator = Validator::make($request->all(), [
                'default_phish_lang' => 'required|string|max:10',
                'default_train_lang' => 'required|string|max:10',
                'default_notifi_lang' => 'required|string|max:10',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            // Step 2: XSS sanitization
            $input = $request->all();
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Invalid input detected.')
                    ], 400);
                }
            }

            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);

            // Step 3: Save to database
            $default_phish_lang = $request->input('default_phish_lang');
            $default_train_lang = $request->input('default_train_lang');
            $default_notifi_lang = $request->input('default_notifi_lang');

            $company_id = Auth::user()->company_id;

            $isUpdated = DB::table('company_settings')
                ->where('company_id', $company_id)
                ->update([
                    'default_phishing_email_lang' => $default_phish_lang,
                    'default_training_lang' => $default_train_lang,
                    'default_notifications_lang' => $default_notifi_lang,
                ]);

            if ($isUpdated) {
                log_action("Default language changed to Phishing: {$default_phish_lang} , Training: {$default_train_lang} and Notification: {$default_notifi_lang}");
                return response()->json([
                    'success' => true,
                    'message' => __('Language Updated')
                ]);
            } else {
                log_action("Failed to update default language to Phishing: {$default_phish_lang} , Training: {$default_train_lang} and Notification: {$default_notifi_lang}");
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to update language')
                ]);
            }
        } catch (\Exception $e) {
            // Catch any exceptions and log the error message
            log_action('Error updating language settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Something went wrong, please try again later.')
            ], 500);
        }
    }



    public function updatePhishingEdu(Request $request)
    {
        try {
            // Step 1: Validate input fields
            $validator = Validator::make($request->all(), [
                'redirect_url' => 'required|url',
                'redirect_type' => 'required|string|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => ("Error: ") . $validator->errors()->first()
                ], 422);
            }

            // Step 2: XSS sanitization
            $input = $request->all();
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Invalid input detected.')
                    ], 400);
                }
            }

            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);

            // Step 3: Save to database
            $redirect_url = $request->input('redirect_url');
            $redirect_type = $request->input('redirect_type');

            $company_id = Auth::user()->company_id;

            $isUpdated = DB::table('company_settings')
                ->where('company_id', $company_id)
                ->update([
                    'phish_redirect' => $redirect_type,
                    'phish_redirect_url' => $redirect_url,
                ]);

            if ($isUpdated) {
                log_action('Website to redirect after failing into simulation updated');
                return response()->json([
                    'success' => true,
                    'message' => __('Phishing Education Settings Updated')
                ]);
            } else {
                log_action('Failed to update website to redirect after failing into simulation');
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to update phishing education settings')
                ]);
            }
        } catch (\Exception $e) {
            // Catch any exceptions that occur during the process
            log_action('Error updating phishing education settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Something went wrong, please try again later.')
            ], 500);
        }
    }


    public function updateTrainFreq(Request $request)
    {
        try {
            // Step 1: XSS sanitization
            $input = $request->all();
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Invalid input detected.')
                    ], 400);
                }
            }

            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);

            // Step 2: Validate the 'days' input
            $validator = Validator::make($request->all(), [
                'days' => 'required|integer|min:1', // Assuming days should be a positive integer
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => __("Error: ") . $validator->errors()->first()
                ], 422);
            }

            // Step 3: Retrieve 'days' and company_id
            $days = $request->input('days');
            $company_id = Auth::user()->company_id;

            // Step 4: Update the database
            $isUpdated = DB::table('company_settings')
                ->where('company_id', $company_id)
                ->update(['training_assign_remind_freq_days' => $days]);

            if ($isUpdated) {
                log_action('Training notification frequency updated');
                return response()->json([
                    'success' => true,
                    'message' => __('Training notification frequency updated')
                ]);
            } else {
                log_action('Failed to update training notification frequency');
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to update training notification frequency')
                ]);
            }
        } catch (\Exception $e) {
            // Catch any unexpected exceptions
            log_action('Error updating training notification frequency: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Something went wrong, please try again later.')
            ], 500);
        }
    }


    public function updateReporting(Request $request)
    {
        try {
            // Step 1: Validate the 'status' input
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:0,1', // Only allow 0 or 1
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => ("Error: ")  . $validator->errors()->first()
                ], 422);
            }

            // Step 2: Retrieve 'status' and company_id
            $status = $request->input('status');
            $company_id = Auth::user()->company_id; // Assuming company_id is stored in session or retrieved from Auth

            // Step 3: Update the database
            $isUpdated = DB::table('company_settings')
                ->where('company_id', $company_id)
                ->update(['phish_reporting' => (int)$status]);

            // Step 4: Check if the update was successful
            if ($isUpdated) {
                log_action('Phish Reporting using Gmail, Outlook and Office365 is ' . ($status == '1' ? "enabled" : "disabled") . '!');
                return response()->json([
                    'success' => true,
                    'message' => __('Phish Reporting using Gmail, Outlook and Office365 is ') . ($status == '1' ? __("enabled") : __("disabled")) . '!'
                ]);
            } else {
                log_action('Failed to ' . ($status == '1' ? "enable" : "disable") . ' Phish Reporting');
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to ') . ($status == '1' ? __("enable") : __("disable")) . __(' Phish Reporting'),
                    'error' => DB::connection()->getPdo()->errorInfo()
                ]);
            }
        } catch (\Exception $e) {
            // Catch any unexpected exceptions
            log_action('Error updating Phish Reporting: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Something went wrong, please try again later.')
            ], 500);
        }
    }

    public function deactivateAccount(Request $request)
    {
        try {
            // Step 1: Authenticate user
            $user = Auth::user(); // API authentication

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => __('Unauthorized')
                ], 401);
            }

            $company_id = $user->company_id;

            // Step 2: Update company service status
            $isUpdated = DB::table('company')
                ->where('company_id', $company_id)
                ->update(['service_status' => 0]);

            // Step 3: Check if update was successful
            if ($isUpdated) {
                log_action("Account for company ID {$company_id} has been deactivated");
                return response()->json([
                    'success' => true,
                    'message' => __('Your Account has been Deactivated')
                ]);
            } else {
                log_action("Failed to deactivate account for company ID {$company_id}");
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to deactivate account')
                ]);
            }
        } catch (\Exception $e) {
            // Step 4: Catch any unexpected exceptions
            log_action('Error deactivating account: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Something went wrong, please try again later.')
            ], 500);
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
                    return response()->json([
                        'success' => false, 
                        'message' => __('Invalid input detected.')
                    ], 400);
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
                return response()->json(['success' => true, 'message' => __('SIEM settings updated')]);
            } else {
                SiemProvider::create([
                    'company_id' => $companyId,
                    'provider_name' => $request->input('provider'),
                    'url' => $request->input('provider_url'),
                    'status' => $request->input('status'),
                    'token' => $request->input('auth_token') == '' ? null : $request->input('auth_token'),
                ]);

                return response()->json(['success' => true, 'message' => __('SIEM settings updated')]);
            }

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->validator->errors()->first()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
