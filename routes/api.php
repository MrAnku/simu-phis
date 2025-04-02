<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;

Route::post('login', [AuthenticatedSessionController::class, 'login']);
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
    });
});
