<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MailController;
use App\Http\Controllers\TprmController;
use App\Http\Controllers\AicallController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeesController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\TestUploadController;
use App\Http\Controllers\ShowWebsiteController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\SenderProfileController;
use App\Http\Controllers\PhishingEmailsController;
use App\Http\Controllers\TrainingModuleController;
use App\Http\Controllers\BrandMonitoringController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\WhiteLabelController;
use App\Http\Controllers\PhishingWebsitesController;
use App\Http\Controllers\WhatsappCampaignController;
use App\Http\Controllers\Learner\LearnerAuthController;
use App\Http\Controllers\Learner\LearnerDashController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPhishingEmailController;
use App\Http\Controllers\Admin\AdminSenderProfileController;
use App\Http\Controllers\Admin\AdminTrainingModuleController;
use App\Http\Controllers\Admin\AdminPhishingWebsiteController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\WhatsAppController;
use App\Http\Controllers\AiTrainingController;
use App\Http\Controllers\PublicInfoController;
use App\Http\Controllers\ScrumPackageController;

use App\Http\Middleware\CorsMiddleware;

Route::middleware([CorsMiddleware::class])->get('/public-info', function () {
    return response()->json(['message' => 'This is public information.']);
});


//---------------learning portal routes------------//

Route::domain('learn.simuphish.com')->group(function () {

    Route::get('/', [LearnerAuthController::class, 'index'])->name('learner.loginPage');
    Route::post('/login', [LearnerAuthController::class, 'login'])->name('learner.login');

    Route::middleware('isLearnerLoggedIn')->group(function () {

        Route::get('/dashboard', [LearnerDashController::class, 'index'])->name('learner.dashboard');
        Route::get('/training/{training_id}/{training_lang}/{id}', [LearnerDashController::class, 'startTraining'])->name('learner.start.training');

        Route::get('/ai-training/{topic}/{language}/{id}', [LearnerDashController::class, 'startAiTraining'])->name('learner.start.ai.training');

        Route::get('/loadTrainingContent/{training_id}/{training_lang}', [LearnerDashController::class, 'loadTraining'])->name('learner.load.training');

        Route::get('/load-ai-training/{topic}', [AiTrainingController::class, 'generateTraining'])->name('generate.training');
        Route::post('/ai-training/translate-quiz', [AiTrainingController::class, 'translateAiTraining'])->name('translate.ai.training');

        Route::post('/update-training-score', [LearnerDashController::class, 'updateTrainingScore'])->name('learner.update.score');
        Route::post('/download-certificate', [LearnerDashController::class, 'downloadCertificate'])->name('learner.download.cert');
        Route::get('/logout', [LearnerAuthController::class, 'logout'])->name('learner.logout');
    });
});



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

    // Route::get('/{websitefile}?sessionid={anysessionid}&token={anytoken}&usrid={anyuser}', [ShowWebsiteController::class, 'index']);

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

    //route for whatsapp campaign
    Route::get('/c/{campaign_id}', [WhatsappCampaignController::class, 'showWebsite']);
    Route::post('/c/update-payload', [WhatsappCampaignController::class, 'updatePayload'])->name('whatsapp.update.payload');
    Route::post('/c/assign-training', [WhatsappCampaignController::class, 'assignTraining'])->name('whatsapp.assign.training');
    Route::post('/c/update-emp-comp', [WhatsappCampaignController::class, 'updateEmpComp'])->name('whatsapp.update.emp.comp');
    Route::get('/c/alert/user', function () {
        return view('whatsapp-alert');
    })->name('whatsapp.phish.alert');
});

Route::get('/', function () {
    return redirect()->route('login');
});

// ---------------------company route---------------------//



