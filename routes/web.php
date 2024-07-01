<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeesController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TrackingController;
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

require __DIR__.'/auth.php';
