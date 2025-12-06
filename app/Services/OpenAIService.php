<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenAIService
{
    public static function generateText($prompt)
    {
        $apiKey = env('OPENAI_API_KEY');

        $response = Http::withToken($apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                "model" => "gpt-4o-mini", // Free fast model
                "messages" => [
                    ["role" => "user", "content" => $prompt]
                ]
            ]);

        if ($response->failed()) {
            return "ERROR: " . json_encode($response->json());
        }

        return $response->json()['choices'][0]['message']['content'] ?? null;
    }
}
