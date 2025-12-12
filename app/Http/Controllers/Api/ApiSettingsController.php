<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use App\Models\SmartGroup;
use App\Models\UsersGroup;
use Endroid\QrCode\QrCode;
use Illuminate\Support\Str;
use App\Models\SiemProvider;
use Illuminate\Http\Request;
use App\Models\CompanySettings;
use Endroid\QrCode\Color\Color;
use App\Mail\CreateSubAdminMail;
use App\Models\AutoSyncEmployee;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\WhiteLabelledCompany;
use App\Models\TrainingSetting;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\RoundBlockSizeMode;
use App\Services\CheckWhitelabelService;
use Endroid\QrCode\ErrorCorrectionLevel;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\UpdatePasswordRequest;
use App\Models\PhishSetting;
use Illuminate\Validation\ValidationException;

class ApiSettingsController extends Controller
{
    //
    public function index()
    {
        try {
            $companyEmail = Auth::user()->email;

            $all_settings = Company::where('email', $companyEmail)
                ->with('company_settings', 'company_whiteLabel', 'company_whiteLabel.smtp', 'company_whiteLabel.whatsappConfig', 'siemConfig', 'phish_settings')
                ->first();

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

            $companyEmail = Auth::user()->email;

            // Update the company settings
            $isUpdated = DB::table('company_settings')
                ->where('email', $companyEmail)
                ->update([
                    'country' => $validated['country'],
                    'time_zone' => $validated['timeZone'],
                    'date_format' => $validated['dateFormat'],
                ]);

            if ($isUpdated) {
                log_action("Profile updated");
                return response()->json([
                    'success' => true,
                    'message' => __('Profile updated')
                ], 200);
            } else {
                log_action("No changes made or record not found");
                return response()->json([
                    'success' => false,
                    'message' => __('No changes made or record not found')
                ], 404);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation failed.'),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            log_action("Exception while updating profile: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('An error occurred while updating the profile.'),
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
                    ->where('email', $user->email)
                    ->update(['mfa_secret' => encrypt($secretKey)]);

                if ($isUpdated) {
                    log_action("Multi-Factor Authentication is enabled");

                    return response()->json([
                        'success' => true,
                        'message' => __("MFA QR code generated successfully."),
                        'QR_Image' => $QR_Image,
                        'secretKey' => encrypt($secretKey)
                    ], 200);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => __('Failed to enable MFA.')
                    ], 500);
                }
            } else {
                // Disable MFA
                $isUpdated = DB::table('company_settings')
                    ->where('email', $user->email)
                    ->update([
                        'mfa' => 0,
                        'mfa_secret' => ''
                    ]);

                if ($isUpdated) {
                    log_action("Multi-Factor Authentication is disabled");

                    return response()->json([
                        'success' => true,
                        'message' => __('Multi-Factor Authentication is disabled.')
                    ], 200);
                } else {
                    log_action("Failed to disable MFA");

                    return response()->json([
                        'success' => false,
                        'message' => __('Failed to disable MFA.')
                    ], 500);
                }
            }
        } catch (\Exception $e) {
            log_action("MFA update failed: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
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

            $userSettings = CompanySettings::where('email', $user->email)->first();

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

            $companyEmail = Auth::user()->email;

            $isUpdated = DB::table('company_settings')
                ->where('email', $companyEmail)
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
                // 'redirect_url' => 'required|url',
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

            $companyEmail = Auth::user()->email;

            $isUpdated = DB::table('company_settings')
                ->where('email', $companyEmail)
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
            $companyEmail = Auth::user()->email;

            // Step 4: Update the database
            $isUpdated = DB::table('company_settings')
                ->where('email', $companyEmail)
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
            $companyEmail = Auth::user()->email;

            // Step 3: Update the database
            $isUpdated = DB::table('company_settings')
                ->where('email', $companyEmail)
                ->update(['phish_reporting' => (int)$status]);

            // return response()->json([
            //     'success' => true,
            //     "data" => $isUpdated
            // ]);

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

    public function addSubAdmin(Request $request)
    {
        // return $request;
        try {
            // $pocAccount = Company::where('company_id', Auth::user()->company_id)
            //     ->where('role', null)
            //     ->where('account_type', 'poc')->exists();

            // if ($pocAccount) {
            //     return response()->json(['success' => false, 'message' => __('You cannot add sub-admins to this account')], 402);
            // }
            $request->validate([
                'email' => 'required|email|unique:company,email',
                'full_name' => 'required|string|max:255',
                'role' => 'required|string|in:sub-admin,admin',
                'enabled_feature' => 'nullable|array',
            ]);

            $token = Str::random(32);
            $pass_create_link = env("NEXT_APP_URL") . "/company/create-password/" . $token;

            $admin = Company::where('company_id', Auth::user()->company_id)->where('role', null)->first();
            Company::create([
                "email" => $request->email,
                "full_name" => $request->full_name,
                "company_id" => $admin->company_id,
                "company_name" => $admin->company_name,
                "partner_id" => $admin->partner_id,
                "employees" => $admin->employees,
                "usedemployees" => $admin->usedemployees,
                "storage_region" => $admin->storage_region,
                "role" => $request->role,
                "approved" => 1,
                "service_status" => 1,
                "account_type" => 'normal',
                "enabled_feature" => $request->enabled_feature !== null ? json_encode($request->enabled_feature) : null,
                'pass_create_token' => $token,
                'approve_date' => now(),
                'created_at' => now(),
            ]);

            CompanySettings::create([
                'company_id' => $admin->company_id,
                "email" => $request->email,
                'country' => $admin->storage_region,
                'time_zone' => 'Pacific/Midway',
                'date_format' => 'dd/MM/yyyy',
                'mfa' => '0',
                'mfa_secret' => '',
                'default_phishing_email_lang' => 'en',
                'default_training_lang' => 'en',
                'default_notifications_lang' => 'en',
                'phish_redirect' => 'simuEducation',
                'phish_redirect_url' => '',
                'phish_reporting' => '0',
                'training_assign_remind_freq_days' => '1',
            ]);

            $branding = new CheckWhitelabelService($admin->company_id);
            $companyName = $branding->companyName();
            $companyLogo = $branding->companyDarkLogo();
            $portalDomain = $branding->platformDomain();

            if ($branding->isCompanyWhitelabeled()) {

                $pass_create_link = $branding->platformDomain() . "/company/create-password/" . $token;
                $branding->updateSmtpConfig();
            } else {
                $branding->clearSmtpConfig();
            }

            // Send email with company creation link
            Mail::to($request->email)->send(new CreateSubAdminMail(
                $request,
                $companyName,
                $companyLogo,
                $portalDomain,
                $pass_create_link
            ));

            // Log action
            log_action("Sub Admin account created and submitted. Sub Admin email: " . $request->email);

            return response()->json(['success' => true, 'message' => __('Sub Admin Created Successfully')]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->validator->errors()->first()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }



    public function updateSubAdmin(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:company,email',
                'role' => 'required|string|in:admin,sub-admin',
                'enabled_feature' => 'nullable|array',
            ]);

            $subAdmin = Company::where('company_id', Auth::user()->company_id)
                ->where('role', '!=', null)
                ->where('email', $request->email)
                ->first();

            if (!$subAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => __('Sub-admin/Admin not found')
                ], 404);
            }

            $subAdmin->enabled_feature = $request->enabled_feature !== null ? json_encode($request->enabled_feature) : null;
            $subAdmin->role = $request->role;
            $subAdmin->save();

            log_action("Sub Admin/Admin updated: " . $request->email);

            return response()->json([
                'success' => true,
                'message' => __('Features updated successfully')
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->validator->errors()->first()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }


    public function subAdmins()
    {
        try {
            $subAdmins = Company::where('company_id', Auth::user()->company_id)
                ->where('role', '!=', null)
                ->get(['email', 'full_name', 'role', 'enabled_feature', 'service_status', 'created_at']);
            $adminPermissions = Company::where('company_id', Auth::user()->company_id)
                ->where('role', null)
                ->first(['enabled_feature']);

            return response()->json([
                'success' => true,
                'data' => $subAdmins,
                'admin_permissions' => $adminPermissions ?? []
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred ') .  $e->getMessage()
            ], 500);
        }
    }

    public function changeServiceStatus(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:company,email',
                'service_status' => 'required',
            ]);

            $subAdmin = Company::where('company_id', Auth::user()->company_id)
                ->where('role', 'sub-admin')
                ->where('email', $request->email)
                ->first();

            if (!$subAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => __('Sub-admin not found')
                ], 404);
            }

            $subAdmin->service_status = $request->service_status;
            $subAdmin->save();

            if ($request->service_status == 1) {
                $msg = 'Subadmin account activated successfully';
            } else {
                $msg = 'Subadmin account deactivated successfully';
            }
            log_action($msg);

            return response()->json([
                'success' => true,
                'message' => __('Sub-admin status updated successfully')
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->validator->errors()->first()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteSubAdmin(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:company,email',
            ]);

            $subAdmin = Company::where('company_id', Auth::user()->company_id)
                ->where('role', '!=', null)
                ->where('email', $request->email)
                ->first();

            if (!$subAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => __('Admin/Sub-admin not found')
                ], 404);
            }

            $subAdmin->delete();
            CompanySettings::where('email', $request->email)->delete();

            log_action("Admin/Sub Admin deleted: " . $request->email);

            return response()->json([
                'success' => true,
                'message' => __('Admin/Sub-admin deleted successfully')
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->validator->errors()->first()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function checkWhitelabeling()
    {
        $company = Company::with('partner')->where('company_id', Auth::user()->company_id)->first();

        $partner_id = $company->partner->partner_id;

        $isWhitelabled = WhiteLabelledCompany::where('partner_id', $partner_id)
            ->where('approved_by_partner', 1)
            ->first();

        if ($isWhitelabled) {
            return [
                'portal_domain' => $isWhitelabled->domain,
                'company_name' => $isWhitelabled->company_name
            ];
        }

        return [
            'portal_domain' => 'app.simuphish.com',
            'company_name' => env('APP_NAME'),
        ];
    }

    public function smartGroups()
    {
        try {
            $smartGroups = SmartGroup::where('company_id', Auth::user()->company_id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $smartGroups
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred ') .  $e->getMessage()
            ], 500);
        }
    }

    public function addSmartGroup(Request $request)
    {
        try {
            $request->validate([
                'group_name' => 'required|string|max:255',
                'risk_type' => 'required|in:low,medium,high',
            ]);

            SmartGroup::create([
                'group_name' => $request->group_name,
                'risk_type' => $request->risk_type,
                'company_id' => Auth::user()->company_id,
            ]);

            log_action("Smart Group created: " . $request->group_name);

            return response()->json([
                'success' => true,
                'message' => __('Smart Group created successfully')
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->validator->errors()->first()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteSmartGroup($id)
    {
        try {
            $id = base64_decode($id);
            $smartGroup = SmartGroup::where('id', $id)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$smartGroup) {
                return response()->json([
                    'success' => false,
                    'message' => __('Smart Group not found')
                ], 404);
            }

            $smartGroup->delete();

            log_action("Smart Group deleted: " . $smartGroup->group_name);

            return response()->json([
                'success' => true,
                'message' => __('Smart Group deleted successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function autoSyncing()
    {
        try {
            $autoSync = AutoSyncEmployee::with('localGroupDetail')->where('company_id', Auth::user()->company_id)->get();

            $localGroups = UsersGroup::where('company_id', Auth::user()->company_id)
                ->get(['group_id', 'group_name']);


            return response()->json([
                'success' => true,
                'data' => [
                    'auto_sync' => $autoSync,
                    'local_groups' => $localGroups
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') .  $e->getMessage()
            ], 500);
        }
    }

    public function addAutoSync(Request $request)
    {
        try {
            $request->validate([
                'provider' => 'required|string|max:20|in:google,outlook',
                'group_id' => 'required|string|max:6',
                'provider_group_id' => 'required|string|max:40',
                'sync_freq_days' => 'required|integer|in:1,3,7',
                'sync_employee_limit' => 'required|integer|min:1|max:100',
            ]);

            //check if the provider is already exists for this company
            $existingSync = AutoSyncEmployee::where('company_id', Auth::user()->company_id)
                ->where('provider', $request->provider)
                ->first();

            if ($existingSync) {
                return response()->json([
                    'success' => false,
                    'message' => __('Auto Sync configuration already exists')
                ], 409);
            }

            AutoSyncEmployee::create([
                'provider' => $request->provider,
                'local_group_id' => $request->group_id,
                'provider_group_id' => $request->provider_group_id,
                'sync_freq_days' => $request->sync_freq_days,
                'sync_employee_limit' => $request->sync_employee_limit,
                'company_id' => Auth::user()->company_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Auto Sync configuration saved successfully')
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->validator->errors()->first()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteAutoSync(Request $request, $id)
    {
        try {
            $id = base64_decode($id);
            $autoSync = AutoSyncEmployee::where('id', $id)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$autoSync) {
                return response()->json([
                    'success' => false,
                    'message' => __('Auto Sync configuration not found')
                ], 404);
            }

            $autoSync->delete();

            return response()->json([
                'success' => true,
                'message' => __('Auto Sync configuration deleted successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateAutoSync(Request $request, $id)
    {
        try {
            $id = base64_decode($id);
            $autoSync = AutoSyncEmployee::where('id', $id)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$autoSync) {
                return response()->json([
                    'success' => false,
                    'message' => __('Auto Sync configuration not found')
                ], 404);
            }

            $request->validate([
                'provider' => 'required|string|max:20|in:google,outlook',
                'group_id' => 'required|string|max:6',
                'provider_group_id' => 'required|string|max:40',
                'sync_freq_days' => 'required|integer|in:1,3,7',
                'sync_employee_limit' => 'required|integer|min:1|max:100',
            ]);

            $autoSync->update([
                'provider' => $request->provider,
                'local_group_id' => $request->group_id,
                'provider_group_id' => $request->provider_group_id,
                'sync_freq_days' => $request->sync_freq_days,
                'sync_employee_limit' => $request->sync_employee_limit,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Auto Sync configuration updated successfully')
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->validator->errors()->first()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateTimeToClick(Request $request)
    {
        try {
            $request->validate([
                'status' => 'required|boolean',
            ]);

            if ($request->status == true) {
                $request->validate([
                    'seconds' => 'required|integer|min:10|max:50',
                ]);
            }

            $settings = CompanySettings::where('email', Auth::user()->email)->first();

            if (!$settings) {
                return response()->json(['success' => false, 'message' => __('Company settings not found')], 404);
            }
            if ($request->status == true) {
                $settings->update([
                    'time_to_click' => $request->seconds,
                ]);
            } else {
                $settings->update([
                    'time_to_click' => null,
                ]);
            }

            return response()->json(['success' => true, 'message' => __('Time to click updated successfully')]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function phishingReply(Request $request)
    {
        try {
            $request->validate([
                'phish_reply' => 'required|boolean',
            ]);

            $companyId = Auth::user()->company_id;

            $settings = CompanySettings::where('company_id', $companyId)->first();

            if (!$settings) {
                return response()->json(['success' => false, 'message' => __('Company settings not found')], 404);
            }

            // Update phish_reply for all settings with this company_id
            CompanySettings::where('company_id', $companyId)
                ->update(['phish_reply' => $request->phish_reply]);

            return response()->json(['success' => true, 'message' => __('Phishing reply updated successfully')], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function enableReport(Request $request)
    {
        try {
            $request->validate([
                'overall_report' => 'required|string|in:weekly,monthly,quarterly,annually,semi_annually',
                'report_emails' => 'array|max:4',
                'report_emails.*' => 'email',
            ]);

            $companyId = Auth::user()->company_id;

            $settings = CompanySettings::where('company_id', $companyId)->first();

            if (!$settings) {
                return response()->json(['success' => false, 'message' => __('Company settings not found')], 404);
            }

            CompanySettings::where('company_id', $companyId)
                ->first()
                ->update(['overall_report' => $request->overall_report, 'report_emails' => $request->report_emails]);

            return response()->json(['success' => true, 'message' => __('Overall reporting updated successfully')], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function disableReport()
    {
        try {
            $companyId = Auth::user()->company_id;

            $settings = CompanySettings::where('company_id', $companyId)->first();

            if (!$settings) {
                return response()->json(['success' => false, 'message' => __('Company settings not found')], 404);
            }

            CompanySettings::where('company_id', $companyId)
                ->update(['overall_report' => null, 'report_emails' => null]);

            return response()->json(['success' => true, 'message' => __('Overall reporting disabled successfully')], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updatePhishResults(Request $request)
    {
        try {
            $request->validate([
                'phish_results_visible' => 'required|boolean',
            ]);
            $companyEmail = Auth::user()->email;

            $settings = PhishSetting::where('company_id', Auth::user()->company_id)->where('email', $companyEmail)->first();

            if (!$settings) {
                return response()->json(['success' => false, 'message' => __('Phishing settings not found')], 404);
            }

            PhishSetting::where('company_id', Auth::user()->company_id)->where('email', $companyEmail)
                ->update(['phish_results_visible' => $request->phish_results_visible]);

            return response()->json(['success' => true, 'message' => __('Phishing results updated successfully')], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateRiskInformation(Request $request)
    {
        try {
            $request->validate([
                'risk_information' => 'required|boolean',
            ]);
            $companyEmail = Auth::user()->email;
            $settings = PhishSetting::where('company_id', Auth::user()->company_id)->where('email', $companyEmail)->first();

            if (!$settings) {
                return response()->json(['success' => false, 'message' => __('Phishing settings not found')], 404);
            }

            PhishSetting::where('company_id', Auth::user()->company_id)->where('email', $companyEmail)
                ->update(['risk_information' => $request->risk_information]);

            return response()->json(['success' => true, 'message' => __('Risk information visibility updated successfully')], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

public function updateSurvey(Request $request)
{
    try {
        // Validate input
        $request->validate([
            'content_survey' => 'required|boolean',
            'survey_questions' => 'required_if:content_survey,true|array',
        ], [
            'survey_questions.required_if' => 'Survey questions are required.',
        ]);

        $companyId = Auth::user()->company_id;
        $email = Auth::user()->email;

        // Fetch existing setting
        $surveySetting = TrainingSetting::where('company_id', $companyId)->first();

        if (!$surveySetting) {
            return response()->json([
                'success' => false,
                'message' => __('Survey setting not found for this company.')
            ], 404);
        }

        // Update fields
        $surveySetting->content_survey = $request->content_survey;
        $surveySetting->email = $email;

        if ($request->content_survey) {
            $surveySetting->survey_questions = $request->survey_questions; // array
            $message = __('Survey enabled successfully');
        } else {
            $surveySetting->survey_questions = null;
            $message = __('Survey disabled successfully');
        }

        $surveySetting->save();

        return response()->json([
            'success' => true,
            'message' => $message,
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => $e->errors()
        ], 422);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => "Something went wrong: " . $e->getMessage()
        ], 500);
    }
}


public function getNotificationLanguages(Request $request)
{
    try {
        $companyId = Auth::user()->company_id;

        $trainingSetting = TrainingSetting::where('company_id', $companyId)->first();

        if (!$trainingSetting) {
            return response()->json([
                'success' => false,
                'message' => __('Training settings not found')
            ]);
        }

         if ($request->has('localized_notification')) {
            $trainingSetting->localized_notification = $request->localized_notification;
            $trainingSetting->save();
        }

          $message = $trainingSetting->localized_notification == 1
            ? __(' localized  Notification enabled successfully')
            : __('localized  Notification disabled successfully');

        return response()->json([
            'success' => true,
            'message' => $message,
            'localized_notification' => $trainingSetting->localized_notification,
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Server Error: '.$e->getMessage()
        ], 500);
    }
}



public function updateHelpRedirect(Request $request)
{
    try {

        $request->validate([
            'help_redirect_to' => 'required|string|max:255'
        ]);

        $value = $request->help_redirect_to;

        // Accept only email or valid http/https URL
        $isEmail = filter_var($value, FILTER_VALIDATE_EMAIL);
        $isUrl   = filter_var($value, FILTER_VALIDATE_URL) && 
                   (str_starts_with($value, 'http://') || str_starts_with($value, 'https://'));

        if (!$isEmail && !$isUrl) {
            return response()->json([
                'success' => false,
                'message' => __('Please enter a valid email or URL')
            ], 422);
        }

        $companyId = Auth::user()->company_id;

        $trainingSetting = TrainingSetting::where('company_id', $companyId)->first();

        if (!$trainingSetting) {
            return response()->json([
                'success' => false,
                'message' => __('Training settings not found')
            ], 404);
        }

        $trainingSetting->help_redirect_to = $value;
        $trainingSetting->save();

        return response()->json([
            'success' => true,
            'message' => __('Help redirect updated successfully'),
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Server Error: '.$e->getMessage()
        ], 500);
    }
}

public function updateTourPrompt(Request $request)
    {
        try {
            $request->validate([
                'tour_prompt' => 'required|boolean',
            ]);

            $companySettings = CompanySettings::where('company_id', Auth::user()->company_id)->first();

            if (!$companySettings) {
                return response()->json([
                    'success' => false,
                    'message' => __('Company not found')
                ], 404);
            }

            // Update tour_prompt
            $companySettings->tour_prompt = $request->tour_prompt;
             $companySettings->save();

            $message =  $companySettings->tour_prompt ? __('Tour prompt enabled successfully') : __('Tour prompt disabled successfully');

            return response()->json([
                'success' => true,
                'message' => $message,
                'tour_prompt' => $companySettings->tour_prompt
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server Error: '.$e->getMessage()
            ], 500);
        }
    }

}
