<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MFAController;
use App\Http\Controllers\WhiteLabelController;
use App\Http\Controllers\Api\ApiTprmController;
use App\Http\Controllers\PhishTriageController;
use App\Http\Controllers\Api\ApiAiCallController;
use App\Http\Controllers\Api\ApiPolicyController;
use App\Http\Controllers\Api\ApiSupportController;
use App\Http\Controllers\Api\ApiCampaignController;
use App\Http\Controllers\Api\ApiQuishingController;
use App\Http\Controllers\Api\ApiSettingsController;
use App\Http\Controllers\Api\ApiSmishingController;
use App\Http\Controllers\Api\ApiDashboardController;
use App\Http\Controllers\Api\ApiEmployeesController;
use App\Http\Controllers\Api\ApiLanguagesController;
use App\Http\Controllers\Api\ApiOutlookAdController;
use App\Http\Controllers\Api\ApiReportingController;
use App\Http\Controllers\Api\ApiBlueCollarController;
use App\Http\Controllers\Api\ApiTprmReportController;
use App\Http\Controllers\Api\ApiWaCampaignController;
use App\Http\Controllers\Api\ApiWhiteLabelController;
use App\Http\Controllers\Api\InforgraphicsController;
use App\Http\Controllers\Api\ApiCompanyLogsController;
use App\Http\Controllers\LearnApi\ApiMediaController;
use App\Http\Controllers\Api\ApiIntegrationController;
use App\Http\Controllers\Api\ApiShowWebsiteController;
use App\Http\Controllers\Api\AivishingReportController;
use App\Http\Controllers\Api\ApiNewReportingController;
use App\Http\Controllers\Api\NoActivityUsersController;
use App\Http\Controllers\Api\ApiQuishingEmailController;
use App\Http\Controllers\Api\ApiSenderProfileController;
use App\Http\Controllers\Api\ApiPhishingEmailsController;
use App\Http\Controllers\Api\ApiPolicyCampaignController;
use App\Http\Controllers\Api\ApiQuishingReportController;
use App\Http\Controllers\Api\ApiTrainingModuleController;
use App\Http\Controllers\Api\ApiWhatsappReportController;
use App\Http\Controllers\Api\WhatsappTemplatesController;
use App\Http\Controllers\Api\ApiAivishingReportController;
use App\Http\Controllers\Api\ApiComplianceController;
use App\Http\Controllers\Api\ApiPhishingWebsitesController;
use App\Http\Controllers\Api\ApiSmishingTemplateController;
use App\Http\Controllers\Api\ApiDarkWebMonitoringController;
use App\Http\Controllers\Api\ApiScormTrainingController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\SSOController;
use App\Http\Controllers\LearnApi\ApiLearnBlueCollarController;
use App\Http\Controllers\LearnApi\ApiLearnController;
use App\Http\Controllers\LearnApi\ApiLearnPolicyController;
use App\Http\Controllers\LearnApi\ApiPolicyController as LearnApiApiPolicyController;

Route::get('checkwhitelabel', [WhiteLabelController::class, 'check']);


Route::post('company/create-password/token-check', [AuthenticatedSessionController::class, 'tokenCheck']);

Route::post('company/create-password', [AuthenticatedSessionController::class, 'createPassword']);

Route::post('login', [AuthenticatedSessionController::class, 'login']);


Route::get('sso/validate', [SSOController::class, 'ssoValidate']);
Route::get('sso-learner/validate', [SSOController::class, 'ssoValidateLearner']);

Route::post('forgot-password', [AuthenticatedSessionController::class, 'forgotPassword']);

Route::post('verify-otp', [AuthenticatedSessionController::class, 'verifyOTP']);

Route::post('reset-password', [AuthenticatedSessionController::class, 'resetPassword']);
Route::post('mfa/verify', [MFAController::class, 'verifyOTP']);
Route::post('logout', [AuthenticatedSessionController::class, 'logout'])->middleware('auth:api');

Route::post('/add-email-template-bulk', [ApiPhishingEmailsController::class, 'addEmailTemplateBulk']);
Route::post('/add-quishing-template-bulk', [ApiQuishingEmailController::class, 'addQuishingTemplateBulk']);


