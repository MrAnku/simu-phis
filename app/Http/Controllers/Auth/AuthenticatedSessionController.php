<?php

namespace App\Http\Controllers\Auth;

use App\Models\Settings;
use Illuminate\Http\Request;
use App\Models\CompanyLicense;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
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

        $user = Auth::user();

        $company_license = CompanyLicense::where('company_id', $user->company_id)->first();

        // Check License Expiry
        if (now()->toDateString() > $company_license->expiry) {
            Auth::logout($user);
            return response()->json(['success' => false, 'message' => __('Your License has beeen Expired')], 422);
        }

        $company_settings = Settings::where('company_id', $user->company_id)->first();
        if ($company_settings->mfa == 1) {
            // Store the user ID in the session and logout
            // session(['mfa_user_id' => $user->id]);
            Auth::logout($user);
            return response()->json([
                "mfa" => true,
                'company' => $user,
                "success" => true
            ]);
        }
        $cookie = cookie('jwt', $token, 60 * 24);
        return response()->json([
            'token' => $token,
            'company' => Auth::user(),
            "success" => true,
            "message" => "Logged in successfully",
            "mfa" => false,
        ])->withCookie($cookie);
    }

    /**
     * Logout and invalidate JWT token.
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            $cookie = cookie('jwt', null, -1);
            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ])->withCookie($cookie);
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
                'email' => 'required|email',
            ]);

            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status == Password::RESET_LINK_SENT) {
                return response()->json([
                    'success' => true,
                    'message' => 'Password reset link sent to your email.',
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset link.',
            ], 500);
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
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|confirmed|min:8',
            ]);

            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user) use ($request) {
                    $user->forceFill([
                        'password' => bcrypt($request->password),
                    ])->save();
                }
            );

            if ($status == Password::PASSWORD_RESET) {
                return response()->json([
                    'success' => true,
                    'message' => 'Password has been reset successfully.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Token is invalid or expired.',
            ], 422);
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
}
