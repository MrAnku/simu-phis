<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MFAController;
use App\Http\Controllers\SenderProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TrainingModuleController;

Route::post('login', [AuthenticatedSessionController::class, 'login']);
Route::post('mfa/verify', [MFAController::class, 'verifyOTP']);
Route::post('logout', [AuthenticatedSessionController::class, 'logout'])->middleware('auth:api');
Route::middleware('auth:api')->get('/dashboard', [DashboardController::class, 'index']);
Route::get('me', [AuthenticatedSessionController::class, 'me'])->middleware('auth:api');
Route::middleware('auth:api')->group(function () {
    // Dashboard routes
    // Route::prefix('dashboard')->group(function () {
    Route::get('/get-pie-data', [DashboardController::class, 'getPieData']);
    Route::get('/get-line-chart-data', [DashboardController::class, 'getLineChartData']);
    Route::get('/get-whats-chart-data', [DashboardController::class, 'whatsappReport']);
    Route::get('/get-payload-click-data', [DashboardController::class, 'getPayloadClickData']);
    Route::get('/get-email-reported-data', [DashboardController::class, 'getEmailReportedData']);
    Route::get('/get-package-data', [DashboardController::class, 'getPackage']);
    Route::get('/get-line-chart-2-data', [DashboardController::class, 'getLineChartData2']);
    // });

    // Settings routes
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index']);
        Route::post('/update-profile', [SettingsController::class, 'updateProfile']);
        Route::post('/update-password', [SettingsController::class, 'updatePassword']);
        Route::post('/acc-dectivate', [SettingsController::class, 'deactivateAccount']);
        Route::post('/update-lang', [SettingsController::class, 'updateLang']);
        Route::put('/update-phish-edu', [SettingsController::class, 'updatePhishingEdu']);
        Route::post('/update-train-freq', [SettingsController::class, 'updateTrainFreq']);
        Route::post('/update-reporting', [SettingsController::class, 'updateReporting']);
        Route::post('/update-mfa', [SettingsController::class, 'updateMFA']);
        Route::post('/verify-mfa', [SettingsController::class, 'verifyMFA']);
    });
    Route::prefix('sender-profiles')->group(function () {
        Route::get('/', [SenderProfileController::class, 'index']);
        Route::post('/add', [SenderProfileController::class, 'addSenderProfile']);
        Route::delete('/delete', [SenderProfileController::class, 'deleteSenderProfile']);
        Route::post('/update/{id}', [SenderProfileController::class, 'updateSenderProfile']);
    });
    Route::get('/get-all-training-module', [TrainingModuleController::class, 'allTrainingModule']);
});
