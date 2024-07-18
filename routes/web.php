<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MailController;
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
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\WhiteLabelController;
use App\Http\Controllers\PhishingWebsitesController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPhishingEmailController;
use App\Http\Controllers\Admin\AdminSenderProfileController;
use App\Http\Controllers\Admin\AdminTrainingModuleController;
use App\Http\Controllers\Admin\AdminPhishingWebsiteController;

Route::get('/', function () {
    return redirect()->route('login');
});

// ---------------------company route---------------------//



Route::middleware('auth')->group(function () {
    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // index page routes-----------------------------------------------------

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/get-pie-data', [DashboardController::class, 'getPieData'])->name('get.pie.data');
    Route::get('/get-line-chart-data', [DashboardController::class, 'getLineChartData'])->name('get.line.chart.data');
    Route::get('/get-total-assets', [DashboardController::class, 'getTotalAssets'])->name('get.total.assets');

    //campaigns page routes------------------------------------------------------

    Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns');
    Route::post('/campaigns/create', [CampaignController::class, 'createCampaign'])->name('campaigns.create');

    //employees route-------------------------------------------------------------

    Route::get('/employees', [EmployeesController::class, 'index'])->name('employees');
    Route::post('/employees/send-domain-verify-otp', [EmployeesController::class, 'sendDomainVerifyOtp'])->name('sendDomainVerificationOtp');

    Route::post('/employees/otp-verify', [EmployeesController::class, 'verifyOtp'])->name('domain.otpverify');
    Route::post('/employees/delete-domain', [EmployeesController::class, 'deleteDomain'])->name('domain.delete');


    Route::post('/employees/newGroup', [EmployeesController::class, 'newGroup'])->name('employee.newgroup');
    Route::get('/employees/viewUsers/{groupid}', [EmployeesController::class, 'viewUsers'])->name('employee.viewUsers');
    Route::post('/employees/deleteUser', [EmployeesController::class, 'deleteUser'])->name('employee.deleteUser');
    Route::post('/employees/addUser', [EmployeesController::class, 'addUser'])->name('employee.addUser');
    Route::post('/employees/deleteGroup', [EmployeesController::class, 'deleteGroup'])->name('employee.deleteGroup');

    //reporting routes-----------------------------------------------------------------

    Route::get('/reporting', [ReportingController::class, 'index'])->name('campaign.reporting');
    Route::post('/reporting/fetch-campaign-report', [ReportingController::class, 'fetchCampaignReport'])->name('campaign.fetchCampaignReport');

    Route::post('/fetch-camp-report-by-users', [ReportingController::class, 'fetchCampReportByUsers'])->name('campaign.fetchCampReportByUsers');

    Route::post('/fetch-camp-training-details', [ReportingController::class, 'fetchCampTrainingDetails'])->name('campaign.fetchCampTrainingDetails');
    Route::post('/fetch-camp-training-details-individual', [ReportingController::class, 'fetchCampTrainingDetailsIndividual'])->name('campaign.fetchCampTrainingDetailsIndividual');

    //phishing emails route---------------------------------------------------------------

    Route::get('/phishing-emails', [PhishingEmailsController::class, 'index'])->name('phishing.emails');
    Route::post('/phishing-email', [PhishingEmailsController::class, 'getTemplateById'])->name('phishing.getTemplateById');
    Route::post('/add-email-template', [PhishingEmailsController::class, 'addEmailTemplate'])->name('addEmailTemplate');
    Route::post('/update-email-template', [PhishingEmailsController::class, 'updateTemplate'])->name('phishing.update');
    Route::post('/delete-email-template', [PhishingEmailsController::class, 'deleteTemplate'])->name('phishing.template.delete');


    //phishing websites routes-------------------------------------------------------------

    Route::get('/phishing-websites', [PhishingWebsitesController::class, 'index'])->name('phishing.websites');
    Route::post('/delete-website', [PhishingWebsitesController::class, 'deleteWebsite'])->name('phishing.website.delete');
    Route::post('/add-phishing-website', [PhishingWebsitesController::class, 'addPhishingWebsite'])->name('phishing.website.add');


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

    Route::get('/training-preview-content/{trainingid}', [TrainingModuleController::class, 'loadPreviewTrainingContent'])->name('trainingmodule.preview.content');

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

    Route::get('admin/training-preview-content/{trainingid}', [AdminTrainingModuleController::class, 'loadPreviewTrainingContent'])->name('admin.trainingmodule.preview.content');

    //---------------------------training module route -----------------//


    Route::get('admin/logout', [AdminLoginController::class, 'logoutAdmin'])->name('adminLogout');
});


//------------------------admin route----------------------//

Route::get('/upload', [TestUploadController::class, 'showUploadForm'])->name('upload.form');
Route::post('/upload', [TestUploadController::class, 'uploadFile'])->name('upload.file');


//-------------------miscellaneous routes------------------//

Route::domain('cloud-services-notifications.com')->group(function () {
    Route::get('/{websitefile}&token={anytoken}&usrid={anyuser}', [ShowWebsiteController::class, 'index']);
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

Route::get('/trackEmailView/{campid}', [TrackingController::class, 'trackemail']);

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
