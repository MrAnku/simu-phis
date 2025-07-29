<?php

namespace App\Http\Controllers\LearnApi;

use App\Http\Controllers\Controller;
use App\Models\BlueCollarTrainingUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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

            // $isWhitelabeled = new CheckWhitelabelService($companyId);
            // if ($isWhitelabeled->isCompanyWhitelabeled()) {
            //     $whitelabelData = $isWhitelabeled->getWhiteLabelData();
            //     $learn_domain = "https://" . $whitelabelData->learn_domain;
            //     $isWhitelabeled->updateSmtpConfig();
            //     $companyName = $whitelabelData->company_name;
            //     $companyDarkLogo = env('CLOUDFRONT_URL') . $whitelabelData->dark_logo;
            // } else {
            //     $learn_domain = env('SIMUPHISH_LEARNING_URL');
            //     $companyName = env('APP_NAME');
            //     $companyDarkLogo = env('CLOUDFRONT_URL') . '/assets/images/simu-logo-dark.png';
            // }



            $learning_dashboard_link = env('SIMUPHISH_LEARNING_URL') . '/training-dashboard/' . $token;

            // Insert new record into the database
            $inserted = DB::table('blue_collar_learner_login_sessions')->insert([
                'whatsapp_number' => $request->user_whatsapp,
                'token' => $token,
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

            // WhatsApp API Configuration
            $access_token = env('WHATSAPP_CLOUD_API_TOKEN');
            $phone_number_id = env('WHATSAPP_CLOUD_API_PHONE_NUMBER_ID');
            $whatsapp_url = "https://graph.facebook.com/v22.0/{$phone_number_id}/messages";

            $token = encrypt($request->user_whatsapp);


            $whatsapp_data = [
                "messaging_product" => "whatsapp",
                "to" => $request->user_whatsapp,
                "type" => "template",
                "template" => [
                    "name" => "session_regenerate",
                    "language" => ["code" => "en"], // or "en_US" if that's what you used
                    "components" => [
                        [
                            "type" => "body",
                            "parameters" => [
                                ["type" => "text", "text" => 'Sana'], // {{1}} - user name
                                ["type" => "text", "text" => 'Your training session has been regenerated.'], // {{2}} - custom message
                                ["type" => "text", "text" => env('SIMUPHISH_LEARNING_URL') . '/training-dashboard/' . $token], // {{3}} - training link
                            ]
                        ]
                    ]
                ]
            ];


            // Send WhatsApp message

            $whatsapp_response = Http::withHeaders([
                "Authorization" => "Bearer {$access_token}",
                "Content-Type" => "application/json"
            ])->withOptions([
                'verify' => false
            ])->post($whatsapp_url, $whatsapp_data);


            if ($whatsapp_response->successful()) {
                // log_action("Bluecolar training Reminder Sent | Training {$campaign->trainingData->name} assigned to {$campaign->user_phone}.", 'employee', 'employee');
                return response()->json(['success' => __('Session regenerate sent via WhatsApp')]);
            } else {
                return response()->json([
                    'error' => __('Failed to send WhatsApp message'),
                    'status' => $whatsapp_response->status(),
                    'response' => $whatsapp_response->body()
                ], 500);
            }







            // // Prepare email data
            // $mailData = [
            //     'learning_site' => $learning_dashboard_link,
            //     'company_name' => $companyName,
            //     'company_dark_logo' => $companyDarkLogo,
            //     'company_id' => $companyId
            // ];

            // $trainingModules = TrainingModule::where('company_id', 'default')->inRandomOrder()->take(5)->get();
            // // Send email
            // Mail::to($request->email)->send(new LearnerSessionRegenerateMail($mailData, $trainingModules));

            // Return success response
            // return response()->json(['success' => true, 'message' => 'Mail sent successfully'], 200);
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
