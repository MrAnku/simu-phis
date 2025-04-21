<?php

use App\Http\Controllers\Api\ApiBlueCollarController;
use App\Http\Controllers\Api\ApiEmployeesController;
use App\Http\Controllers\Api\ApiOutlookAdController;
use App\Http\Controllers\Api\ApiReportingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MFAController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\SenderProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupportController;
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
    Route::prefix('training-module')->group(function () {
        Route::get('/get-all', [TrainingModuleController::class, 'allTrainingModule']);
        Route::get('/getby/{id}', [TrainingModuleController::class, 'getTrainingById']);
        Route::put('/update', [TrainingModuleController::class, 'updateTrainingModule']);
        Route::delete('/delete', [TrainingModuleController::class, 'deleteTraining']);
        Route::get('/training-modules', [TrainingModuleController::class, 'index']);
        Route::post('/add', [TrainingModuleController::class, 'addTraining']);
        Route::get('/previewby/{trainingid}', [TrainingModuleController::class, 'trainingPreview']);
        Route::get('/preview-content/{trainingid}/{lang}', [TrainingModuleController::class, 'loadPreviewTrainingContent']);
        Route::post('/add-gamified-training', [TrainingModuleController::class, 'addGamifiedTraining']);
        Route::put('/update-gamified-training', [TrainingModuleController::class, 'updateGamifiedTraining']);
    });
    Route::prefix('support')->group(function () {
        Route::get('/', [SupportController::class, 'index']);
        Route::post('/load-conversations', [SupportController::class, 'loadConversations']);
        Route::post('/submit-reply', [SupportController::class, 'submitReply']);
        Route::post('/create-ticket', [SupportController::class, 'createTicket']);
    });
    Route::prefix('reporting')->group(function () {
        Route::get('/', [ApiReportingController::class, 'index']);
        Route::get('/getChartData', [ApiReportingController::class, 'getChartData']);
        Route::get('/wgetChartData', [ApiReportingController::class, 'wgetChartData']);
        Route::get('/cgetChartData', [ApiReportingController::class, 'cgetChartData']);
        Route::get('/fetch-campaign-report/{campaignId}', [ApiReportingController::class, 'fetchCampaignReport']);
        Route::get('/whatsappfetch-campaign-report/{campaignId}', [ApiReportingController::class, 'whatsappfetchCampaignReport']);
        Route::get('/aicallingfetch-campaign-report/{campaignId}', [ApiReportingController::class, 'aicallingfetchCampaignReport']);
        Route::get('/tprmfetch-campaign-report/{campaignId}', [ApiReportingController::class, 'tprmfetchCampaignReport']);
        Route::get('/fetch-camp-report-by-users/{campaignId}', [ApiReportingController::class, 'fetchCampReportByUsers']);
    });
    Route::prefix('employees')->group(function () {
        Route::get('/', [ApiEmployeesController::class, 'index']);
        Route::get('/all-employees', [ApiEmployeesController::class, 'allEmployee']);
        Route::get('/blue-collar-employees', [ApiBlueCollarController::class, 'index']);
        Route::get('/normalemployees', [ApiEmployeesController::class, 'index']);
        Route::get('/employee/{base_encode_id?}', [ApiEmployeesController::class, 'employeeDetail']);
        Route::get('/login-with-microsoft', [ApiOutlookAdController::class, 'loginMicrosoft']);
        Route::get('/microsoft-ad-callback', [ApiOutlookAdController::class, 'handleMicrosoftCallback']);
        Route::get('/fetch-outlook-groups', [ApiOutlookAdController::class, 'fetchGroups']);
        Route::get('/fetch-outlook-emps/{groupId?}', [ApiOutlookAdController::class, 'fetchEmps']);
        Route::post('/save-outlook-employees', [ApiOutlookAdController::class, 'saveOutlookEmps']);
        Route::post('/send-domain-verify-otp', [ApiEmployeesController::class, 'sendDomainVerifyOtp']);
        Route::post('/otp-verify', [ApiEmployeesController::class, 'verifyOtp']);
        Route::delete('/delete-domain/{domain?}', [ApiEmployeesController::class, 'deleteDomain']);
        Route::post('/create-new-group', [ApiEmployeesController::class, 'createNewGroup']);
        Route::post('/create-blue-collar-group', [ApiBlueCollarController::class, 'blueCollarNewGroup']);
        Route::get('/view-users/{groupId?}', [ApiEmployeesController::class, 'viewUsers']);
        Route::get('/view-unique-emails', [ApiEmployeesController::class, 'viewUniqueEmails']);
        Route::post('/add-emp-from-all-emp', [ApiEmployeesController::class, 'addEmpFromAllEmp']);
        Route::get('/view-blue-collar-users/{groupId?}', [ApiBlueCollarController::class, 'viewBlueCollarUsers']);
        Route::delete('/delete-user/{user_id?}', [ApiEmployeesController::class, 'deleteUser']);
        Route::delete('/delete-emp-by-email', [ApiEmployeesController::class, 'deleteUserByEmail']);
        Route::delete('/delete-blue-user/{user_id?}', [ApiBlueCollarController::class, 'deleteBlueUser']);
        Route::post('/add-user', [ApiEmployeesController::class, 'addUser']);
        Route::post('/add-plan-user', [ApiEmployeesController::class, 'addPlanUser']);
        Route::post('/add-blue-collar-user', [ApiBlueCollarController::class, 'addBlueCollarUser']);
        Route::post('/import-csv', [ApiEmployeesController::class, 'importCsv']);
        Route::delete('/delete-group/{groupId?}', [ApiEmployeesController::class, 'deleteGroup']);
        Route::delete('/delete-blue-group/{group_id?}', [ApiBlueCollarController::class, 'deleteBlueGroup']);
        Route::get('/check-ldap-ad-config', [ApiEmployeesController::class, 'checkAdConfig']);
        Route::put('/update-ldap-config', [ApiEmployeesController::class, 'updateLdapConfig']);
        Route::post('/add-ldap-config', [ApiEmployeesController::class, 'addLdapConfig']);
        Route::get('/sync-ldap-directory', [ApiEmployeesController::class, 'syncLdap']);
    });
});
