<?php

namespace App\Console\Commands;

use App\Mail\Admin\CompanyManagementMail;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

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
    }

    private function checkCompanyLicense()
    {
        $companies = Company::where('approved', 1)
        ->where('role', null)
        ->where('service_status', 1)
        ->get();
        
        if($companies->isEmpty()) {
            return;
        }
        foreach ($companies as $company) {
            $license = $company->license;
            if (!$license) {
                echo ("Company {$company->company_name} does not have a valid license.");
                continue;
            }
            if($license->expiry < now()) {
                echo ("Company {$company->company_name} license has expired.");
                try {
                    Mail::to($company->email)->send(new CompanyManagementMail($company, 'license_expired'));
                } catch (\Exception $e) {
                    echo "Failed to send license expiry email to {$company->email}: " . $e->getMessage();
                }
                
            }


            // Additional logic can be added here to handle company management tasks
        }

    }
}
