<?php

namespace App\Services;

use App\Models\BlueCollarEmployee;
use App\Models\BlueCollarLearnerLoginSession;
use Illuminate\Support\Facades\Http;

class BlueCollarWhatsappService
{
    protected $learn_domain;
    protected $companyName;
    protected $access_token;
    protected $phone_number_id;

    public function __construct($companyId)
    {
        $isWhitelabeled = new CheckWhitelabelService($companyId);
        if ($isWhitelabeled->isCompanyWhitelabeled() && $isWhitelabeled->geṭWhatsappConfig()) {
            $whitelabelData = $isWhitelabeled->getWhiteLabelData();
            $this->learn_domain = "https://" . $whitelabelData->learn_domain;
            $this->companyName = $whitelabelData->company_name;
            $whatsappConfig = $isWhitelabeled->geṭWhatsappConfig();
            $this->access_token = $whatsappConfig->access_token;
            $this->phone_number_id = $whatsappConfig->from_phone_id;
        } else {
            $this->learn_domain = env('SIMUPHISH_LEARNING_URL');
            $this->companyName = env('APP_NAME');
            $this->access_token = env('WHATSAPP_CLOUD_API_TOKEN');
            $this->phone_number_id = env('WHATSAPP_CLOUD_API_PHONE_NUMBER_ID');
        }
    }
    public function sendSessionRegenerate($user_whatsapp)
    {
        $token = encrypt($user_whatsapp);
        $user_name = BlueCollarEmployee::where('whatsapp', $user_whatsapp)->value('user_name');

        $whatsapp_data = [
            "messaging_product" => "whatsapp",
            "to" => $user_whatsapp,
            "type" => "template",
            "template" => [
                "name" => "session_regenerate",
                "language" => ["code" => "en"],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $user_name],
                            ["type" => "text", "text" => 'Session Successfully Reinitialized!'],
                            ["type" => "text", "text" => $this->learn_domain . '/blue-collar-training-dashboard/' . $token],
                        ]
                    ]
                ]
            ]
        ];

        $whatsapp_url = "https://graph.facebook.com/v22.0/{$this->phone_number_id}/messages";

        $response = Http::withHeaders([
            "Authorization" => "Bearer {$this->access_token}",
            "Content-Type" => "application/json"
        ])->withOptions([
            'verify' => false
        ])->post($whatsapp_url, $whatsapp_data);

        return $response;
    }

    public function sendTrainingComplete($data)
    {
        $token = encrypt($data['user_whatsapp']);

        $whatsapp_data = [
            "messaging_product" => "whatsapp",
            "to" => $data['user_whatsapp'],
            "type" => "template",
            "template" => [
                "name" => "training_complete",
                "language" => ["code" => "en"],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $data['user_name']],
                            ["type" => "text", "text" => $data['training_name']],
                            ["type" => "text", "text" => $data['completion_date']],
                        ]
                    ]
                ]
            ]
        ];

        $whatsapp_url = "https://graph.facebook.com/v22.0/{$this->phone_number_id}/messages";

        $response = Http::withHeaders([
            "Authorization" => "Bearer {$this->access_token}",
            "Content-Type" => "application/json"
        ])->withOptions([
            'verify' => false
        ])->post($whatsapp_url, $whatsapp_data);

        return $response;
    }

    public function sendTrainingAssign($data)
    {
        $token = encrypt($data->user_phone);

        $whatsapp_data = [
            "messaging_product" => "whatsapp",
            "to" => $data->user_phone, // Replace with actual user phone number
            "type" => "template",
            "template" => [
                "name" => "training_message",
                "language" => ["code" => "en"],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $data->user_name],
                            ["type" => "text", "text" => $data->training_names],
                            ["type" => "text", "text" => $this->learn_domain . "/start-blue-collar-training/" . $token]
                        ]
                    ]
                ]
            ]
        ];

        $whatsapp_url = "https://graph.facebook.com/v22.0/{$this->phone_number_id}/messages";

        // Insert new record into the database

        $inserted = BlueCollarLearnerLoginSession::insert([
            'whatsapp_number' => $data->user_phone,
            'token' => $token,
            'expiry' => now()->addHours(24),
        ]);

        // Check if the record was inserted successfully
        if (!$inserted) {
           return null;
        }

        $whatsapp_response = Http::withHeaders([
            "Authorization" => "Bearer {$this->access_token}",
            "Content-Type" => "application/json"
        ])->withOptions([
            'verify' => false
        ])->post($whatsapp_url, $whatsapp_data);

        return $whatsapp_response;
    }
}