Route::get('me', [AuthenticatedSessionController::class, 'me'])->middleware('auth:api');
Route::middleware(['auth:api', 'timezone'])->group(function () {

    Route::get('/dashboard', [ApiDashboardController::class, 'index']);
    Route::get('/dashboard/campaign-card/sort-by-time', [ApiDashboardController::class, 'sortByTimeCampaignCard']);

    Route::put('/dashboard/accept-eula', [ApiDashboardController::class, 'acceptEula']);

    Route::get('/tour-taken/check', [ApiDashboardController::class, 'tourTakenCheck']);

    Route::put('/tour-taken', [ApiDashboardController::class, 'tourTaken']);

    Route::put('/save-outlook-code', [ApiOutlookAdController::class, 'saveOutlookCode']);

    Route::put('/save-outlook-dmi-code', [ApiOutlookAdController::class, 'saveOutlookDmiCode']);

    Route::get('/get-pie-data', [ApiDashboardController::class, 'getPieData']);
    Route::get('/get-line-chart-data', [ApiDashboardController::class, 'getLineChartData']);
    //old
    Route::get('/get-whats-chart-data', [ApiDashboardController::class, 'whatsappReport']);

    //new
    Route::get('/whatsapp-campaign-report', [ApiDashboardController::class, 'whatsappReportNew']);

    Route::get('/get-ai-chart-data', [ApiDashboardController::class, 'aiCallReport']);
    Route::get('/get-payload-click-data', [ApiDashboardController::class, 'getPayloadClickData']);
    Route::get('/get-email-reported-data', [ApiDashboardController::class, 'getEmailReportedData']);
    Route::get('/get-package-data', [ApiDashboardController::class, 'getPackage']);

    //old
    Route::get('/get-line-chart-2-data', [ApiDashboardController::class, 'getLineChartData2']);
    //new
    Route::get('/risk-comparison', [ApiDashboardController::class, 'riskComparison']);

    //card reports
    Route::prefix('simulation-report')->group(function () {
        Route::get('/email', [ApiDashboardController::class, 'emailSimulationReport']);
        Route::get('/quishing', [ApiQuishingReportController::class, 'quishingSimulationReport']);
        Route::get('/whatsapp', [ApiWhatsappReportController::class, 'whatsappSimulationReport']);
        Route::get('/tprm', [ApiTprmReportController::class, 'tprmSimulationReport']);
        Route::get('/aivishing', [ApiAivishingReportController::class, 'aivishingSimulationReport']);
    });


    //card reports
    Route::prefix('insight-reporting')->group(function () {
        Route::get('/', [ApiNewReportingController::class, 'index']);
        Route::get('/training-report/{training_id}', [ApiNewReportingController::class, 'trainingReport']);
    });


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

    Route::prefix('no-activity-users')->group(function () {
        Route::post('/send-training', [NoActivityUsersController::class, 'sendTrainingWithoutActivity']);
    });

    Route::prefix('assigned-trainings')->group(function () {
        Route::get('/email/{email?}', [ApiCampaignController::class, 'getAssignedTrainings']);
    });

    //quishing campaign routes
    Route::prefix('quishing-campaign')->group(function () {
        Route::get('/', [ApiQuishingController::class, 'index']);
        Route::post('/create-campaign', [ApiQuishingController::class, 'createCampaign']);
        Route::put('/relaunch/{campaign_id?}', [ApiQuishingController::class, 'relaunchCampaign']);
        Route::delete('/delete-campaign/{campaign_id?}', [ApiQuishingController::class, 'deleteCampaign']);
        Route::get('/detail/{campaign_id?}', [ApiQuishingController::class, 'campaignDetail']);
    });

    //inforgraphics 
    Route::prefix('infographics')->group(function () {
        Route::get('/', [InforgraphicsController::class, 'index']);
        Route::post('/save', [InforgraphicsController::class, 'saveInfographics']);
        Route::delete('/delete/{encodedId?}', [InforgraphicsController::class, 'deleteInfographics']);
    });

    Route::prefix('infographics-campaign')->group(function () {
        Route::get('/', [InforgraphicsController::class, 'campaignIndex']);
        Route::post('/create-campaign', [InforgraphicsController::class, 'createCampaign']);
        Route::get('/detail/{campaign_id?}', [InforgraphicsController::class, 'campaignDetail']);
        Route::delete('/delete-campaign/{campaign_id?}', [InforgraphicsController::class, 'deleteCampaign']);
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
        Route::post('/request-new-template', [ApiWaCampaignController::class, 'requestNewTemplate']);

        Route::get('/requested-templates', [ApiWaCampaignController::class, 'requestedTemplates']);

        Route::get('/requested-template/check/{template_id}', [ApiWaCampaignController::class, 'checkTemplateStatus']);

        Route::put('/relaunch/{campaign_id?}', [ApiWaCampaignController::class, 'relaunchCampaign']);
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
        Route::get('/sub-admins', [ApiSettingsController::class, 'subAdmins']);
        Route::put('/sub-admins/change-service-status', [ApiSettingsController::class, 'changeServiceStatus']);
        Route::delete('/sub-admins/delete-sub-admin', [ApiSettingsController::class, 'deleteSubAdmin']);
        Route::post('/add-sub-admin', [ApiSettingsController::class, 'addSubAdmin']);
    });

    Route::prefix('sender-profiles')->group(function () {
        //old
        Route::get('/', [ApiSenderProfileController::class, 'index']);
        //new
        Route::get('/index', [ApiSenderProfileController::class, 'index2']);
        Route::post('/add', [ApiSenderProfileController::class, 'addSenderProfile']);
        Route::post('/save/manual', [ApiSenderProfileController::class, 'saveManualSenderProfile']);
        Route::post('/save/managed', [ApiSenderProfileController::class, 'saveManagedSenderProfile']);
        Route::delete('/delete', [ApiSenderProfileController::class, 'deleteSenderProfile']);
        Route::put('/update/{id?}', [ApiSenderProfileController::class, 'updateSenderProfile']);
    });

    Route::prefix('integration')->group(function () {
        Route::get('/', [ApiIntegrationController::class, 'index']);
    });

    Route::prefix('training-module')->group(function () {
        Route::get('/', [ApiTrainingModuleController::class, 'trainings']);
        Route::get('/training_page', [ApiTrainingModuleController::class, 'trainingPage']);
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

    Route::prefix('report')->group(function () {

        Route::get('/whatsappfetch-camp-training-details-individual/{campaignId?}', [ApiReportingController::class, 'whatsappfetchCampTrainingDetailsIndividual']);

        // Awareness and Education Reporting
        Route::get('/fetch-awareness-edu-report', [ApiReportingController::class, 'fetchAwarenessEduReport']);

        Route::get('/fetch-awareness-edu-reporting', [ApiReportingController::class, 'fetchAwarenessEduReporting']);

        // Division Reporting
        Route::get('/fetch-division-users-report', [ApiReportingController::class, 'fetchDivisionUsersReport']);

        Route::get('/fetch-division-users-reporting', [ApiReportingController::class, 'fetchDivisionUsersReporting']);

        // Users Reporting
        Route::get('/fetch-users-report', [ApiReportingController::class, 'fetchUsersReport']);

        Route::get('/fetch-users-reporting', [ApiReportingController::class, 'fetchUsersReporting']);

        // Training Reporting
        Route::get('/fetch-training-report', [ApiReportingController::class, 'fetchTrainingReport']);

        Route::get('/fetch-training-reporting', [ApiReportingController::class, 'fetchTrainingReporting']);

        // Games Reporting
        Route::get('/fetch-games-report', [ApiReportingController::class, 'fetchGamesReport']);

        // Policy Reporting
        Route::get('/fetch-policies-report', [ApiReportingController::class, 'fetchPoliciesReport']);

        Route::get('/fetch-policies-reporting', [ApiReportingController::class, 'fetchPoliciesReporting']);

        // Course Summary Reporting
        Route::get('/fetch-course-summary-report', [ApiReportingController::class, 'fetchCourseSummaryReport']);

        Route::prefix('compliance')->group(function () {

            Route::get('/', [ApiComplianceController::class, 'index']);
            //general compliance report
            Route::get('/generate-compliance-report', [ApiComplianceController::class, 'generateComplianceReport']);
            Route::get('/soc2-report', [ApiComplianceController::class, 'soc2Report']);
            Route::get('/iso27001-report', [ApiComplianceController::class, 'iso27001Report']);
            Route::get('/hipaa-report', [ApiComplianceController::class, 'hipaaReport']);
            Route::get('/gdpr-report', [ApiComplianceController::class, 'gdprReport']);
            Route::get('/pdpl-report/{region}', [ApiComplianceController::class, 'pdplReport']);
            Route::get('/nist-sp800-50-report', [ApiComplianceController::class, 'nistSp80050Report']);
            Route::get('/nist-sp800-53-report', [ApiComplianceController::class, 'nistSp80053Report']);
            Route::get('/pci-dss-report', [ApiComplianceController::class, 'pciDssReport']);
            Route::get('/iso20000-report', [ApiComplianceController::class, 'iso20000Report']);
            Route::get('/qcsf-report', [ApiComplianceController::class, 'qcsfReport']);
            Route::get('/ocert-report', [ApiComplianceController::class, 'ocertReport']);
        });
    });

    Route::prefix('employees')->group(function () {
        Route::get('/', [ApiEmployeesController::class, 'index']);
        Route::get('/groups', [ApiEmployeesController::class, 'getGroups']);
        Route::get('/all-employees', [ApiEmployeesController::class, 'allEmployee']);
        Route::get('/blue-collar-employees', [ApiBlueCollarController::class, 'index']);
        Route::get('/normalemployees', [ApiEmployeesController::class, 'index']);
        Route::get('/employee/{base_encode_id?}', [ApiEmployeesController::class, 'employeeDetail']);
        Route::get('/detail/{email?}', [ApiEmployeesController::class, 'employeeDetailNew']);
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
        Route::put('/update/{email?}', [ApiEmployeesController::class, 'updateEmployee']);
    });
    Route::prefix('ai-agents')->group(function () {
        Route::get('/', [ApiAiCallController::class, 'aiAgents']);
        Route::post('/test-agent', [ApiAiCallController::class, 'testAiAgent']);
        Route::delete('/delete-agent/{agent_id}', [ApiAiCallController::class, 'deleteAiAgent']);
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
        Route::put('/relaunch/{campaign_id?}', [ApiAiCallController::class, 'relaunchCampaign']);
    });

    Route::prefix('phishing-emails')->group(function () {
        Route::get('/', [ApiPhishingEmailsController::class, 'index']);
        Route::get('/get-template-by-id/{id?}', [ApiPhishingEmailsController::class, 'getTemplateById']);
        Route::get('/search-email-template', [ApiPhishingEmailsController::class, 'searchPhishingEmails']);
        Route::post('/add-email-template', [ApiPhishingEmailsController::class, 'addEmailTemplate']);
        Route::post('/generate-template', [ApiPhishingEmailsController::class, 'generateTemplate']);
        Route::post('/save-ai-phish-template', [ApiPhishingEmailsController::class, 'saveAIPhishTemplate']);
        Route::post('/update-email-template', [ApiPhishingEmailsController::class, 'updateTemplate']);
        Route::delete('/delete-email-template', [ApiPhishingEmailsController::class, 'deleteTemplate']);
        Route::post('/duplicate/{id?}', [ApiPhishingEmailsController::class, 'duplicate']);
    });

    Route::prefix('quishing-emails')->group(function () {
        Route::get('/', [ApiQuishingEmailController::class, 'index']);
        Route::post('/add-temp', [ApiQuishingEmailController::class, 'addTemplate']);
        Route::get('/get-template-by-id/{id?}', [ApiQuishingEmailController::class, 'getTemplateById']);
        Route::delete('/delete-temp', [ApiQuishingEmailController::class, 'deleteTemplate']);
        Route::post('/update-temp', [ApiQuishingEmailController::class, 'updateTemplate']);
        Route::post('/duplicate/{id?}', [ApiQuishingEmailController::class, 'duplicate']);
    });

    Route::prefix('languages')->group(function () {
        Route::get('/', [ApiLanguagesController::class, 'index']);
    });

    Route::prefix('phishing-website')->group(function () {
        Route::get('/all', [ApiPhishingWebsitesController::class, 'getAll']);
        Route::get('/', [ApiPhishingWebsitesController::class, 'index']);
        Route::delete('/delete/{encodedId?}', [ApiPhishingWebsitesController::class, 'deleteWebsite']);
        Route::post('/add', [ApiPhishingWebsitesController::class, 'addPhishingWebsite']);
        Route::get('/get-website-by-id/{id?}', [ApiPhishingWebsitesController::class, 'getWebsiteById']);
        Route::post('/update-website', [ApiPhishingWebsitesController::class, 'updateWebsite']);
        Route::post('/generate', [ApiPhishingWebsitesController::class, 'generateWebsite']);
        Route::post('/check-website-for-clone', [ApiPhishingWebsitesController::class, 'checkWebsiteForClone']);
        Route::post('/clone-website', [ApiPhishingWebsitesController::class, 'cloneWebsite']);
        Route::post('/save-cloned-website', [ApiPhishingWebsitesController::class, 'saveClonedWebsite']);

        Route::delete('/delete-cloned-website/{encodedId?}', [ApiPhishingWebsitesController::class, 'deleteClonedWebsite']);

        Route::get('/cloned-websites', [ApiPhishingWebsitesController::class, 'getClonedWebsites']);
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
        Route::put('/campaigns/relaunch/{campId?}', [ApiTprmController::class, 'relaunchCampaign']);
        Route::get('/campaigns/fetch-phish-data', [ApiTprmController::class, 'fetchPhishData']);
        Route::get('/treporting/fetch-campaign-report/{campaignId?}', [ApiReportingController::class, 'tfetchCampaignReport']);
        Route::get('/tfetch-camp-report-by-users/{campaignId?}', [ApiReportingController::class, 'tfetchCampReportByUsers']);
        Route::post('/campaigns/fetchEmail', [ApiTprmController::class, 'fetchEmail']);
        Route::post('/campaigns/addGroupUser', [ApiTprmController::class, 'addGroupUser']);
        Route::get('/campaigns/get-emails-by-domain/{domain?}', [ApiTprmController::class, 'getEmailsByDomain']);
        Route::delete('/delete-tprm-emp-by-email', [ApiTprmController::class, 'deleteTprmUserByEmail']);
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

    //phish triage
    Route::prefix('phish-triage')->group(function () {
        Route::get('/email-reported', [PhishTriageController::class, 'emailsReported']);
        Route::get('/resolved', [PhishTriageController::class, 'emailsResolved']);

        Route::put('/ai-analysis', [PhishTriageController::class, 'aiAnalysis']);
        Route::get('/domain-analysis/{domain}', [PhishTriageController::class, 'domainAnalysis']);

        Route::get('/auth', [PhishTriageController::class, 'redirect']);
        Route::put('/callback', [PhishTriageController::class, 'callback']);
        Route::get('/emails', [PhishTriageController::class, 'listEmails']);
        Route::post('/email/action', [PhishTriageController::class, 'performAction']);
        Route::post('/email/findmsgid', [PhishTriageController::class, 'findMessageId']);
    });

    //policy
    Route::prefix('policy')->group(function () {
        Route::get('/', [ApiPolicyController::class, 'index']);
        Route::post('/add-policy', [ApiPolicyController::class, 'addPolicy']);
        Route::post('/edit-policy', [ApiPolicyController::class, 'editPolicy']);
        Route::get('/fetch-assigned-policy', [ApiPolicyController::class, 'fetchAssignedPolicy']);
        Route::put('/accept-policy', [ApiPolicyController::class, 'acceptPolicy']);
        Route::delete('/delete-policy/{encoded_id?}', [ApiPolicyController::class, 'deletePolicy']);
    });

    //policy campaign
    Route::prefix('policy-campaign')->group(function () {
        Route::post('/create-campaign', [ApiPolicyCampaignController::class, 'create']);
        Route::get('/detail', [ApiPolicyCampaignController::class, 'detail']);
        Route::delete('/delete-policy-campaign/{campaign_id?}', [ApiPolicyController::class, 'deletePolicyCampaign']);
    });

    //company logs
    Route::prefix('company-logs')->group(function () {
        Route::get('/', [ApiCompanyLogsController::class, 'index']);
    });

    // Media
    Route::prefix('media')->group(function () {
        Route::post('/upload_file', [ApiMediaController::class, 'uploadFile']);
        Route::get('/fetch_files', [ApiMediaController::class, 'fetchFiles']);
        Route::delete('/delete-file', [ApiMediaController::class, 'deleteFile']);

        Route::post('/splitFileIntoChunks', [ApiMediaController::class, 'splitFileIntoChunks']);
    });

    // Scorm Training
    Route::prefix('scorm-training')->group(function () {
        Route::post('/add-scorm-training', [ApiScormTrainingController::class, 'addScormTraining']);
        Route::get('fetch-scorm-trainings', [ApiScormTrainingController::class, 'fetchScormTrainings']);
        Route::delete('delete-scorm-trainings', [ApiScormTrainingController::class, 'deleteScormTrainings']);
        Route::get('view-scorm-training', [ApiScormTrainingController::class, 'viewScormTraining']);
    });
});

Route::prefix('learn')->group(function () {
    Route::get('/login-with-token', [ApiLearnController::class, 'loginWithToken']);
    Route::post('/create-new-token', [ApiLearnController::class, 'createNewToken']);

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

    // For Blue Collar
    Route::prefix('blue-collar')->group(function () {
        Route::post('/create-new-token', [ApiLearnBlueCollarController::class, 'createNewToken']);
        Route::get('/login-with-token', [ApiLearnBlueCollarController::class, 'loginWithToken']);

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
    });
});
