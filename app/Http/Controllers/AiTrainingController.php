<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AiTrainingService;
use Illuminate\Support\Facades\Http;

class AiTrainingController extends Controller
{


    public function generateTraining($topic)
    {
        try {
            $prompt = "
        Create a JSON array of quiz questions about $topic for phishing awareness training. 
        The JSON should include the following fields for each question:
        
        1. qtype (e.g., \"multipleChoice\" or \"trueFalse\")
        2. question (the text of the question)
        3. option1, option2, option3, option4 (for multiple-choice questions)
        4. correctOption (e.g., \"option2\")
        5. ansDesc (a detailed explanation of the correct answer)
        
        Ensure the questions are clear, concise, and relevant to the topic of $topic. Include at least one true/false question.
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

                log_action("Failed to generate AI Training on topic {$topic}", 'learner', 'learner');

                return response()->json([
                    'status' => 0,
                    'msg' => $response->body(),
                ]);
            }

            $generatedText = $response->json('choices.0.text');

            // Attempt to decode JSON from the response
            $quiz = json_decode($generatedText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {

                log_action("Failed to decode JSON from AI Training on topic {$topic}", 'learner', 'learner');

                return response()->json([
                    'status' => 0,
                    'msg' => 'Failed to decode JSON from AI response',
                ]);
            }

            log_action("AI Training generated on topic: {$topic}", 'learner', 'learner');

            return response()->json([
                'status' => 1,
                'quiz' => $quiz,
            ]);
        } catch (\Exception $e) {

            log_action("Failed to generate AI Training on topic {$topic}", 'learner', 'learner');

            return response()->json([
                'status' => 0,
                'msg' => $e->getMessage(),
            ]);
        }
    }

    public function translateAiTraining(Request $request)
    {

        if ($request->lang !== 'en') {

            // $translatedArray = translateArrayValues($request->quiz, $request->lang);
            // $translatedQuizJson = json_encode($translatedArray, JSON_UNESCAPED_UNICODE);

            //translation using ai
            $quiz = json_encode($request->quiz, JSON_UNESCAPED_UNICODE);
            $quiz = translateQuizUsingAi($quiz, $request->lang);
            // $translatedQuizJson = json_encode($quiz, JSON_UNESCAPED_UNICODE);

            return response()->json(['status' => 1, 'quiz' => $quiz]);
        }

        return response()->json(['status' => 0, 'msg' => 'Unable to translate in english']);

    }
}
