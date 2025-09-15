<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;
use App\Http\Controllers\AicallController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\BluecolarController;
use App\Http\Controllers\OutlookAdController;
use App\Http\Controllers\AiTrainingController;
use App\Http\Controllers\PhishTriageController;
use App\Http\Controllers\ShowWebsiteController;
use App\Http\Controllers\PhishingReplyController;
use App\Http\Controllers\Learner\CreatePassController;
use App\Http\Controllers\Learner\LearnerAuthController;
use App\Http\Controllers\Learner\LearnerDashController;
use App\Http\Controllers\Admin\AdminTrainingGameController;

Route::get('/company/create-password/{token}', [CreatePassController::class, 'createCompanyPassPage'])->name('company.createCompanyPassPage');
Route::post('/company/create-password', [CreatePassController::class, 'storeCompanyPass'])->name('company.storeCompanyPass');

Route::get('/send-email/{email}', function ($email) {
    // Simple email sending logic
    try {
        // config(['mail.mailers.smtp' => [
        //     'transport' => 'smtp',
        //     'host' => env('MAIL_HOST', 'smtp.mailtrap.io'),
        //     'port' => env('MAIL_PORT', 2525),
        //     'encryption' => env('MAIL_ENCRYPTION', null),
        //     'username' => env('MAIL_USERNAME'),
        //     'password' => env('MAIL_PASSWORD'),
        //     'timeout' => null,
        //     'auth_mode' => null,
        // ]]);
        Mail::raw('This is a test email.', function ($message) use ($email) {
            $message->to($email)
                ->subject('Test Email');
        });
        return response()->json([
            'status' => 'success',
            'message' => 'Email sent successfully'
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to send email',
            'error' => $e->getMessage()
        ], 500);
    }
});


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

Route::domain(checkPhishingWebsiteDomain())->middleware('blockGoogleBots')->group(function () {

    Route::get('/', function () {
        abort(404, 'Page not found');
    });
});

Route::domain("{subdomain}." . checkPhishingWebsiteDomain())->middleware(['blockGoogleBots', 'hook-limiter'])->group(function () {

    Route::get('/', function () {
        abort(404, 'Page not found');
    });

    Route::get('{dynamicvalue}', [ShowWebsiteController::class, 'index']);

    Route::get('/js/gz.js', [ShowWebsiteController::class, 'loadjs']);

    //route for showing alert page
    Route::get('/show/ap', [ShowWebsiteController::class, 'showAlertPage']);

    //route to check where to redirect
    Route::post('/check-where-to-redirect', [ShowWebsiteController::class, 'checkWhereToRedirect']);

    //route for assigning training
    Route::post('/assignTraining', [ShowWebsiteController::class, 'assignTraining']);

    //route for email compromise
    Route::post('/emp-compromised', [ShowWebsiteController::class, 'handleCompromisedEmail']);

    //route for updating payload
    Route::post('/update-payload', [ShowWebsiteController::class, 'updatePayloadClick']);
});

//test routes
Route::get('/test/scorm/{id}', [TestController::class, 'testScorm'])
    ->name('test.scorm');


Route::middleware('hook-limiter')->group(function () {

    Route::post('/ai-calling/log-call-detail', [AicallController::class, 'logCallDetail'])->name('ai.call.log.call');
    Route::post('/phish-triage/log-report', [PhishTriageController::class, 'logReport']);

    Route::post('/phishing-reply', [PhishingReplyController::class, 'phishingReply']);


    Route::get('/trackEmailView/{campid}', [TrackingController::class, 'trackemail']);
    Route::get('/ttrackEmailView/{campid}', [TrackingController::class, 'ttrackemail']);
    Route::get('/qrcodes/{filename}', [TrackingController::class, 'trackquishing']);
    Route::post('/outlook-phish-report', [TrackingController::class, 'outlookPhishReport']);
});



require __DIR__ . '/auth.php';
