<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;

Route::post('login', [AuthenticatedSessionController::class, 'login']);
Route::post('logout', [AuthenticatedSessionController::class, 'logout'])->middleware('auth:api');
Route::middleware('auth:api')->get('/dashboard', [DashboardController::class, 'index']);
Route::get('me', [AuthenticatedSessionController::class, 'me'])->middleware('auth:api');
Route::middleware('auth:api')->get('/get-pie-data', [DashboardController::class, 'getPieData']);
Route::middleware('auth:api')->get('/get-line-chart-data', [DashboardController::class, 'getLineChartData']);
Route::middleware('auth:api')->get('/get-whats-chart-data', [DashboardController::class, 'whatsappReport']);
Route::middleware('auth:api')->get('/get-payload-click-data', [DashboardController::class, 'getPayloadClickData']);
Route::middleware('auth:api')->get('/get-email-reported-data', [DashboardController::class, 'getEmailReportedData']);
Route::middleware('auth:api')->get('/get-package-data', [DashboardController::class, 'getPackage']);
Route::middleware('auth:api')->get('/get-line-chart-2-data', [DashboardController::class, 'getLineChartData2']);
