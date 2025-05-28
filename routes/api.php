<?php

use App\Http\Controllers\Api\ApiAiCallController;
use App\Http\Controllers\Api\ApiBlueCollarController;
// use App\Http\Controllers\Api\ApiBlueCollarController;
use App\Http\Controllers\Api\ApiEmployeesController;
use App\Http\Controllers\Api\ApiOutlookAdController;
use App\Http\Controllers\Api\ApiPhishingEmailsController;
use App\Http\Controllers\Api\ApiQuishingEmailController;
use App\Http\Controllers\Api\ApiReportingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MFAController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\SenderProfileController;
use App\Http\Controllers\TrainingModuleController;
use App\Http\Controllers\Api\ApiCampaignController;
use App\Http\Controllers\Api\ApiQuishingController;
use App\Http\Controllers\Api\ApiDarkWebMonitoringController;
use App\Http\Controllers\Api\ApiDashboardController;
use App\Http\Controllers\Api\ApiPhishingWebsitesController;
use App\Http\Controllers\Api\ApiSenderProfileController;
use App\Http\Controllers\Api\ApiSettingsController;
use App\Http\Controllers\Api\ApiShowWebsiteController;
use App\Http\Controllers\Api\ApiSmishingController;
use App\Http\Controllers\Api\ApiSmishingTemplateController;
use App\Http\Controllers\Api\ApiSupportController;
use App\Http\Controllers\Api\ApiTprmController;
use App\Http\Controllers\Api\ApiTrainingModuleController;
use App\Http\Controllers\Api\ApiWaCampaignController;
use App\Http\Controllers\Api\ApiWhiteLabelController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::post('login', [AuthenticatedSessionController::class, 'login']);
Route::post('mfa/verify', [MFAController::class, 'verifyOTP']);
Route::post('logout', [AuthenticatedSessionController::class, 'logout'])->middleware('auth:api');

// Route::get('{dynamicvalue}', [ApiShowWebsiteController::class, 'index']);


// Route::domain("{subdomain}." . env('PHISHING_WEBSITE_DOMAIN'))->group(
//     function () {
//         Route::get('{dynamicvalue}', [ApiShowWebsiteController::class, 'index']);
//     }
// );

// Route::domain("{subdomain}." . env('PHISHING_WEBSITE_DOMAIN'))->middleware('blockGoogleBots')->group(
//     function () {
//         Route::get('{dynamicvalue}', [ApiShowWebsiteController::class, 'index']);
//     }
// );


