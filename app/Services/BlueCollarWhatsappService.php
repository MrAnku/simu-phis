<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BlueCollarWhatsappService
{

   public function sendSessionRegenerate($sessionRegenerateData)
    {
        $access_token = $sessionRegenerateData['access_token'];
        $phone_number_id = $sessionRegenerateData['phone_number_id'];
        $whatsapp_url = "https://graph.facebook.com/v22.0/{$phone_number_id}/messages";
        $token = encrypt($sessionRegenerateData['user_whatsapp']);

        $whatsapp_data = [
            "messaging_product" => "whatsapp",
            "to" => $sessionRegenerateData['user_whatsapp'],
            "type" => "template",
            "template" => [
                "name" => "session_regenerate",
                "language" => ["code" => "en"],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $sessionRegenerateData['user_name']],
                            ["type" => "text", "text" => 'Session Successfully Reinitialized!'],
                            ["type" => "text", "text" => $sessionRegenerateData['learn_domain'] . '/blue-collar-training-dashboard/' . $token],
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
