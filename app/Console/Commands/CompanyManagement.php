<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Company;
use App\Models\NewIpLogin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\Admin\CompanyManagementMail;
use App\Mail\NewIpLoginAlert;

class CompanyManagement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:company-management';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->checkCompanyLicense();
        $this->needSupport();
        $this->alertNewIpLogins();
    }

    private function checkCompanyLicense()
    {
        $companies = Company::where('approved', 1)
            ->where('role', null)
            ->where('service_status', 1)
            ->get();

        if ($companies->isEmpty()) {
            return;
        }
        foreach ($companies as $company) {

            //check if the company id exists in the alert table
            if (!$company->alert()->exists()) {
                $company->alert()->create([
                    'company_id' => $company->company_id
                ]);
            }
            $license = $company->license;
            if (!$license) {
                echo ("Company {$company->company_name} does not have a valid license. \n");
                continue;
            }
            if (
                $license->expiry < now() &&
                $company->alert?->license_expired == null
            ) {
                echo ("Company {$company->company_name} license has expired. \n");
                try {
                    $ccMailAddress = Company::where('company_id', $company->company_id)->value('account_manager');
                    if (!$ccMailAddress) {
                        $ccMailAddress = 'contact@simuphish.com';
                    }
                    Mail::to($company->email)->send(new CompanyManagementMail($company, 'license_expired', $ccMailAddress));

                    $company->alert?->update(['license_expired' => now()]);
                } catch (\Exception $e) {
                    echo "Failed to send license expiry email to {$company->email}: " . $e->getMessage();
                }
            }


            // Additional logic can be added here to handle company management tasks
        }
    }

    private function needSupport()
    {
        $companies = Company::where('approved', 1)
            ->where('role', null)
            ->where('service_status', 1)
            ->get();

        if ($companies->isEmpty()) {
            return;
        }
        foreach ($companies as $company) {

            if ($company->alert?->need_support == null && $company->license?->expiry > now()) {
                $companyCreatedDate = Carbon::parse($company->created_at);

                //check if the company was created more than 15 days ago
                if ($companyCreatedDate->lessThan(now()->subDays(15))) {
                    echo ("Send Company support email to {$company->company_name}.");
                    try {
                        $ccMailAddress = Company::where('company_id', $company->company_id)->value('account_manager');
                        if (!$ccMailAddress) {
                            $ccMailAddress = 'contact@simuphish.com';
                        }
                        Mail::to($company->email)->send(new CompanyManagementMail($company, 'need_support', $ccMailAddress));
                        $company->alert?->update(['need_support' => now()]);
                    } catch (\Exception $e) {
                        echo "Failed to send support email to {$company->email}: " . $e->getMessage();
                    }
                }
            }
        }
    }

    private function alertNewIpLogins()
    {
        $pendingAlerts = NewIpLogin::where('notified', 0)->get();
        foreach ($pendingAlerts as $alert) {
            try {
                Mail::to($alert->email)->send(new NewIpLoginAlert(
                    $alert->email,
                    $alert->login_time,
                    $alert->ip_address
                ));
                $alert->notified = 1;
                $alert->save();
            } catch (\Exception $e) {
                echo "Failed to send new IP login alert to admin: " . $e->getMessage();
            }
        }
    }
}