Route::middleware('auth:api')->get('/dashboard', [ApiDashboardController::class, 'index']);
Route::get('me', [AuthenticatedSessionController::class, 'me'])->middleware('auth:api');
Route::middleware('auth:api')->group(function () {
    // Route::domain("{subdomain}." . env('PHISHING_WEBSITE_DOMAIN'))->group(
    //     function () {
    //         Route::get('{dynamicvalue}', [ApiShowWebsiteController::class, 'index']);
    //     }
    // );
    // Dashboard routes
    // Route::prefix('dashboard')->group(function () {
    Route::get('/get-pie-data', [ApiDashboardController::class, 'getPieData']);
    Route::get('/get-line-chart-data', [ApiDashboardController::class, 'getLineChartData']);
    Route::get('/get-whats-chart-data', [ApiDashboardController::class, 'whatsappReport']);
    Route::get('/get-payload-click-data', [ApiDashboardController::class, 'getPayloadClickData']);
    Route::get('/get-email-reported-data', [ApiDashboardController::class, 'getEmailReportedData']);
    Route::get('/get-package-data', [ApiDashboardController::class, 'getPackage']);
    Route::get('/get-line-chart-2-data', [ApiDashboardController::class, 'getLineChartData2']);
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

    //quishing campaign routes
    Route::prefix('quishing-campaign')->group(function () {
        Route::get('/', [ApiQuishingController::class, 'index']);
        Route::post('/create-campaign', [ApiQuishingController::class, 'createCampaign']);
        Route::delete('/delete-campaign/{campaign_id?}', [ApiQuishingController::class, 'deleteCampaign']);
        Route::get('/detail/{campaign_id?}', [ApiQuishingController::class, 'campaignDetail']);
        Route::post('/duplicate/{id?}', [ApiQuishingController::class, 'duplicate']);
    });

    //whatsapp campaign routes
    Route::prefix('whatsapp-campaign')->group(function () {
        Route::get('/', [ApiWaCampaignController::class, 'index']);
        Route::post('/save-config', [ApiWaCampaignController::class, 'saveConfig']);
        Route::put('/update-config', [ApiWaCampaignController::class, 'updateConfig']);
        Route::get('/sync-templates', [ApiWaCampaignController::class, 'syncTemplates']);
        Route::post('/create-campaign', [ApiWaCampaignController::class, 'createCampaign']);
        Route::delete('/delete-campaign/{campaign_id?}', [ApiWaCampaignController::class, 'deleteCampaign']);
        Route::get('/campaign-detail/{campaign_id?}', [ApiWaCampaignController::class, 'fetchCampaign']);
        Route::get('/group-employees/{employee_type?}', [ApiWaCampaignController::class, 'groupUsers']);
        Route::post('/new-template', [ApiWaCampaignController::class, 'newTemplate']);
    });

    // Phishing Material routes
    Route::prefix('phishing-material')->group(function () {
        Route::get('/', [ApiPhishingEmailsController::class, 'index']);
        Route::get('/search', [ApiPhishingEmailsController::class, 'searchPhishingMaterial']);
    });
    // Settings routes
    Route::prefix('settings')->group(function () {
        Route::get('/', [ApiSettingsController::class, 'index']);
        Route::put('/update-profile', [ApiSettingsController::class, 'updateProfile']);
        Route::put('/update-password', [ApiSettingsController::class, 'updatePassword']);
        Route::post('/acc-dectivate', [ApiSettingsController::class, 'deactivateAccount']);
        Route::put('/update-lang', [ApiSettingsController::class, 'updateLang']);
        Route::put('/update-phish-edu', [ApiSettingsController::class, 'updatePhishingEdu']);
        Route::put('/update-train-freq', [ApiSettingsController::class, 'updateTrainFreq']);
        Route::put('/update-reporting', [ApiSettingsController::class, 'updateReporting']);
        Route::put('/update-mfa', [ApiSettingsController::class, 'updateMFA']);
        Route::post('/verify-mfa', [ApiSettingsController::class, 'verifyMFA']);
        Route::put('/update-siem', [ApiSettingsController::class, 'updateSiem']);
    });

    Route::prefix('sender-profiles')->group(function () {
        Route::get('/', [ApiSenderProfileController::class, 'index']);
        Route::post('/add', [ApiSenderProfileController::class, 'addSenderProfile']);
        Route::delete('/delete', [ApiSenderProfileController::class, 'deleteSenderProfile']);
        Route::post('/update/{id}', [ApiSenderProfileController::class, 'updateSenderProfile']);
    });

    Route::prefix('training-module')->group(function () {
        Route::get('/get-all', [ApiTrainingModuleController::class, 'allTrainingModule']);
        Route::get('/getby/{id}', [ApiTrainingModuleController::class, 'getTrainingById']);
        Route::put('/update', [ApiTrainingModuleController::class, 'updateTrainingModule']);
        Route::delete('/delete', [ApiTrainingModuleController::class, 'deleteTraining']);
        Route::get('/training-modules', [ApiTrainingModuleController::class, 'index']);
        Route::post('/add', [ApiTrainingModuleController::class, 'addTraining']);
        Route::get('/previewby/{trainingid}', [ApiTrainingModuleController::class, 'trainingPreview']);
        Route::get('/preview-content/{trainingid}/{lang}', [ApiTrainingModuleController::class, 'loadPreviewTrainingContent']);
        Route::get('/games', [ApiTrainingModuleController::class, 'getGames']);
        Route::post('/add-gamified-training', [ApiTrainingModuleController::class, 'addGamifiedTraining']);
        Route::put('/update-gamified-training', [ApiTrainingModuleController::class, 'updateGamifiedTraining']);
        Route::post('/duplicate/{id?}', [ApiTrainingModuleController::class, 'duplicate']);
    });

    Route::prefix('support')->group(function () {
        Route::get('/', [ApiSupportController::class, 'index']);
        Route::post('/load-conversations', [ApiSupportController::class, 'loadConversations']);
        Route::post('/submit-reply', [ApiSupportController::class, 'submitReply']);
        Route::post('/create-ticket', [ApiSupportController::class, 'createTicket']);
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

        Route::get('/tprm-fetch-camp-report-by-users/{campaignId?}', [ApiReportingController::class, 'tprmfetchCampReportByUsers']);

        Route::get('/aicallingfetch-camp-report-by-users/{campaignId?}', [ApiReportingController::class, 'aicallingfetchCampReportByUsers']);

        Route::get('/whatsappfetch-camp-report-by-users/{campaignId?}', [ApiReportingController::class, 'whatsappfetchCampReportByUsers']);

        Route::get('/fetch-camp-training-details/{campaignId}', [ApiReportingController::class, 'fetchCampTrainingDetails']);

        Route::get('/aicallingfetch-camp-training-details/{campaignId?}', [ApiReportingController::class, 'aicallingfetchCampTrainingDetails']);

        Route::get('/whatsappfetch-camp-training-details/{campaignId?}', [ApiReportingController::class, 'whatsappfetchCampTrainingDetails']);

        Route::get('/fetch-camp-training-details-individual/{campaignId?}', [ApiReportingController::class, 'fetchCampTrainingDetailsIndividual']);

        Route::get('/aicallingfetch-camp-training-details-individual/{campaignId?}', [ApiReportingController::class, 'aicallingfetchCampTrainingDetailsIndividual']);

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
        Route::get('/view-campaign/{campaign_id?}', [ApiAiCallController::class, 'viewCampaign']);
        Route::delete('/delete-campaign/{campaign_id?}', [ApiAiCallController::class, 'deleteCampaign']);
        Route::get('/get-agents', [ApiAiCallController::class, 'getAgents']);
        Route::get('/fetch-call-report/{callId?}', [ApiAiCallController::class, 'fetchCallReport']);
        Route::post('/agent-req', [ApiAiCallController::class, 'agentRequest']);
    });

    Route::prefix('phishing-emails')->group(function () {
        Route::get('/', [ApiPhishingEmailsController::class, 'index']);
        Route::get('/get-template-by-id/{id?}', [ApiPhishingEmailsController::class, 'getTemplateById']);
        Route::get('/search-email-template', [ApiPhishingEmailsController::class, 'searchPhishingEmails']);
        Route::post('/add-email-template', [ApiPhishingEmailsController::class, 'addEmailTemplate']);
        Route::post('/generate-template', [ApiPhishingEmailsController::class, 'generateTemplate']);
        Route::post('/save-ai-phish-template', [ApiPhishingEmailsController::class, 'saveAIPhishTemplate']);
        Route::put('/update-email-template', [ApiPhishingEmailsController::class, 'updateTemplate']);
        Route::delete('/delete-email-template', [ApiPhishingEmailsController::class, 'deleteTemplate']);
        Route::post('/duplicate/{id?}', [ApiPhishingEmailsController::class, 'duplicate']);
    });

    Route::prefix('quishing-emails')->group(function () {
        Route::get('/', [ApiQuishingEmailController::class, 'index']);
        Route::post('/add-temp', [ApiQuishingEmailController::class, 'addTemplate']);
        Route::get('/get-template-by-id/{id?}', [ApiQuishingEmailController::class, 'getTemplateById']);
        Route::delete('/delete-temp', [ApiQuishingEmailController::class, 'deleteTemplate']);
        Route::put('/update-temp', [ApiQuishingEmailController::class, 'updateTemplate']);
    });

    Route::prefix('phishing-website')->group(function () {
        Route::get('/all', [ApiPhishingWebsitesController::class, 'getAll']);
        Route::get('/', [ApiPhishingWebsitesController::class, 'index']);
        Route::delete('/delete', [ApiPhishingWebsitesController::class, 'deleteWebsite']);
        Route::post('/add', [ApiPhishingWebsitesController::class, 'addPhishingWebsite']);
        Route::get('/get-website-by-id/{id?}', [ApiPhishingWebsitesController::class, 'getWebsiteById']);
        Route::post('/generate', [ApiPhishingWebsitesController::class, 'generateWebsite']);
        Route::get('/search-website', [ApiPhishingWebsitesController::class, 'searchWebsite']);
        Route::post('/save-generate', [ApiPhishingWebsitesController::class, 'saveGeneratedSite']);
        Route::post('/duplicate/{id?}', [ApiPhishingWebsitesController::class, 'duplicate']);
    });

    Route::get('/human-risk-intelligence', [ApiDarkWebMonitoringController::class, 'index']);

    Route::prefix('tprm')->group(function () {
        Route::get('/', [ApiTprmController::class, 'index']);
        Route::post('/submit-req', [ApiTprmController::class, 'submitReq']);
        Route::post('/submit-domains', [ApiTprmController::class, 'submitdomains']);
        Route::delete('/delete-domain', [ApiTprmController::class, 'deleteDomain']);
        Route::post('/campaigns/create', [ApiTprmController::class, 'createCampaign']);
        Route::delete('/delete-campaign/{campId?}', [ApiTprmController::class, 'deleteCampaign']);
        Route::post('/campaigns/relaunch/{campId?}', [ApiTprmController::class, 'relaunchCampaign']);
        Route::get('/campaigns/fetch-phish-data', [ApiTprmController::class, 'fetchPhishData']);
        Route::get('/treporting/fetch-campaign-report/{campaignId?}', [ApiReportingController::class, 'tfetchCampaignReport']);
        Route::get('/tfetch-camp-report-by-users/{campaignId?}', [ApiReportingController::class, 'tfetchCampReportByUsers']);
        Route::post('/campaigns/fetchEmail', [ApiTprmController::class, 'fetchEmail']);
        Route::post('/campaigns/addGroupUser', [ApiTprmController::class, 'addGroupUser']);
        Route::get('/campaigns/get-emails-by-domain/{domain?}', [ApiTprmController::class, 'getEmailsByDomain']);
    });

    //smishing campaign routes---------------------------------
    Route::prefix('smishing')->group(function () {
        Route::get('/', [ApiSmishingController::class, 'index']);
        Route::post('/create-campaign', [ApiSmishingController::class, 'createCampaign']);
        Route::get('/fetch-more-templates', [ApiSmishingController::class, 'fetchMoreTemps']);
        Route::get('/fetch-more-websites', [ApiSmishingController::class, 'fetchMoreWebsites']);
        Route::get('/search-template', [ApiSmishingController::class, 'searchTemplate']);
        Route::get('/search-website', [ApiSmishingController::class, 'searchWebsite']);
        Route::delete('/delete-campaign/{campId?}', [ApiSmishingController::class, 'deleteCampaign']);
        Route::get('/fetch-campaign-details/{campId?}', [ApiSmishingController::class, 'fetchCampDetail']);
    });

    //smishing templates routes---------------------------------
    Route::prefix('smishing-templates')->group(function () {
        Route::get('/', [ApiSmishingTemplateController::class, 'index']);
        Route::post('/add-template', [ApiSmishingTemplateController::class, 'storeTemplate']);
        Route::put('/update-template', [ApiSmishingTemplateController::class, 'updateTemplate']);
        Route::delete('/delete-template', [ApiSmishingTemplateController::class, 'deleteTemplate']);
        Route::post('/send-test-sms', [ApiSmishingTemplateController::class, 'testSms']);
    });

    // White Label routes
    Route::post('/save-white-label', [ApiWhiteLabelController::class, 'saveWhiteLabel']);
});
