<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeesController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\PhishingEmailsController;
use App\Http\Controllers\PhishingWebsitesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\SenderProfileController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\TrainingModuleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});



Route::middleware('auth')->group(function () {
    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // index page routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/get-pie-data', [DashboardController::class, 'getPieData'])->name('get.pie.data');
    Route::get('/get-line-chart-data', [DashboardController::class, 'getLineChartData'])->name('get.line.chart.data');
    Route::get('/get-total-assets', [DashboardController::class, 'getTotalAssets'])->name('get.total.assets');

    //campaigns page routes
    Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns');
    Route::post('/campaigns/create', [CampaignController::class, 'createCampaign'])->name('campaigns.create');

    //employees route
    Route::get('/employees', [EmployeesController::class, 'index'])->name('employees');
    Route::post('/employees/send-domain-verify-otp', [EmployeesController::class, 'sendDomainVerifyOtp'])->name('sendDomainVerificationOtp');

    Route::post('/employees/otp-verify', [EmployeesController::class, 'verifyOtp'])->name('domain.otpverify');
    Route::post('/employees/delete-domain', [EmployeesController::class, 'deleteDomain'])->name('domain.delete');


    Route::post('/employees/newGroup', [EmployeesController::class, 'newGroup'])->name('employee.newgroup');
    Route::get('/employees/viewUsers/{groupid}', [EmployeesController::class, 'viewUsers'])->name('employee.viewUsers');
    Route::post('/employees/deleteUser', [EmployeesController::class, 'deleteUser'])->name('employee.deleteUser');
    Route::post('/employees/addUser', [EmployeesController::class, 'addUser'])->name('employee.addUser');
    Route::post('/employees/deleteGroup', [EmployeesController::class, 'deleteGroup'])->name('employee.deleteGroup');

    //reporting routes

    Route::get('/reporting', [ReportingController::class, 'index'])->name('campaign.reporting');
    Route::post('/reporting/fetch-campaign-report', [ReportingController::class, 'fetchCampaignReport'])->name('campaign.fetchCampaignReport');

    Route::post('/fetch-camp-report-by-users', [ReportingController::class, 'fetchCampReportByUsers'])->name('campaign.fetchCampReportByUsers');

    Route::post('/fetch-camp-training-details', [ReportingController::class, 'fetchCampTrainingDetails'])->name('campaign.fetchCampTrainingDetails');
    Route::post('/fetch-camp-training-details-individual', [ReportingController::class, 'fetchCampTrainingDetailsIndividual'])->name('campaign.fetchCampTrainingDetailsIndividual');

    //phishing emails route

    Route::get('/phishing-emails', [PhishingEmailsController::class, 'index'])->name('phishing.emails');
    Route::post('/phishing-email', [PhishingEmailsController::class, 'getTemplateById'])->name('phishing.getTemplateById');
    Route::post('/add-email-template', [PhishingEmailsController::class, 'addEmailTemplate'])->name('addEmailTemplate');
    Route::post('/update-email-template', [PhishingEmailsController::class, 'updateTemplate'])->name('phishing.update');
    Route::post('/delete-email-template', [PhishingEmailsController::class, 'deleteTemplate'])->name('phishing.template.delete');


    //phishing websites routes
    
    Route::get('/phishing-websites', [PhishingWebsitesController::class, 'index'])->name('phishing.websites');
    Route::post('/delete-website', [PhishingWebsitesController::class, 'deleteWebsite'])->name('phishing.website.delete');
    Route::post('/add-phishing-website', [PhishingWebsitesController::class, 'addPhishingWebsite'])->name('phishing.website.add');


    //sender profiles routes
    Route::get('/sender-profiles', [SenderProfileController::class, 'index'])->name('senderprofile.index');
    Route::post('/delete-sender-profile', [SenderProfileController::class, 'deleteSenderProfile'])->name('senderprofile.delete');
    Route::post('/add-sender-profile', [SenderProfileController::class, 'addSenderProfile'])->name('senderprofile.add');
    Route::get('/get-sender-profile/{id}', [SenderProfileController::class, 'getSenderProfile'])->name('senderprofile.get');

    Route::post('/update-sender-profile', [SenderProfileController::class, 'updateSenderProfile'])->name('senderprofile.update');

    //training module routes
    Route::get('/training-modules', [TrainingModuleController::class, 'index'])->name('trainingmodule.index');
    Route::post('/add-training-module', [TrainingModuleController::class, 'addTraining'])->name('trainingmodule.add');
    Route::get('/get-training-module/{id}', [TrainingModuleController::class, 'getTrainingById'])->name('trainingmodule.getTrainingById');

    Route::post('/update-training-module', [TrainingModuleController::class, 'updateTrainingModule'])->name('trainingmodule.update');
    Route::post('/delete-training-module', [TrainingModuleController::class, 'deleteTraining'])->name('trainingmodule.delete');

    Route::get('/training-preview/{trainingid}', [TrainingModuleController::class, 'trainingPreview'])->name('trainingmodule.preview');
    
    Route::get('/training-preview-content/{trainingid}', [TrainingModuleController::class, 'loadPreviewTrainingContent'])->name('trainingmodule.preview.content');





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