Route::middleware(['auth', 'checkWhiteLabel'])->group(function () {
    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // index page routes-----------------------------------------------------

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/get-pie-data', [DashboardController::class, 'getPieData'])->name('get.pie.data');
    Route::get('/get-line-chart-data', [DashboardController::class, 'getLineChartData'])->name('get.line.chart.data');
    Route::get('/get-total-assets', [DashboardController::class, 'getTotalAssets'])->name('get.total.assets');
    Route::get('/whatsappreport-chart-data', [DashboardController::class, 'whatsappReport']);
    Route::get('/dash/get-payload-click-data', [DashboardController::class, 'getPayloadClickData']);
    Route::get('/dash/get-emailreported-data', [DashboardController::class, 'getEmailReportedData']);
    Route::post('/dash/reqNewLimit', [DashboardController::class, 'reqNewLimit'])->name('reqNewLimit');

    //campaigns page routes------------------------------------------------------

    Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns');
    Route::post('/campaigns/create', [CampaignController::class, 'createCampaign'])->name('campaigns.create');
    Route::post('/campaigns/delete', [CampaignController::class, 'deleteCampaign'])->name('campaigns.delete');
    Route::post('/campaigns/fetch-campaign-detail', [CampaignController::class, 'fetchCampaignDetail'])->name('campaigns.detail');
    Route::post('/campaigns/fetch-training-individual', [CampaignController::class, 'fetchTrainingIndividual'])->name('campaigns.fetch.training.individual');
    Route::post('/campaigns/relaunch', [CampaignController::class, 'relaunchCampaign'])->name('campaigns.relaunch');
    Route::post('/campaigns/fetch-phish-data', [CampaignController::class, 'fetchPhishData'])->name('campaigns.fetch.phish.data');
    Route::post('/campaigns/reschedule', [CampaignController::class, 'rescheduleCampaign'])->name('reschedule.campaign');
    Route::post('/campaigns/send-training-reminder', [CampaignController::class, 'sendTrainingReminder'])->name('campaign.send.training.reminder');
    Route::post('/campaigns/complete-training', [CampaignController::class, 'completeTraining'])->name('campaign.complete.training');
    Route::post('/campaigns/remove-training', [CampaignController::class, 'removeTraining'])->name('campaign.remove.training');


    //whatsapp Campaign
    Route::get('/whatsapp-campaign', [WhatsappCampaignController::class, 'index'])->name('whatsapp.campaign');
    Route::get('/whatsapp-templates', [WhatsappCampaignController::class, 'getTemplates'])->name('whatsapp.templates');
    Route::post('/whatsapp-submit-campaign', [WhatsappCampaignController::class, 'submitCampaign'])->name('whatsapp.submitCampaign');
    Route::post('/whatsapp-delete-campaign', [WhatsappCampaignController::class, 'deleteCampaign'])->name('whatsapp.deleteCampaign');
    Route::post('/whatsapp-fetch-campaign', [WhatsappCampaignController::class, 'fetchCampaign'])->name('whatsapp.fetchCamp');

    Route::post('/whatsapp-new-template', [WhatsappCampaignController::class, 'newTemplate'])->name('whatsapp.newTemplate');

    //employees route-------------------------------------------------------------

    Route::get('/employees', [EmployeesController::class, 'index'])->name('employees');
    Route::post('/employees/send-domain-verify-otp', [EmployeesController::class, 'sendDomainVerifyOtp'])->name('sendDomainVerificationOtp');

    Route::post('/employees/otp-verify', [EmployeesController::class, 'verifyOtp'])->name('domain.otpverify');
    Route::post('/employees/delete-domain', [EmployeesController::class, 'deleteDomain'])->name('domain.delete');


    Route::post('/employees/newGroup', [EmployeesController::class, 'newGroup'])->name('employee.newgroup');
    Route::get('/employees/viewUsers/{groupid}', [EmployeesController::class, 'viewUsers'])->name('employee.viewUsers');
    Route::post('/employees/deleteUser', [EmployeesController::class, 'deleteUser'])->name('employee.deleteUser');
    Route::post('employees/update-whatsapp-number', [EmployeesController::class, 'updateWhatsapp'])->name('employee.updatewhatsapp');
    Route::post('/employees/addUser', [EmployeesController::class, 'addUser'])->name('employee.addUser');
    Route::post('/employees/importCsv', [EmployeesController::class, 'importCsv'])->name('employee.importCsv');
    Route::post('/employees/deleteGroup', [EmployeesController::class, 'deleteGroup'])->name('employee.deleteGroup');

    Route::get('/employees/check-ldap-ad-config', [EmployeesController::class, 'checkAdConfig'])->name('employee.check.ldap.ad.config');

    Route::post('/employees/save-ldap-config', [EmployeesController::class, 'saveLdapConfig'])->name('employee.save.ldap.config');

    Route::post('/employees/add-ldap-config', [EmployeesController::class, 'addLdapConfig'])->name('employee.add.ldap.config');

    Route::get('/employees/sync-ldap-directory', [EmployeesController::class, 'syncLdap'])->name('employee.sync.ldap');

    //reporting routes-----------------------------------------------------------------

    Route::get('/reporting', [ReportingController::class, 'index'])->name('campaign.reporting');
    Route::get('/reporting/get-chart-data', [ReportingController::class, 'getChartData'])->name('campaign.getChartData');
    Route::get('/reporting/wget-chart-data', [ReportingController::class, 'wgetChartData'])->name('campaign.wgetChartData');
    Route::get('/reporting/cget-chart-data', [ReportingController::class, 'cgetChartData'])->name('campaign.cgetChartData');
    Route::post('/reporting/fetch-campaign-report', [ReportingController::class, 'fetchCampaignReport'])->name('campaign.fetchCampaignReport');
    Route::post('/reporting/whatsappfetch-campaign-report', [ReportingController::class, 'whatsappfetchCampaignReport'])->name('campaign.whatsappfetchCampaignReport');
    Route::post('/reporting/aicallingfetch-campaign-report', [ReportingController::class, 'aicallingfetchCampaignReport'])->name('campaign.aicallingfetchCampaignReport');

    Route::post('/fetch-camp-report-by-users', [ReportingController::class, 'fetchCampReportByUsers'])->name('campaign.fetchCampReportByUsers');
    Route::post('/aicallingfetch-camp-report-by-users', [ReportingController::class, 'aicallingfetchCampReportByUsers'])->name('campaign.aicallingfetchCampReportByUsers');
    Route::post('/whatsappfetch-camp-report-by-users', [ReportingController::class, 'whatsappfetchCampReportByUsers'])->name('campaign.whatsappfetchCampReportByUsers');

    Route::post('/fetch-camp-training-details', [ReportingController::class, 'fetchCampTrainingDetails'])->name('campaign.fetchCampTrainingDetails');
    Route::post('/aicallingfetch-camp-training-details', [ReportingController::class, 'aicallingfetchCampTrainingDetails'])->name('campaign.aicallingfetchCampTrainingDetails');
    Route::post('/whatsappfetch-camp-training-details', [ReportingController::class, 'whatsappfetchCampTrainingDetails'])->name('campaign.whatsappfetchCampTrainingDetails');
   
    Route::post('/fetch-camp-training-details-individual', [ReportingController::class, 'fetchCampTrainingDetailsIndividual'])->name('campaign.fetchCampTrainingDetailsIndividual');
    Route::post('/aicallingfetch-camp-training-details-individual', [ReportingController::class, 'aicallingfetchCampTrainingDetailsIndividual'])->name('campaign.aicallingfetchCampTrainingDetailsIndividual');
    Route::post('/whatsappfetch-camp-training-details-individual', [ReportingController::class, 'whatsappfetchCampTrainingDetailsIndividual'])->name('campaign.whatsappfetchCampTrainingDetailsIndividual');

    //---------------------TPRM routes----------------------//
    Route::get('/tprm', [TprmController::class, 'index'])->name('campaign.tprm');
   
    Route::post('/submit-domains', [TprmController::class, 'submitdomains'])->name('submit-domains');
    Route::get('/test', [TprmController::class, 'test'])->name('test');
    Route::post('/tprm/otp-verify', [TprmController::class, 'verifyOtp'])->name('domain.otpverify.tprm');
    Route::post('/tprm/delete-domain', [TprmController::class, 'deleteDomain'])->name('domain.delete.tprm');
   
    //-------------------------------------TPRM routes for champaingns----------------------//
    
    Route::get('/tprmcampaigns', [TprmController::class, 'index'])->name('tprmcampaigns');
    Route::post('/tprmcampaigns/create', [TprmController::class, 'createCampaign'])->name('tprmcampaigns.create');
    Route::post('/tprmcampaigns/delete', [TprmController::class, 'deleteCampaign'])->name('tprmcampaigns.delete');
    Route::post('/tprmcampaigns/relaunch', [TprmController::class, 'relaunchCampaign'])->name('tprmcampaigns.relaunch');
    Route::post('/tprmcampaigns/fetch-phish-data', [TprmController::class, 'fetchPhishData'])->name('tprmcampaigns.fetch.phish.data');
    Route::post('/tprmcampaigns/reschedule', [TprmController::class, 'rescheduleCampaign'])->name('tprmreschedule.campaign');
    Route::post('/treporting/fetch-campaign-report', [ReportingController::class, 'tfetchCampaignReport'])->name('tprmcampaign.fetchCampaignReport');
    Route::post('/tfetch-camp-report-by-users', [ReportingController::class, 'tfetchCampReportByUsers'])->name('tprmcampaign.fetchCampReportByUsers');
    Route::get('/test-route', function () {return 'Test route reached!';});
   Route::post('/tprmcampaigns/fetchEmail', [TprmController::class, 'fetchEmail'])->name('tprmcampaigns.fetchEmail');
   Route::post('/tprmcampaigns/tprmnewGroup', [TprmController::class, 'tprmnewGroup'])->name('tprmcampaigns.tprmnewGroup');
   Route::post('/tprmcampaigns/emailtprmnewGroup', [TprmController::class, 'emailtprmnewGroup'])->name('tprmcampaigns.emailtprmnewGroup');
   Route::get('/tprmcampaigns/emails/{domain}', [TprmController::class, 'getEmailsByDomain'])->name('tprmcampaigns.getEmailsByDomain');

    //Ai Calling routes ----------------------------------------------------------------------
    Route::get('/ai-calling', [AicallController::class, 'index'])->name('ai.calling');
    Route::post('/ai-calling/submit-req', [AicallController::class, 'submitReq'])->name('ai.calling.sub.req');
    Route::post('/ai-calling/create-campaign', [AicallController::class, 'createCampaign'])->name('ai.call.create.campaign');
    Route::get('/ai-calling/view-campaign/{id}', [AicallController::class, 'viewCampaign'])->name('ai.call.view.campaign');
    Route::post('/ai-calling/delete-campaign', [AicallController::class, 'deleteCampaign'])->name('ai.call.delete.campaign');
    Route::get('/ai-calling/get-agents', [AicallController::class, 'getAgents'])->name('ai.call.get.agents');
    Route::get('/ai-calling/fetch-call-report/{callid}', [AicallController::class, 'fetchCallReport'])->name('ai.call.fetch.call.report');


    //phishing emails route---------------------------------------------------------------

    Route::get('/phishing-emails', [PhishingEmailsController::class, 'index'])->name('phishing.emails');
    Route::post('/phishing-email', [PhishingEmailsController::class, 'getTemplateById'])->name('phishing.getTemplateById');
    Route::get('/search-email-template', [PhishingEmailsController::class, 'searchPhishingEmails'])->name('phishingEmails.search');
    Route::post('/add-email-template', [PhishingEmailsController::class, 'addEmailTemplate'])->name('addEmailTemplate');
    Route::post('/generate-template', [PhishingEmailsController::class, 'generateTemplate']);
    Route::post('/save-ai-phish-template', [PhishingEmailsController::class, 'saveAIPhishTemplate']);
    Route::post('/update-email-template', [PhishingEmailsController::class, 'updateTemplate'])->name('phishing.update');
    Route::post('/delete-email-template', [PhishingEmailsController::class, 'deleteTemplate'])->name('phishing.template.delete');


    //phishing websites routes-------------------------------------------------------------

    Route::get('/phishing-websites', [PhishingWebsitesController::class, 'index'])->name('phishing.websites');
    Route::post('/delete-website', [PhishingWebsitesController::class, 'deleteWebsite'])->name('phishing.website.delete');
    Route::get('/search-website', [PhishingWebsitesController::class, 'searchWebsite'])->name('phishingWebsites.search');
    Route::post('/add-phishing-website', [PhishingWebsitesController::class, 'addPhishingWebsite'])->name('phishing.website.add');
    Route::post('/save-generate-phishing-website', [PhishingWebsitesController::class, 'saveGeneratedSite'])->name('phishing.website.saveGeneratedSite');


    //sender profiles routes-----------------------------------------------------------------
    Route::get('/sender-profiles', [SenderProfileController::class, 'index'])->name('senderprofile.index');
    Route::post('/delete-sender-profile', [SenderProfileController::class, 'deleteSenderProfile'])->name('senderprofile.delete');
    Route::post('/add-sender-profile', [SenderProfileController::class, 'addSenderProfile'])->name('senderprofile.add');
    Route::get('/get-sender-profile/{id}', [SenderProfileController::class, 'getSenderProfile'])->name('senderprofile.get');

    Route::post('/update-sender-profile', [SenderProfileController::class, 'updateSenderProfile'])->name('senderprofile.update');

    //training module routes-------------------------------------------------------------------------
    Route::get('/training-modules', [TrainingModuleController::class, 'index'])->name('trainingmodule.index');
    Route::post('/add-training-module', [TrainingModuleController::class, 'addTraining'])->name('trainingmodule.add');
    Route::get('/get-training-module/{id}', [TrainingModuleController::class, 'getTrainingById'])->name('trainingmodule.getTrainingById');

    Route::post('/update-training-module', [TrainingModuleController::class, 'updateTrainingModule'])->name('trainingmodule.update');
    Route::post('/delete-training-module', [TrainingModuleController::class, 'deleteTraining'])->name('trainingmodule.delete');

    Route::get('/training-preview/{trainingid}', [TrainingModuleController::class, 'trainingPreview'])->name('trainingmodule.preview');

    Route::get('/training-preview-content/{trainingid}/{lang}', [TrainingModuleController::class, 'loadPreviewTrainingContent'])->name('trainingmodule.preview.content');
    
    
     

    //support routes ------------------------------------------------------------------------------------
    Route::get('/support', [SupportController::class, 'index'])->name('company.support');
    Route::post('/support/create-ticket', [SupportController::class, 'createTicket'])->name('support.createTicket');
    Route::post('/support/load-conversations', [SupportController::class, 'loadConversations'])->name('support.loadConversation');
    Route::post('/support/submit-reply', [SupportController::class, 'submitReply'])->name('support.submitReply');

    //brand monitoring routes
    Route::get('/brand-monitoring', [BrandMonitoringController::class, 'index'])->name('brand.monitoring');

    Route::get('/scans/{sid}/domains', [BrandMonitoringController::class, 'fetchDomains']);
    Route::get('/scans/{sid}', [BrandMonitoringController::class, 'pollScan']);
    Route::post('/scans', [BrandMonitoringController::class, 'createScan']);
    Route::post('/scans/{sid}/stop', [BrandMonitoringController::class, 'stopScan']);
    Route::get('/scans/{sid}/list', [BrandMonitoringController::class, 'getScanList']);
    Route::get('/scans/{sid}/csv', [BrandMonitoringController::class, 'downloadCSV']);
    Route::get('/scans/{sid}/json', [BrandMonitoringController::class, 'downloadJSON']);


    ///settings route------------------------------------------------------------------------------------

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/update-profile', [SettingsController::class, 'updateProfile'])->name('settings.update.profile');
    Route::post('/settings/update-password', [SettingsController::class, 'updatePassword'])->name('settings.update.password');
    Route::post('/settings/update-mfa', [SettingsController::class, 'updateMFA'])->name('settings.update.mfa');
    Route::post('/settings/verify-mfa', [SettingsController::class, 'verifyMFA'])->name('settings.verify.mfa');
    Route::post('/settings/update-lang', [SettingsController::class, 'updateLang'])->name('settings.update.lang');
    Route::post('/settings/update-phish-edu', [SettingsController::class, 'updatePhishingEdu'])->name('settings.update.phish.edu');
    Route::post('/settings/update-train-freq', [SettingsController::class, 'updateTrainFreq'])->name('settings.update.train.freq');
    Route::post('/settings/update-reporting', [SettingsController::class, 'updateReporting'])->name('settings.update.reporting');
    Route::post('/settings/acc-dectivate', [SettingsController::class, 'deactivateAccount'])->name('settings.acc.deactivate');

    //
    Route::get('/auth-user', function () {
        $companyid = Auth::user()->company_id;
        $comp_settings = DB::table('company_settings')->where('company_id', $companyid)->get();
        return $comp_settings;
    })->name('auth-user');
});


