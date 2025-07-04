<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AicallController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\BluecolarController;
use App\Http\Controllers\OutlookAdController;
use App\Http\Controllers\AiTrainingController;
use App\Http\Controllers\ShowWebsiteController;
use App\Http\Controllers\Learner\CreatePassController;
use App\Http\Controllers\Learner\LearnerAuthController;
use App\Http\Controllers\Learner\LearnerDashController;
use App\Http\Controllers\Admin\AdminTrainingGameController;
use App\Http\Controllers\PhishTriageController;

Route::get('/company/create-password/{token}', [CreatePassController::class, 'createCompanyPassPage'])->name('company.createCompanyPassPage');
Route::post('/company/create-password', [CreatePassController::class, 'storeCompanyPass'])->name('company.storeCompanyPass');


//---------------learning portal routes------------//


Route::domain(checkWhiteLabelDomain())->group(function () {

    Route::get('/', [LearnerAuthController::class, 'index'])->name('learner.loginPage');

    //  Route::post('/renew-token', [LearnerDashController::class, 'renewToken']);
    Route::post('/create-new-token', [LearnerDashController::class, 'createNewToken']);

    Route::get('/training-dashboard/{token}', [LearnerDashController::class, 'trainingWithoutLogin'])
        ->name('learner.training.dashboard');

    Route::get('/policies/{token}', [LearnerDashController::class, 'policyWithoutLogin'])
        ->name('learner.policy.dashboard');

    Route::post('/accept-policy', [LearnerDashController::class, 'acceptPolicy'])
        ->name('learner.accept.policy');


    Route::get('/start-blue-collar-training/{token}', [LearnerDashController::class, 'startBlueCollarTraining'])
        ->name('learner.start.blue.collar.training');




    Route::middleware('isValidLearnerToken')->group(function () {

        Route::get('lang/{locale}', [LearnerDashController::class, 'appLangChange']);

        Route::get('/training/{training_id}/{training_lang}/{id}', [LearnerDashController::class, 'startTraining'])->name('learner.start.training');

        Route::get('/ai-training/{topic}/{language}/{id}', [LearnerDashController::class, 'startAiTraining'])->name('learner.start.ai.training');

        Route::get('/loadTrainingContent/{training_id}/{training_lang}', [LearnerDashController::class, 'loadTraining'])->name('learner.load.training');

        Route::get('/load-ai-training/{topic}', [AiTrainingController::class, 'generateTraining'])->name('generate.training');
        Route::post('/ai-training/translate-quiz', [AiTrainingController::class, 'translateAiTraining'])->name('translate.ai.training');

        Route::get('/gamified/training/{training_id}/{id}/{lang}', [LearnerDashController::class, 'startGamifiedTraining'])->name('learn.gamified.training');

        Route::post('/update-training-score', [LearnerDashController::class, 'updateTrainingScore'])->name('learner.update.score');

        Route::post('/update-bluecollar-training-score', [BluecolarController::class, 'bluecollarUpdateTrainingScore'])->name('learner.bluecollarupdate.score');

        Route::post('/download-certificate', [LearnerDashController::class, 'downloadCertificate'])->name('learner.download.cert');
    });
});

//  ============= Game Training Routes ===========
Route::post('game-score', [AdminTrainingGameController::class, 'gameScore'])
    ->name('gamescore')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);



Route::get('/login-with-microsoft', [OutlookAdController::class, 'loginMicrosoft'])->name('login.with.microsoft');


//-------------------miscellaneous routes------------------//

Route::domain(env('PHISHING_WEBSITE_DOMAIN'))->middleware('blockGoogleBots')->group(function () {

    Route::get('/', function () {
        abort(404, 'Page not found');
    });
});

Route::domain("{subdomain}." . env('PHISHING_WEBSITE_DOMAIN'))->middleware('blockGoogleBots')->group(function () {

    Route::get('/', function () {
        abort(404, 'Page not found');
    });

    Route::get('{dynamicvalue}', [ShowWebsiteController::class, 'index']);

    Route::get('/js/gz.js', [ShowWebsiteController::class, 'loadjs']);

    //route for showing alert page
    Route::get('/show/ap', [ShowWebsiteController::class, 'showAlertPage']);

    //route to check where to redirect
    Route::post('/check-where-to-redirect', [ShowWebsiteController::class, 'checkWhereToRedirect']);
    Route::post('/tcheck-where-to-redirect', [ShowWebsiteController::class, 'tcheckWhereToRedirect']);

    //route for assigning training
    Route::post('/assignTraining', [ShowWebsiteController::class, 'assignTraining']);

    //route for email compromise
    Route::post('/emp-compromised', [ShowWebsiteController::class, 'handleCompromisedEmail']);
    Route::post('/temp-compromised', [ShowWebsiteController::class, 'thandleCompromisedEmail']);

    //route for updating payload
    Route::post('/update-payload', [ShowWebsiteController::class, 'updatePayloadClick']);
    Route::post('/tupdate-payload', [ShowWebsiteController::class, 'tupdatePayloadClick']);

   
});


Route::post('/ai-calling/log-call-detail', [AicallController::class, 'logCallDetail'])->name('ai.call.log.call');
Route::post('/phish-triage/log-report', [PhishTriageController::class, 'logReport']);



Route::get('/trackEmailView/{campid}', [TrackingController::class, 'trackemail']);
Route::get('/ttrackEmailView/{campid}', [TrackingController::class, 'ttrackemail']);
Route::get('/qrcodes/{filename}', [TrackingController::class, 'trackquishing']);
Route::post('/outlook-phish-report', [TrackingController::class, 'outlookPhishReport']);



require __DIR__ . '/auth.php';


