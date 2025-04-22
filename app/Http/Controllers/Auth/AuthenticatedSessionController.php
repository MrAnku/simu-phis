<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $cookie = cookie('jwt', $token, 60*24);
        $user = Auth::user();
        $company_settings = Settings::where('company_id', $user->company_id)->first();
        if ($company_settings->mfa == 1) {
            // Store the user ID in the session and logout
            session(['mfa_user_id' => $user->id]);
            Auth::logout();
            return response()->json([
                "MFA" => true,
                'token' => $token,
                'company' => $user,
                "success" => false
            ])->withCookie($cookie);
            // throw ValidationException::withMessages([
            //     'mfa' => 'Multi-factor authentication is required.',
            // ])->redirectTo(route('mfa.enter'));
        }
        return response()->json([
            'token' => $token,
            'company' => Auth::user(),
            "success" => true,
            "MFA" => false,
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
}
