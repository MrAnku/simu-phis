<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TestController extends Controller
{
    public function translate(Request $request)
    {
        $apiKey = env('OPENAI_API_KEY');
        $apiEndpoint = "https://api.openai.com/v1/completions";
        $file = public_path($request->query('fileurl'));
        $fileContent = file_get_contents($file);

        // return response($fileContent, 200)->header('Content-Type', 'text/html');

        $prompt = "Translate the following email content to {$request->query('lang')}:\n\n{$fileContent}";

        $requestBody = [
            'model' => 'gpt-3.5-turbo-instruct',
            'prompt' => $prompt,
            'max_tokens' => 1500,
            'temperature' => 0.7,
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $apiKey,
        ])->post($apiEndpoint, $requestBody);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to fetch translation' . $response->body()], 500);
        }
        $responseData = $response->json();
        $translatedMailBody = $responseData['choices'][0]['text'] ?? null;

        return response($translatedMailBody, 200)->header('Content-Type', 'text/html');
    }
}
