<?php

namespace App\Services;

use App\Models\TrainingModule;
use Illuminate\Support\Facades\Http;


class TranslationService
{

    public function translateTraining(TrainingModule $trainingModule, string $lang)
    {
        if ($trainingModule->training_type === 'gamified') {
            $trainingData = $trainingModule->json_quiz;

            list($flatList, $fieldMap) = $this->extractTranslatableFieldsGamified($trainingData);
        } else if ($trainingModule->training_type === 'static_training') {
            $trainingData = $trainingModule->json_quiz;
            list($flatList, $fieldMap) = $this->extractTranslatableStrings($trainingData);
        } else if($lang === 'en') {
            return $trainingModule;
        }else {
            return $trainingModule;
        }


        $response = Http::withHeaders([
            'Authorization' => env('VANEX_API_KEY'),
            'Accept' => 'application/json',
        ])->post('https://api-b2b.backenster.com/b1/api/v3/translate', [
            'platform' => 'api',
            'from' => 'en',
            'to' => $lang,
            'data' => $flatList,
            'enableTransliteration' => false,
        ]);

        if ($response->failed()) {
            \Log::error('Translation API request failed', ['response' => $response->body()]);
            return $trainingModule;
        }

        $translated = $response->json()['result'] ?? [];

        if ($trainingModule->training_type === 'gamified') {
            $translatedQuiz = $this->rebuildGamifiedTrainingData($trainingData, $fieldMap, $translated);
            $trainingModule->json_quiz = $translatedQuiz;
        } else if ($trainingModule->training_type === 'static_training') {
            $translatedQuiz = $this->rebuildTrainingData($trainingData, $fieldMap, $translated);
            $trainingModule->json_quiz = $translatedQuiz;
        }

        return $trainingModule;
    }

    

    public function translateOnlyQuiz(array $quizData, string $lang)
    {
        list($flatList, $fieldMap) = $this->extractTranslatableStrings($quizData);

        $response = Http::withHeaders([
            'Authorization' => env('VANEX_API_KEY'),
            'Accept' => 'application/json',
        ])->post('https://api-b2b.backenster.com/b1/api/v3/translate', [
            'platform' => 'api',
            'from' => 'en',
            'to' => $lang,
            'data' => $flatList,
            'enableTransliteration' => false,
        ]);

        if ($response->failed()) {
            \Log::error('Translation API request failed', ['response' => $response->body()]);
            return $quizData;
        }

        $translated = $response->json()['result'] ?? [];

        $translatedQuiz = $this->rebuildTrainingData($quizData, $fieldMap, $translated);

        return $translatedQuiz;
    }


    private function extractTranslatableStrings(array $data, array &$flatList = [], array &$pathMap = [], $path = [])
    {

        $translatableKeys = [
            "sTitle",
            "sContent",
            "question",
            "option1",
            "option2",
            "option3",
            "option4",
            "ansDesc"
        ];

        foreach ($data as $itemIndex => $item) {
            foreach ($item as $key => $value) {
                if (in_array($key, $translatableKeys) && is_string($value)) {
                    $flatList[] = $value;

                    // Store where to insert it back
                    $fieldMap[] = [
                        "itemIndex" => $itemIndex,
                        "key" => $key,
                    ];
                }
            }
        }

        return [$flatList, $fieldMap];
    }

    private function rebuildTrainingData(array $data, array $fieldMap, array $translated)
    {
        foreach ($fieldMap as $index => $map) {
            $itemIndex = $map['itemIndex'];
            $key = $map['key'];

            $data[$itemIndex][$key] = $translated[$index];
        }

        return $data;
    }

    private function extractTranslatableFieldsGamified(array $training, array &$flatList = [], array &$fieldMap = [])
    {
        if (!isset($training['questions'])) {
            return [$flatList, $fieldMap];
        }

        foreach ($training['questions'] as $qIndex => $questionBlock) {

            // Extract "question"
            if (isset($questionBlock['question']) && is_string($questionBlock['question'])) {
                $flatList[] = $questionBlock['question'];

                $fieldMap[] = [
                    'qIndex' => $qIndex,
                    'type'   => 'question',
                ];
            }

            // Extract each option
            if (isset($questionBlock['options']) && is_array($questionBlock['options'])) {
                foreach ($questionBlock['options'] as $optIndex => $optionText) {
                    if (is_string($optionText)) {
                        $flatList[] = $optionText;

                        $fieldMap[] = [
                            'qIndex'   => $qIndex,
                            'type'     => 'option',
                            'optIndex' => $optIndex,
                        ];
                    }
                }
            }
        }

        return [$flatList, $fieldMap];
    }

    private function rebuildGamifiedTrainingData(array $training, array $fieldMap, array $translatedList)
    {
        $tIndex = 0;

        foreach ($fieldMap as $map) {

            $qIndex = $map['qIndex'];

            if ($map['type'] === 'question') {
                $training['questions'][$qIndex]['question'] = $translatedList[$tIndex++];
            }

            if ($map['type'] === 'option') {
                $optIndex = $map['optIndex'];
                $training['questions'][$qIndex]['options'][$optIndex] = $translatedList[$tIndex++];
            }
        }

        return $training;
    }
}
