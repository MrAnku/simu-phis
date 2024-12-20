<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AiTrainingService;
use Illuminate\Support\Facades\Http;

class AiTrainingController extends Controller
{
    
   
    public function generateTraining($topic, $language)
    {
        
        $prompt = "
        Create a JSON array of quiz questions about $topic for phishing awareness training. 
        The JSON should include the following fields for each question:
        
        1. qtype (e.g., \"multipleChoice\" or \"trueFalse\")
        2. question (the text of the question)
        3. option1, option2, option3, option4 (for multiple-choice questions)
        4. correctOption (e.g., \"option2\")
        5. ansDesc (a detailed explanation of the correct answer)
        
        Ensure the questions are clear, concise, and relevant to the topic of $topic. Include at least one true/false question.
        Translate the questions, answers, and explanation into $language.
        ";

        $response = Http::withOptions(['verify' => false])->withHeaders([
            'Authorization' => 'Bearer ' . env("OPENAI_API_KEY"),
        ])->post('https://api.openai.com/v1/completions', [
            'model' => 'gpt-3.5-turbo-instruct',
            'prompt' => $prompt,
            'max_tokens' => 1500,
            'temperature' => 0.7,
        ]);

        if ($response->failed()) {

            log_action("Failed to generate AI Training on topic {$topic} in language {$language}", 'learner', 'learner');

            return [
                'error' => 'Failed to generate a response from OpenAI.',
                'status' => $response->status(),
                'body' => $response->body(),
            ];
        }

        $generatedText = $response->json('choices.0.text');

        // Attempt to decode JSON from the response
        $quiz = json_decode($generatedText, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Failed to generate valid JSON. Please refine the prompt.'];
        }

        log_action("AI Training generated on topic: {$topic} in language: {$language}", 'learner', 'learner');

        return response()->json($quiz);
    }
}
