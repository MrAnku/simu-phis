<?php

namespace App\Services\Whatsapp;

class WhatsappTemplateService
{
    public static function sessionRegenerateTemplate($to, $userName, $learnDomain, $token): array
    {
        return [
            "messaging_product" => "whatsapp",
            "to" => $to,
            "type" => "template",
            "template" => [
                "name" => "session_regenerate",
                "language" => ["code" => "en"],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $userName],
                            ["type" => "text", "text" => 'Session Successfully Reinitialized!'],
                            ["type" => "text", "text" => $learnDomain . '/blue-collar-training-dashboard/' . $token],
                        ]
                    ]
                ]
            ]
        ];
    }

    public static function trainingCompleteTemplate($to, $userName, $trainingName, $completionData): array
    {
        return [
            "messaging_product" => "whatsapp",
            "to" => $to,
            "type" => "template",
            "template" => [
                "name" => "training_complete",
                "language" => ["code" => "en"],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $userName],
                            ["type" => "text", "text" => $trainingName],
                            ["type" => "text", "text" => $completionData],
                        ]
                    ]
                ]
            ]
        ];
    }

    public static function trainingMessage($to, $userName, $trainings, $learnDomain, $token): array
    {
        return [
            "messaging_product" => "whatsapp",
            "to" => $to, // Replace with actual user phone number
            "type" => "template",
            "template" => [
                "name" => "training_message",
                "language" => ["code" => "en"],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $userName],
                            ["type" => "text", "text" => $trainings],
                            ["type" => "text", "text" => $learnDomain . "/blue-collar-training-dashboard/" . $token]
                        ]
                    ]
                ]
            ]
        ];
    }
}