// ---------------------company route---------------------//

Route::post('/ai-calling/log-call-detail', [AicallController::class, 'logCallDetail'])->name('ai.call.log.call');



//------------------------admin route----------------------//

Route::get('/admin', function () {
    if (!Auth::guard('admin')->check()) {
        return redirect()->route('admin.login');
    } else {

        return redirect()->route('admin.dashboard');
    }
});

Route::get('admin/login', [AdminLoginController::class, 'showLoginPage'])
    ->name('admin.login');

Route::post('admin/login', [AdminLoginController::class, 'doAdminLogin'])
    ->name('admin.doLogin');

Route::middleware(['isAdminLoggedIn'])->group(function () {

    Route::get('admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    //-----------------partners route ------------------------//

    Route::get('admin/partners', [PartnerController::class, 'index'])->name('admin.partners');
    Route::post('admin/approve-partner', [PartnerController::class, 'approvePartner'])->name('admin.approvePartner');
    Route::post('admin/hold-service', [PartnerController::class, 'holdService'])->name('admin.holdService');
    Route::post('admin/start-service', [PartnerController::class, 'startService'])->name('admin.startService');
    Route::post('admin/reject-approval', [PartnerController::class, 'rejectApproval'])->name('admin.rejectApproval');
    Route::post('admin/delete-partner', [PartnerController::class, 'deletePartner'])->name('admin.deletePartner');
    Route::post('admin/add-notice', [PartnerController::class, 'addNotice'])->name('admin.addNotice');
    Route::post('admin/delete-notice', [PartnerController::class, 'deleteNotice'])->name('admin.deleteNotice');

    Route::post('admin/create-partner', [PartnerController::class, 'createPartner'])->name('admin.createPartner');

    //-----------------partners route ------------------------//

    //-------------------companies route ---------------------//
    Route::get('admin/companies', [CompanyController::class, 'index'])->name('admin.companies');
    Route::post('/admin/companies/approve', [CompanyController::class, 'approveCompany'])->name('admin.companies.approve');
    Route::post('/admin/companies/reject', [CompanyController::class, 'rejectApproval'])->name('admin.companies.reject');
    Route::post('/admin/companies/delete', [CompanyController::class, 'deleteCompany'])->name('admin.companies.delete');

    //-------------------companies route ---------------------//


    //-----------------whatsapp routes-------------------------//

    Route::get('admin/whatsapp', [WhatsAppController::class, 'index'])->name('admin.whatsapp');
    Route::post('admin/whatsapp/approve', [WhatsAppController::class, 'approveNumberChange'])->name('admin.whatsappnumber.change');

    //-----------------whitelabel requests route--------------//
    Route::get('admin/whitelabel-req', [WhiteLabelController::class, 'index'])->name('admin.whitelabel');
    Route::post('admin/approve-whitelabel', [WhiteLabelController::class, 'approveWhitelabel'])->name('admin.whitelabel.approve');
    Route::post('admin/stop-whitelabel', [WhiteLabelController::class, 'stopWhitelabel'])->name('admin.whitelabel.stop');
    Route::post('admin/reject-whitelabel', [WhiteLabelController::class, 'rejectWhitelabel'])->name('admin.whitelabel.reject');
    //-----------------whitelabel requests route--------------//

    //----------------phishing emails route ----------------------//

    Route::get('admin/phishing-emails', [AdminPhishingEmailController::class, 'index'])->name('admin.phishingEmails');

    Route::post('admin/phishing-email', [AdminPhishingEmailController::class, 'getTemplateById'])->name('admin.phishing.getTemplateById');
    Route::post('admin/add-email-template', [AdminPhishingEmailController::class, 'addEmailTemplate'])->name('admin.addEmailTemplate');
    Route::post('admin/update-email-template', [AdminPhishingEmailController::class, 'updateTemplate'])->name('admin.phishing.update');
    Route::post('admin/delete-email-template', [AdminPhishingEmailController::class, 'deleteTemplate'])->name('admin.phishing.template.delete');

    //----------------phishing emails route ----------------------//

    //----------------phishing websites route -------------------------//

    Route::get('admin/phishing-websites', [AdminPhishingWebsiteController::class, 'index'])->name('admin.phishing.websites');
    Route::post('admin/delete-website', [AdminPhishingWebsiteController::class, 'deleteWebsite'])->name('admin.phishing.website.delete');
    Route::post('admin/add-phishing-website', [AdminPhishingWebsiteController::class, 'addPhishingWebsite'])->name('admin.phishing.website.add');

    //----------------phishing websites route -------------------------//


    //----------------------sender profiles route ----------------------//

    Route::get('admin/sender-profiles', [AdminSenderProfileController::class, 'index'])->name('admin.senderprofile.index');
    Route::post('admin/delete-sender-profile', [AdminSenderProfileController::class, 'deleteSenderProfile'])->name('admin.senderprofile.delete');
    Route::post('admin/add-sender-profile', [AdminSenderProfileController::class, 'addSenderProfile'])->name('admin.senderprofile.add');
    Route::get('admin/get-sender-profile/{id}', [AdminSenderProfileController::class, 'getSenderProfile'])->name('admin.senderprofile.get');

    Route::post('admin/update-sender-profile', [AdminSenderProfileController::class, 'updateSenderProfile'])->name('admin.senderprofile.update');

    //----------------------sender profiles route ----------------------//


    //---------------------------training module route -----------------//

    Route::get('admin/training-modules', [AdminTrainingModuleController::class, 'index'])->name('admin.trainingmodule.index');
    Route::post('admin/add-training-module', [AdminTrainingModuleController::class, 'addTraining'])->name('admin.trainingmodule.add');
    Route::get('admin/get-training-module/{id}', [AdminTrainingModuleController::class, 'getTrainingById'])->name('admin.trainingmodule.getTrainingById');

    Route::post('admin/update-training-module', [AdminTrainingModuleController::class, 'updateTrainingModule'])->name('admin.trainingmodule.update');

    Route::post('admin/delete-training-module', [AdminTrainingModuleController::class, 'deleteTraining'])->name('admin.trainingmodule.delete');

    Route::get('admin/training-preview/{trainingid}', [AdminTrainingModuleController::class, 'trainingPreview'])->name('admin.trainingmodule.preview');

    Route::get('admin/training-preview-content/{trainingid}/{lang}', [AdminTrainingModuleController::class, 'loadPreviewTrainingContent'])->name('admin.trainingmodule.preview.content');

    //---------------------------all logs route -----------------//
    Route::get('admin/all-logs', [LogController::class, 'index'])->name('admin.all.logs');


    Route::get('admin/logout', [AdminLoginController::class, 'logoutAdmin'])->name('adminLogout');
});


//------------------------admin route----------------------//



Route::get('/trackEmailView/{campid}', [TrackingController::class, 'trackemail']);
Route::post('/outlook-phish-report', [TrackingController::class, 'outlookPhishReport']);

Route::get('/send-mail', function (MailController $controller) {
    $mailData = [
        'email' => 'vivek821038@gmail.com',
        'from_name' => 'Sender Name',
        'email_subject' => 'Test Subject',
        'mailBody' => '<p>This is a test email</p>',
        'from_email' => 'noreply@simuphish.com',
        'sendMailHost' => 'mailer.simuphish.com',
        'sendMailUserName' => 'noreply@simuphish.com',
        'sendMailPassword' => 'pf=n?y1_Z_1yuq+?',
    ];

    return $controller->sendMail($mailData);
});

require __DIR__ . '/auth.php';
