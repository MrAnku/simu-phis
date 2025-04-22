<?php

use App\Http\Controllers\Api\ApiBlueCollarController;
use App\Http\Controllers\Api\ApiEmployeesController;
use App\Http\Controllers\Api\ApiOutlookAdController;
use App\Http\Controllers\Api\ApiPhishingEmailsController;
use App\Http\Controllers\Api\ApiQuishingEmailController;
use App\Http\Controllers\Api\ApiReportingController;
use App\Http\Controllers\ApiAiCallController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MFAController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\SenderProfileController;
use App\Http\Controllers\TrainingModuleController;
use App\Http\Controllers\Api\ApiCampaignController;
use App\Http\Controllers\Api\ApiPhishingEmailsController;
use App\Http\Controllers\Api\ApiTrainingModuleController;
use App\Http\Controllers\Api\ApiWhatsappCampaignController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

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

    //email campaign routes
    Route::prefix('email-campaign')->group(function () {
        Route::get('/', [ApiCampaignController::class, 'index']);
        Route::post('/create', [ApiCampaignController::class, 'createCampaign']);
        Route::delete('/delete/{campaign_id}', [ApiCampaignController::class, 'deleteCampaign']);
        Route::get('/detail/{campaign_id}', [ApiCampaignController::class, 'fetchCampaignDetail']);
        Route::get('/game-detail/{campaign_id}', [ApiCampaignController::class, 'fetchGameDetail']);
        Route::post('/relaunch/{campaign_id}', [ApiCampaignController::class, 'relaunchCampaign']);
        Route::get('/fetch-phish-data', [ApiCampaignController::class, 'fetchPhishData']);
        Route::post('/reschedule/{campaign_id?}', [ApiCampaignController::class, 'rescheduleCampaign']);
        Route::post('/send-training-reminder/{email?}', [ApiCampaignController::class, 'sendTrainingReminder']);
        Route::put('/complete-training/{encodedTrainingId?}', [ApiCampaignController::class, 'completeTraining']);
        Route::delete('/remove-training/{encodedTrainingId?}', [ApiCampaignController::class, 'removeTraining']);
    });

    //whatsapp campaign routes
    Route::prefix('whatsapp-campaign')->group(function () {
        Route::get('/', [ApiWhatsappCampaignController::class, 'index']);
        Route::post('/save-config', [ApiWhatsappCampaignController::class, 'saveConfig']);
        Route::put('/update-config', [ApiWhatsappCampaignController::class, 'updateConfig']);
        Route::get('/sync-templates', [ApiWhatsappCampaignController::class, 'syncTemplates']);
        Route::post('/create-campaign', [ApiWhatsappCampaignController::class, 'createCampaign']);
        Route::delete('/delete-campaign/{campaign_id?}', [ApiWhatsappCampaignController::class, 'deleteCampaign']);
        Route::get('/campaign-detail/{campaign_id?}', [ApiWhatsappCampaignController::class, 'fetchCampaign']);
        Route::get('/group-employees/{employee_type?}', [ApiWhatsappCampaignController::class, 'groupUsers']);
        Route::post('/new-template', [ApiWhatsappCampaignController::class, 'newTemplate']);
    });

    // Phishing Material routes
    Route::prefix('phishing-material')->group(function () {
        Route::get('/', [ApiPhishingEmailsController::class, 'index']);
        Route::get('/search', [ApiPhishingEmailsController::class, 'searchPhishingMaterial']);
    });

   
    

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
        Route::get('/fetch-campaign-report/{campaignId?}', [ApiReportingController::class, 'fetchCampaignReport']);
        Route::get('/whatsappfetch-campaign-report/{campaignId?}', [ApiReportingController::class, 'whatsappfetchCampaignReport']);
        Route::get('/aicallingfetch-campaign-report/{campaignId?}', [ApiReportingController::class, 'aicallingfetchCampaignReport']);
        Route::get('/tprmfetch-campaign-report/{campaignId?}', [ApiReportingController::class, 'tprmfetchCampaignReport']);
        Route::get('/fetch-camp-report-by-users/{campaignId?}', [ApiReportingController::class, 'fetchCampReportByUsers']);

        // 1
        Route::get('/tprm-fetch-camp-report-by-users/{campaignId?}', [ApiReportingController::class, 'tprmfetchCampReportByUsers']);
        // 2
        Route::get('/aicallingfetch-camp-report-by-users/{campaignId?}', [ApiReportingController::class, 'aicallingfetchCampReportByUsers']);
        // 3 
        Route::get('/whatsappfetch-camp-report-by-users/{campaignId?}', [ApiReportingController::class, 'whatsappfetchCampReportByUsers']);
        // 4 
        Route::get('/fetch-camp-training-details/{campaignId}', [ApiReportingController::class, 'fetchCampTrainingDetails']);
        // 5 
        Route::get('/aicallingfetch-camp-training-details/{campaignId?}', [ApiReportingController::class, 'aicallingfetchCampTrainingDetails']);
        // 6 
        Route::get('/whatsappfetch-camp-training-details/{campaignId?}', [ApiReportingController::class, 'whatsappfetchCampTrainingDetails']);

        // 7 
        Route::get('/fetch-camp-training-details-individual/{campaignId?}', [ApiReportingController::class, 'fetchCampTrainingDetailsIndividual']);

        // 8 
        Route::get('/aicallingfetch-camp-training-details-individual/{campaignId?}', [ApiReportingController::class, 'aicallingfetchCampTrainingDetailsIndividual']);

        // 9 
        Route::get('/whatsappfetch-camp-training-details-individual/{campaignId?}', [ApiReportingController::class, 'whatsappfetchCampTrainingDetailsIndividual']);
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

    Route::prefix('ai-calling')->group(function () {
        Route::get('/', [ApiAiCallController::class, 'index']);
        Route::post('/submit-req', [ApiAiCallController::class, 'submitReq']);
        Route::post('/create-campaign', [ApiAiCallController::class, 'createCampaign']);
        Route::get('/view-campaign/{id?}', [ApiAiCallController::class, 'viewCampaign']);
        Route::delete('/delete-campaign/{id?}', [ApiAiCallController::class, 'deleteCampaign']);
        Route::get('/get-agents', [ApiAiCallController::class, 'getAgents']);
        Route::get('/fetch-call-report/{callId?}', [ApiAiCallController::class, 'fetchCallReport']);
        Route::post('/agent-req', [ApiAiCallController::class, 'agentRequest']);
    });
    Route::prefix('phishing-emails')->group(function () {
        //    10 
        Route::get('/', [ApiPhishingEmailsController::class, 'index']);
        //    11 ho gya test
        Route::post('/get-template-by-id/{id?}', [ApiPhishingEmailsController::class, 'getTemplateById']);
        //    12 ho gya test
        Route::get('/search-email-template', [ApiPhishingEmailsController::class, 'searchPhishingEmails']);
        // 13 ho gya test
        Route::post('/add-email-template', [ApiPhishingEmailsController::class, 'addEmailTemplate']);
        // 14 ho gya test
        Route::post('/generate-template', [ApiPhishingEmailsController::class, 'generateTemplate']);
        // 15 ho gya test
        Route::post('/save-ai-phish-template', [ApiPhishingEmailsController::class, 'saveAIPhishTemplate']);
        // 16 ho gya test
        Route::post('/update-email-template', [ApiPhishingEmailsController::class, 'updateTemplate']);
        // 17 ho gya test
        Route::post('/delete-email-template', [ApiPhishingEmailsController::class, 'deleteTemplate']);
    });
    Route::prefix('quishing-emails')->group(function () {
        Route::get('/', [ApiQuishingEmailController::class, 'index']);
        Route::post('/add-temp', [ApiQuishingEmailController::class, 'addTemplate']);
        Route::post('/delete-temp', [ApiQuishingEmailController::class, 'deleteTemplate']);
        Route::post('/update-temp', [ApiQuishingEmailController::class, 'updateTemplate']);
    });
});
