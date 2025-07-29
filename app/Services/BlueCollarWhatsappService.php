<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BlueCollarWhatsappService
{

   public function sendSessionRegenerate($sessionRegenrateData)
    {
        $access_token = env('WHATSAPP_CLOUD_API_TOKEN');
        $phone_number_id = env('WHATSAPP_CLOUD_API_PHONE_NUMBER_ID');
        $whatsapp_url = "https://graph.facebook.com/v22.0/{$phone_number_id}/messages";
        $token = encrypt($sessionRegenrateData['user_whatsapp']);

        $whatsapp_data = [
            "messaging_product" => "whatsapp",
            "to" => $sessionRegenrateData['user_whatsapp'],
            "type" => "template",
            "template" => [
                "name" => "session_regenerate",
                "language" => ["code" => "en"],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $sessionRegenrateData['user_name']],
                            ["type" => "text", "text" => 'Your training session has been regenerated.'],
                            ["type" => "text", "text" => env('SIMUPHISH_LEARNING_URL') . '/training-dashboard/' . $token],
                        ]
                    ]
                ]
            ]
        ];

        $response = Http::withHeaders([
            "Authorization" => "Bearer {$access_token}",
            "Content-Type" => "application/json"
        ])->withOptions([
            'verify' => false
        ])->post($whatsapp_url, $whatsapp_data);

        return $response;
    }
}
