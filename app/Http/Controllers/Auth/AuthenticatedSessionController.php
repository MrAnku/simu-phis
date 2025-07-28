<?php

namespace App\Http\Controllers\Auth;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Models\CompanyLicense;
use App\Models\CompanySettings;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\Otp;
use App\Services\CheckWhitelabelService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request and return JWT.
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Check if the user is approved
        $approved = $this->isApproved();
        if (!$approved) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Your account is not approved yet. Please contact your service provider.',
            ], 422);
        }

        // Check if the service status is approved
        $serviceStatusApproved = $this->isServiceStatusApproved();
        if (!$serviceStatusApproved) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Your services are on hold. Please contact your service provider.',
            ], 422);
        }

        // check License Expiry
        $licenseExpired = $this->checkLicenseExpiry();
        if ($licenseExpired) {
            return response()->json(['success' => false, 'message' => __('Your License has beeen Expired')], 422);
        }

        $mfaEnabled = $this->checkMfa();
        if ($mfaEnabled) {

            Auth::logout(Auth::user());
            return response()->json([
                "mfa" => true,
                'company' => Auth::user(),
                "success" => true
            ]);
        }
        $enabledFeatures = Company::where('company_id', Auth::user()->company_id)->value('enabled_feature');

        if (!$enabledFeatures) {
            $enabledFeatures = 'null'; // Default to null if no features are enabled
        }

        $cookie = cookie('jwt', $token, env('JWT_TTL', 1440));
        $enabledFeatureCookie = cookie('enabled_feature', $enabledFeatures, env('JWT_TTL', 1440));

        return response()->json([
            'token' => $token,
            'company' => Auth::user(),
            "success" => true,
            "message" => "Logged in successfully",
            "mfa" => false,
        ])->withCookie($cookie)
            ->withCookie($enabledFeatureCookie);
    }

    public function tokenCheck(Request $request): JsonResponse
    {
        //has valid token
        $token = $request->token;
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token is required',
            ], 422);
        }
        $validToken = Company::where('pass_create_token', $token)->where('password', null)->first();
        if (!$validToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Token or Token Expired',
            ], 422);
        }
        return response()->json([
            'success' => true,
            'message' => 'Valid Token'
        ]);
    }

    public function createPassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'token' => 'required',
                'password' => 'required|confirmed|min:8',
            ]);

            $company = Company::where('pass_create_token', $request->token)->first();
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session expired or invalid token',
                ], 404);
            }

            $company->password = bcrypt($request->password);
            $company->pass_create_token = null;
            $company->save();

            return response()->json([
                'success' => true,
                'message' => 'Password created successfully',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout and invalidate JWT token.
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            $cookie = cookie('jwt', null, -1);
            $enabledFeatureCookie = cookie('enabled_feature', null, -1);
            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ])->withCookie($cookie)
                ->withCookie($enabledFeatureCookie);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout',
            ], 500);
        }
    }

    /**
     * Get authenticated user details.
     */
    public function me(): JsonResponse
    {
        return response()->json(Auth::user());
    }

    public function forgotPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:company,email',
            ]);

            $company = Company::where('email', $request->email)->first();

            $companyId = $company->company_id;

            $recordExists = Otp::where('email', $request->email)->exists();

            $otp = rand(100000, 999999);

            if ($recordExists) {
                Otp::where('email', $request->email)->update([
                    'otp' => $otp,
                    'otp_expiry' => now()->addMinutes(10)
                ]);
            } else {
                Otp::create([
                    'email' => $request->email,
                    'company_id' => $companyId,
                    'otp' => $otp,
                    'otp_expiry' => now()->addMinutes(10)
                ]);
            }

            $isWhitelabeled = new CheckWhitelabelService($companyId);
            if ($isWhitelabeled->isCompanyWhitelabeled()) {
                $whitelabelData = $isWhitelabeled->getWhiteLabelData();
                $isWhitelabeled->updateSmtpConfig();
                $companyName = $whitelabelData->company_name;
                $companyDarkLogo = env('CLOUDFRONT_URL') . $whitelabelData->dark_logo;
            } else {
                $companyName = env('APP_NAME');
                $companyDarkLogo = env('CLOUDFRONT_URL') . '/assets/images/simu-logo-dark.png';
            }

            // Prepare email data
            $mailData = [
                'company_name' => $companyName,
                'company_dark_logo' => $companyDarkLogo,
                'email' => $request->email,
                'otp' => $otp,
                'full_name' => $company->full_name
            ];

            $mailSent = Mail::to($request->email)->send(new PasswordResetMail($mailData));

            if ($mailSent) {
                return response()->json([
                    'success' => true,
                    'message' => 'OTP has been sent successfully'
                ], 200);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function verifyOTP(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:company,email',
                'otp' => 'required|integer|digits:6'
            ]);

            $otp = Otp::where('email', $request->email)->first();

            if ($otp->otp != $request->otp) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP did not match'
                ], 422);
            }

            if ($otp->otp_expiry < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP Expired'
                ], 422);
            }

            if ($otp->otp == $request->otp) {
                if ($otp->otp_expiry > now()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'OTP verfied successfully'
                    ], 200);
                }
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:company,email',
                'password' => 'required|confirmed|min:8',
            ]);

            $updated = Company::where('email', $request->email)->update([
                'password' => bcrypt($request->password),
            ]);

            if ($updated) {
                return response()->json([
                    'success' => true,
                    'message' => 'Password changed successfully',
                ], 200);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
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
