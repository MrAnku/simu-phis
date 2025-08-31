<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Models\AssignedPolicy;
use App\Models\ScormAssignedUser;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\Http;
use App\Services\CheckWhitelabelService;

class SSOController extends Controller
{
    public function ssoValidate(Request $request)
    {
        // Validate the SSO request
        $accessToken = $request->query('access_token');
        $provider = $request->query('provider');

        if ($provider == 'google') {
            return $this->validateToken($accessToken, 'google');
        }

        if ($provider == 'outlook') {
            return $this->validateToken($accessToken, 'outlook');
        }
        return response()->json([
            'success' => false,
            'message' => 'Unsupported provider'
        ], 400);
    }

    private function validateToken($accessToken, $provider)
    {
        // Validate the access token with Google
        if ($provider == 'google') {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get('https://www.googleapis.com/oauth2/v3/userinfo');
        }

        // Validate the access token with Outlook
        if ($provider == 'outlook') {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get('https://graph.microsoft.com/v1.0/me');
        }

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid access token'
            ], 401);
        }

        $payload = $response->json();


        if ($provider == 'google') {
            $email = $payload['email'] ?? null;
        } elseif ($provider == 'outlook') {
            $email = $payload['mail'] ?? null; // Outlook uses 'mail' for email
        }

        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => 'Email not found in token'
            ], 400);
        }

        $authService = new AuthService($email);
        return $authService->loginCompany('sso');
        
    }

  


    public function ssoValidateLearner(Request $request)
    {
        // Validate the SSO request for learners
        $accessToken = $request->query('access_token');
        $provider = $request->query('provider');

        if ($provider == 'google') {
            return $this->validateLearnerToken($accessToken, 'google');
        }

        if ($provider == 'outlook') {
            return $this->validateLearnerToken($accessToken, 'outlook');
        }
        return response()->json([
            'success' => false,
            'message' => 'Unsupported provider'
        ], 400);
    }

    private function validateLearnerToken($accessToken, $provider)
    {
        try {

            // Validate the access token with Google
            if ($provider == 'google') {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                ])->get('https://www.googleapis.com/oauth2/v3/userinfo');
            }

            // Validate the access token with Outlook
            if ($provider == 'outlook') {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                ])->get('https://graph.microsoft.com/v1.0/me');
            }

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid access token'
                ], 401);
            }

            $payload = $response->json();


            if ($provider == 'google') {
                $email = $payload['email'] ?? null;
            } elseif ($provider == 'outlook') {
                $email = $payload['mail'] ?? null; // Outlook uses 'mail' for email
            }

            if (!$email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not found in token'
                ], 400);
            }

            // check if this email has any training assigned or any policy assigned
            $hasTraining = TrainingAssignedUser::where('user_email', $email)->first();
            $hasScormAssigned = ScormAssignedUser::where('user_email', $email)->first();
            $hasPolicy = AssignedPolicy::where('user_email', $email)->first();

            if (!$hasTraining && !$hasPolicy && !$hasScormAssigned) {
                return response()->json([
                    'success' => false,
                    'message' => 'No training or policy has been assigned to this email.'
                ], 422);
            }

            // delete old generated tokens from db
            DB::table('learnerloginsession')->where('email', $email)->delete();

            //create a session link
            $token = encrypt($email);
            if ($hasTraining) {
                $companyId = $hasTraining->company_id;
            }
            if ($hasPolicy) {
                $companyId = $hasPolicy->company_id;
            }
            if ($hasScormAssigned) {
                $companyId = $hasScormAssigned->company_id;
            }
            $isWhitelabeled = new CheckWhitelabelService($companyId);
            if ($isWhitelabeled->isCompanyWhitelabeled()) {
                $whitelabelData = $isWhitelabeled->getWhiteLabelData();
                $learn_domain = "https://" . $whitelabelData->learn_domain;
            } else {
                $learn_domain = env('SIMUPHISH_LEARNING_URL');
            }
            $learning_dashboard_link = $learn_domain . '/training-dashboard/' . $token;

            // Insert new record into the database
            DB::table('learnerloginsession')->insert([
                'email' => $email,
                'token' => $token,
                'expiry' => now()->addHours(24),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // return session link

            return response()->json([
                'success' => true,
                'message' => 'Learner validated successfully',
                'data' => [
                    'email' => $email,
                    'session_link' => $learning_dashboard_link,
                ]
            ]);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
