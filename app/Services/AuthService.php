<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService
{
    protected $companyEmail;
    protected $password = null;
    protected $company;
    protected $criteriaMsg;

    public function __construct($companyEmail, $password = null)
    {
        $this->companyEmail = $companyEmail;
        $this->password = $password;
    }

    public function companyExists(): bool
    {
        $this->company = Company::where('email', $this->companyEmail)->first();
        return $this->company !== null;
    }

    public function isApproved(): bool
    {
        return $this->company?->approved == 1;
    }

    public function isInService(): bool
    {
        return $this->company?->service_status == 1;
    }

    public function licenseIsValid(): bool
    {
        return $this->company?->license?->expiry > now();
    }

    public function hasEnabledMfa(): bool
    {
        return $this->company?->company_settings?->mfa == 1;
    }

    public function loginUsingSSO(): JsonResponse
    {
        if (!$this->criteriaFulfilled()) {
            return response()->json([
                'message' => $this->criteriaMsg,
                'success' => false
            ], 422);
        }

        if ($this->hasEnabledMfa()) {
            return response()->json([
                "mfa" => true,
                'company' => [
                    'email' => $this->companyEmail,
                ],
                "success" => true
            ]);
        }

        return $this->getLoginResponse();
    }

    private function criteriaFulfilled(): bool
    {
        if (!$this->companyExists()) {
            $this->criteriaMsg = 'Account not found';
            return false;
        }

        if (!$this->isApproved()) {
            $this->criteriaMsg = 'Your account is not approved yet. Please contact your service provider.';
            return false;
        }

        if (!$this->isInService()) {
            $this->criteriaMsg = 'Your services are on hold. Please contact your service provider.';
            return false;
        }

        if (!$this->licenseIsValid()) {
            $this->criteriaMsg = 'Your License has been Expired';
            return false;
        }

        return true;
    }


    public function loginUsingCredentials(): JsonResponse
    {
        if (!$this->criteriaFulfilled()) {
            return response()->json([
                'message' => $this->criteriaMsg,
                'success' => false
            ], 422);
        }

        if ($this->hasEnabledMfa()) {
            return response()->json([
                "mfa" => true,
                'company' => [
                    'email' => $this->companyEmail,
                ],
                "success" => true
            ]);
        }

        return $this->getLoginResponse();
    }

    public function loginUsingMfa(): JsonResponse
    {
        if (!$this->criteriaFulfilled()) {
            return response()->json([
                'message' => $this->criteriaMsg,
                'success' => false
            ], 422);
        }

        return $this->getLoginResponse();
    }

    public function loginCompany($using): JsonResponse
    {
        if ($using == 'sso') {
            return $this->loginUsingSSO();
        }
        if ($using == 'password') {
            return $this->loginUsingCredentials();
        }
        if ($using == 'mfa') {
            return $this->loginUsingMfa();
        }

        return response()->json([
            'message' => 'Invalid login method',
            'success' => false
        ], 400);
    }

    public function getLoginResponse(): JsonResponse
    {
        // Get enabled features
        $enabledFeatures = $this->company->enabled_feature ?? 'null';

        if ($this->password == null) {
            Auth::login($this->company);
            $token = JWTAuth::fromUser($this->company);
        } else {
            $token = JWTAuth::attempt(['email' => $this->companyEmail, 'password' => $this->password]);
        }


        $cookie = cookie('jwt', $token, env('JWT_TTL', 1440));
        $enabledFeatureCookie = cookie('enabled_feature', $enabledFeatures, env('JWT_TTL', 1440));

        return response()->json([
            'token' => $token,
            'success' => true,
            'company' => $this->company,
            'message' => 'Logged in successfully',
        ])->withCookie($cookie)->withCookie($enabledFeatureCookie);
    }
}
