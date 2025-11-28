<?php

namespace App\Services;

use App\Models\TrainingModule;
use GrokPHP\Client\Enums\Model;
use GrokPHP\Laravel\Facades\GrokAI;
use GrokPHP\Client\Config\ChatOptions;
use Exception;
use GrokPHP\Client\Exceptions\GrokException;

class TranslationService
{
    public function translateTraining(TrainingModule $trainingModule, string $targetLanguage): array
    {
        $originalJson = $trainingModule->json_quiz;
        $json = json_encode($originalJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $prompt = "Translate ONLY the text values in this JSON into {$targetLanguage}.
Do NOT translate JSON keys.
Do NOT change the structure at all.
Do NOT add any explanations, comments, or markdown.
Return ONLY the valid JSON object, nothing else.

JSON to translate:
{$json}";

        try {
            $response = GrokAI::chat(
                messages: [
                    [
                        'role'    => 'system',
                        'content' => 'You are a precise JSON-to-JSON translation engine. Always respond with exactly one valid JSON object and nothing else.'
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt
                    ]
                ],
                options: new ChatOptions(
                    model: Model::GROK_BETA,  // Fixed model
                    temperature: 0.0,
                )
            );

            $raw = trim($response->content());
            $jsonString = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $raw);
            $translated = json_decode($jsonString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON in AI response: ' . json_last_error_msg());
            }

            return $translated;

        } catch (GrokException $e) {
            // Specific handling for Grok errors (post-update, this won't TypeError)
            \Log::error('Grok API Error during translation', [
                'target_language' => $targetLanguage,
                'module_id' => $trainingModule->id,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'original_json_size' => strlen($json)
            ]);
            throw new Exception("Translation failed: {$e->getMessage()} (Check API key/limits)");

        } catch (Exception $e) {
            \Log::error('Translation service error', [
                'target_language' => $targetLanguage,
                'module_id' => $trainingModule->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}