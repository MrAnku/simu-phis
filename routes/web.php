<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;
use App\Http\Middleware\BlockMicrosoftIps;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\PhishTriageController;
use App\Http\Controllers\ShowWebsiteController;
use App\Http\Controllers\PhishingReplyController;
use App\Http\Controllers\Admin\AdminTrainingGameController;


//  ============= Game Training Routes ===========
Route::post('game-score', [AdminTrainingGameController::class, 'gameScore'])
    ->name('gamescore')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

//-------------------miscellaneous routes------------------//

Route::domain(checkPhishingWebsiteDomain())->middleware(['blockGoogleBots', 'msBotBlocker'])->group(function () {

    Route::get('/', function () {
        abort(404, 'Page not found');
    });
});

Route::domain("{subdomain}." . checkPhishingWebsiteDomain())->middleware('blockGoogleBots')->group(function () {

    Route::get('/', function () {
        abort(404, 'Page not found');
    });

    Route::get('{dynamicvalue}', [ShowWebsiteController::class, 'index'])->middleware('msBotBlocker');

    Route::get('/js/gz.js', [ShowWebsiteController::class, 'loadjs']);

    //route for showing alert page
    Route::get('/show/ap', [ShowWebsiteController::class, 'showAlertPage']);

    //route to check where to redirect
    Route::post('/check-where-to-redirect', [ShowWebsiteController::class, 'checkWhereToRedirect']);

    //route for assigning training
    Route::post('/assignTraining', [ShowWebsiteController::class, 'assignTraining'])->middleware('msBotBlocker');

    //route for email compromise
    Route::post('/emp-compromised', [ShowWebsiteController::class, 'handleCompromisedEmail'])->middleware('msBotBlocker');

    //route for updating payload
    Route::post('/update-payload', [ShowWebsiteController::class, 'updatePayloadClick'])->middleware('msBotBlocker');
});

//test routes
Route::get('/test/scorm/{id}', [TestController::class, 'testScorm'])
    ->name('test.scorm');


Route::middleware('throttle:hook-limiter')->group(function () {

    
    Route::post('/phish-triage/log-report', [PhishTriageController::class, 'logReport']);

    Route::post('/phishing-reply', [PhishingReplyController::class, 'phishingReply']);


    Route::get('/trackEmailView/{campid}', [TrackingController::class, 'trackemail'])->middleware('msBotBlocker');
    Route::get('/ttrackEmailView/{campid}', [TrackingController::class, 'ttrackemail']);
    Route::get('/qrcodes/{filename}', [TrackingController::class, 'trackquishing']);
    Route::post('/outlook-phish-report', [TrackingController::class, 'outlookPhishReport']);
    Route::post('/googlereport', [TrackingController::class, 'googleReport']);
});


Route::middleware(BlockMicrosoftIps::class)->group(function () {
    Route::get('test-outlook-bot', function () {
        return "You're not a bot!";
    });
});


require __DIR__ . '/auth.php';
