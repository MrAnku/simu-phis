<?php

use App\Http\Controllers\Api\ApiScormTrainingController;
use App\Http\Controllers\LearnApi\ApiLearnBlueCollarController;
use App\Http\Controllers\LearnApi\ApiLearnController;
use App\Http\Controllers\LearnApi\ApiLearnPolicyController;
use Illuminate\Support\Facades\Route;

Route::prefix('learn')->middleware('throttle:learner-limiter')->group(function () {
    Route::get('/login-with-token', [ApiLearnController::class, 'loginWithToken']);
    Route::post('/create-new-token', [ApiLearnController::class, 'createNewToken']);

    Route::middleware('checkLearnToken')->group(function () {
        Route::get('/dashboard/metrics', [ApiLearnController::class, 'getDashboardMetrics']);
        Route::get('/get-normal-emp-tranings', [ApiLearnController::class, 'getNormalEmpTranings']);
        Route::post('/update-training-score', [ApiLearnController::class, 'updateTrainingScore']);
        Route::put('/update-training-feedback', [ApiLearnController::class, 'updateTrainingFeedback']);
        Route::get('/fetch-normal-emp-scorm-trainings', [ApiLearnController::class, 'fetchNormalEmpScormTrainings']);
        Route::post('/download-training-certificate', [ApiLearnController::class, 'downloadTrainingCertificate']);
        Route::post('/download-scorm-certificate', [ApiLearnController::class, 'downloadScormCertificate']);
        Route::post('/update-scorm-training-score', [ApiLearnController::class, 'updateScormTrainingScore']);
        Route::put('/update-scorm-training-feedback', [ApiLearnController::class, 'updateScormTrainingFeedback']);
        Route::get('/fetch-score-board', [ApiLearnController::class, 'fetchScoreBoard']);
        Route::get('/fetch-leader-board', [ApiLearnController::class, 'fetchLeaderBoard']);
        Route::get('/fetch-training-grades', [ApiLearnController::class, 'fetchTrainingGrades']);
        Route::get('/fetch-training-badges', [ApiLearnController::class, 'fetchTrainingBadges']);
        Route::get('/fetch-training-goals', [ApiLearnController::class, 'fetchTrainingGoals']);
        Route::get('/fetch-training-achievements', [ApiLearnController::class, 'fetchTrainingAchievements']);
        Route::get('/fetch-all-assigned-trainings', [ApiLearnController::class, 'fetchAllAssignedTrainings']);
        Route::put('/start-training-module', [ApiLearnController::class, 'startTrainingModule']);
        Route::put('/start-scorm', [ApiLearnController::class, 'startScorm']);

        // Policy api
        Route::get('/fetch-assigned-policies', [ApiLearnPolicyController::class, 'fetchAssignedPolicies']);
        Route::put('/accept-policy', [ApiLearnPolicyController::class, 'acceptPolicy']);
        Route::get('/policy-login-with-token', [ApiLearnPolicyController::class, 'policyLoginWithToken']);
        Route::get('/fetch-accepted-policies', [ApiLearnPolicyController::class, 'fetchAcceptedPolicies']);

        // Assigned Comics
        Route::get('/fetch-assigned-comics', [ApiLearnController::class, 'fetchAssignedComics']);


        // Translate traning lang
        Route::get('/change-training-lang', [ApiLearnController::class, 'changeTrainingLang']);

        // Fetch Assigned Games
        Route::get('/fetch-assigned-games', [ApiLearnController::class, 'fetchAssignedGames']);

        Route::post('update-game-score', [ApiLearnController::class, 'updateGameScore']);

        // AI Training
        Route::get('/load-ai-training', [ApiLearnController::class, 'generateAiTraining'])->name('generate.training');
        Route::post('/translate-ai-training-quiz', [ApiLearnController::class, 'translateAiTraining'])->name('translate.ai.training');

        // Scorm Training
        Route::get('view-scorm-training', [ApiScormTrainingController::class, 'viewScormTraining']);
        Route::get('/fetch-languages', [ApiLearnController::class, 'fetchLanguages']);

        // Fetch phish test results
        Route::get('/fetch-phish-test-results', [ApiLearnController::class, 'fetchPhishTestResults']);
        // Tour Complete
        Route::post('/tour-complete', [ApiLearnController::class, 'tourComplete']);
    });

    // For Blue Collar
    Route::prefix('blue-collar')->group(function () {
        Route::post('/create-new-token', [ApiLearnBlueCollarController::class, 'createNewToken']);
        Route::get('/login-with-token', [ApiLearnBlueCollarController::class, 'loginWithToken']);

        Route::middleware('checkBlueCollarLearnToken')->group(function () {
            Route::get('/dashboard/metrics', [ApiLearnBlueCollarController::class, 'getDashboardMetrics']);
            Route::get('/get-tranings', [ApiLearnBlueCollarController::class, 'getTranings']);

            Route::post('/update-training-score', [ApiLearnBlueCollarController::class, 'updateTrainingScore']);
            Route::put('/update-training-feedback', [ApiLearnBlueCollarController::class, 'updateTrainingFeedback']);
            Route::get('/fetch-scorm-trainings', [ApiLearnBlueCollarController::class, 'fetchScormTrainings']);
            Route::post('/download-training-certificate', [ApiLearnBlueCollarController::class, 'downloadTrainingCertificate']);
            Route::post('/download-scorm-certificate', [ApiLearnBlueCollarController::class, 'downloadScormCertificate']);
            Route::post('/update-scorm-training-score', [ApiLearnBlueCollarController::class, 'updateScormTrainingScore']);
            Route::put('/update-scorm-training-feedback', [ApiLearnBlueCollarController::class, 'updateScormTrainingFeedback']);
            Route::get('/fetch-score-board', [ApiLearnBlueCollarController::class, 'fetchScoreBoard']);
            Route::get('/fetch-leader-board', [ApiLearnBlueCollarController::class, 'fetchLeaderBoard']);
            Route::get('/fetch-training-grades', [ApiLearnBlueCollarController::class, 'fetchTrainingGrades']);
            Route::get('/fetch-training-badges', [ApiLearnBlueCollarController::class, 'fetchTrainingBadges']);
            Route::get('/fetch-training-goals', [ApiLearnBlueCollarController::class, 'fetchTrainingGoals']);
            Route::get('/fetch-training-achievements', [ApiLearnBlueCollarController::class, 'fetchTrainingAchievements']);
            Route::get('/fetch-all-assigned-trainings', [ApiLearnBlueCollarController::class, 'fetchAllAssignedTrainings']);
            Route::put('/start-training-module', [ApiLearnBlueCollarController::class, 'startTrainingModule']);
            Route::put('/start-scorm', [ApiLearnBlueCollarController::class, 'startScorm']);

            //missing routes
            Route::get('/fetch-languages', [ApiLearnController::class, 'fetchLanguages']);
            Route::get('/change-training-lang', [ApiLearnController::class, 'changeTrainingLang']);
            Route::get('/fetch-assigned-games', [ApiLearnBlueCollarController::class, 'fetchAssignedGames']);
            Route::post('update-game-score', [ApiLearnBlueCollarController::class, 'updateGameScore']);
            Route::get('/load-ai-training', [ApiLearnController::class, 'generateAiTraining']);
            Route::post('/translate-ai-training-quiz', [ApiLearnController::class, 'translateAiTraining']);
            Route::get('view-scorm-training', [ApiScormTrainingController::class, 'viewScormTraining']);

            // Fetch phish test results
            Route::get('/fetch-phish-test-results', [ApiLearnBlueCollarController::class, 'fetchPhishTestResults']);
        });
    });
});
