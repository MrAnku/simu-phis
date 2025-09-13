<?php

use App\Models\TrainingModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $trainingModules = TrainingModule::where('company_id', 'default')->where('training_type', 'static_training')->get();
        foreach ($trainingModules as $module) {
            $quiz = $module->json_quiz;
            if ($quiz) {
                $quiz = json_decode($quiz, true);
                foreach ($quiz as &$qtype) {
                    if (isset($qtype['videoUrl'])) {
                        $qtype['videoUrl'] = str_replace(
                            'https://cdn.simuphish.com',
                            'https://simuphish.hel1.your-objectstorage.com/uploads/trainingModule',
                            $qtype['videoUrl']
                        );
                    }
                }
                $module->json_quiz = json_encode($quiz, JSON_UNESCAPED_SLASHES);
                $module->save();
            }
        }

        //for gamified
        $gamifiedTrainings = TrainingModule::where('company_id', 'default')->where('training_type', 'gamified')->get();
        foreach ($gamifiedTrainings as $module) {
            $quiz = $module->json_quiz;
            if ($quiz) {
                $quiz = json_decode($quiz, true);
                if (isset($quiz['videoUrl'])) {
                    $quiz['videoUrl'] = str_replace(
                        'https://cdn.simuphish.com',
                        'https://simuphish.hel1.your-objectstorage.com/uploads/trainingModule',
                        $quiz['videoUrl']
                    );
                }
                $module->json_quiz = json_encode($quiz, JSON_UNESCAPED_SLASHES);
                $module->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $trainingModules = TrainingModule::where('company_id', 'default')->where('training_type', 'static_training')->get();
        foreach ($trainingModules as $module) {
            $quiz = $module->json_quiz;
            if ($quiz) {
                $quiz = json_decode($quiz, true);
                foreach ($quiz as &$qtype) {
                    if (isset($qtype['videoUrl'])) {
                        $qtype['videoUrl'] = str_replace(
                            'https://simuphish.hel1.your-objectstorage.com/uploads/trainingModule',
                            'https://cdn.simuphish.com',
                            $qtype['videoUrl']
                        );
                    }
                }
                $module->json_quiz = json_encode($quiz, JSON_UNESCAPED_SLASHES);
                $module->save();
            }
        }

        //for gamified
        $gamifiedTrainings = TrainingModule::where('company_id', 'default')->where('training_type', 'gamified')->get();
        foreach ($gamifiedTrainings as $module) {
            $quiz = $module->json_quiz;
            if ($quiz) {
                $quiz = json_decode($quiz, true);
                if (isset($quiz['videoUrl'])) {
                    $quiz['videoUrl'] = str_replace(
                        'https://simuphish.hel1.your-objectstorage.com/uploads/trainingModule',
                        'https://cdn.simuphish.com',
                        $quiz['videoUrl']
                    );
                }
                $module->json_quiz = json_encode($quiz, JSON_UNESCAPED_SLASHES);
                $module->save();
            }
        }
    }
};
