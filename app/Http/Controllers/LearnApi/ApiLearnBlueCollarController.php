<?php

namespace App\Http\Controllers\LearnApi;

use App\Http\Controllers\Controller;
use App\Models\BlueCollarEmployee;
use App\Models\BlueCollarTrainingUser;
use App\Services\BlueCollarWhatsappService;
use App\Services\CheckWhitelabelService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class ApiLearnBlueCollarController extends Controller
{
    public function createNewToken(Request $request)
    {
        try {
            $request->validate(['user_whatsapp' => 'required|integer']);

            $hasTraining = BlueCollarTrainingUser::where('user_whatsapp', $request->user_whatsapp)->first();

            // $hasPolicy = AssignedPolicy::where('user_email', $request->email)->first();

            if (!$hasTraining) {
                return response()->json([
                    'success' => false,
                    'message' => 'No training has been assigned to this whatsapp number.'
                ], 422);
            }

            // delete old generated tokens from db
            DB::table('blue_collar_learner_login_sessions')->where('whatsapp_number', $request->user_whatsapp)->delete();

            // Encrypt user_whatsapp to generate token
            $token = encrypt($request->user_whatsapp);
            if ($hasTraining) {
                $companyId = $hasTraining->company_id;
            }
            // if ($hasPolicy) {
            //     $companyId = $hasPolicy->company_id;
            // }

            $isWhitelabeled = new CheckWhitelabelService($companyId);
            if ($isWhitelabeled->isCompanyWhitelabeled()) {
                $whitelabelData = $isWhitelabeled->getWhiteLabelData();
                $learn_domain = "https://" . $whitelabelData->learn_domain;
                // $isWhitelabeled->updateSmtpConfig();
                $companyName = $whitelabelData->company_name;
                // $companyDarkLogo = env('CLOUDFRONT_URL') . $whitelabelData->dark_logo;
                $whatsappConfig = $isWhitelabeled->geṭWhatsappConfig();
                $access_token = $whatsappConfig->access_token;
                $phone_number_id = $whatsappConfig->from_phone_id;
            } else {
                $learn_domain = env('SIMUPHISH_LEARNING_URL');
                $companyName = env('APP_NAME');
                // $companyDarkLogo = env('CLOUDFRONT_URL') . '/assets/images/simu-logo-dark.png';
                $access_token = env('WHATSAPP_CLOUD_API_TOKEN');
                $phone_number_id = env('WHATSAPP_CLOUD_API_PHONE_NUMBER_ID');
            }

            // Insert new record into the database
            $inserted = DB::table('blue_collar_learner_login_sessions')->insert([
                'token' => $token,
                'whatsapp_number' => $request->user_whatsapp,
                'expiry' => now()->addHours(24),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Check if the record was inserted successfully
            if (!$inserted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create token'
                ], 422);
            }

            $sessionRegenerateData = [
                'learn_domain' => $learn_domain,
                'company_name' => $companyName,
                'access_token' => $access_token,
                'phone_number_id' => $phone_number_id,
                'user_whatsapp' => $request->user_whatsapp,
                'user_name' => BlueCollarEmployee::where('whatsapp', $request->user_whatsapp)->value('user_name'),
            ];

            $whatsappService = new BlueCollarWhatsappService();
            $whatsapp_response = $whatsappService->sendSessionRegenerate($sessionRegenerateData);

            if ($whatsapp_response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Session regenerated successfully'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send WhatsApp message'
                ], 422);
            }
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function loginWithToken(Request $request)
    {
        try {
            $token = $request->query('token');

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token is required!'
                ], 422);
            }

            $session = DB::table('blue_collar_learner_login_sessions')->where('token', $token)->orderBy('created_at', 'desc') // Ensure the latest session is checked
                ->first();
            if (!$session || now()->greaterThan(Carbon::parse($session->expiry))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your training session has expired!'
                ], 422);
            }

            // Decrypt the email
            $userWhatsapp = decrypt($session->token);

            Session::put('token', $token);

            $employeeType = 'bluecollar';
            $userName = BlueCollarEmployee::where('whatsapp', $userWhatsapp)->value('user_name');


            return response()->json([
                'success' => true,
                'data' => [
                    'user_whatsapp' => $userWhatsapp,
                    'employee_type' => $employeeType,
                    'user_name' => $userName,
                ],
                'message' => 'You can Login now'
            ], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
