<?php

namespace App\Console\Commands;

use App\Models\Users;
use App\Models\Company;
use App\Models\SmartGroup;
use App\Models\UsersGroup;
use Illuminate\Support\Str;
use App\Models\CampaignLive;
use App\Models\BreachedEmail;
use App\Models\AiCallCampLive;
use App\Models\WaLiveCampaign;
use Illuminate\Console\Command;
use App\Models\QuishingLiveCamp;
use App\Services\EmployeeService;
use App\Mail\BreachAlertAdminMail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\BreachAlertEmployeeMail;
use App\Services\CheckWhitelabelService;

class EmailBreachCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:email-breach-check';

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
        // //check if any user not scanned for breach
        $this->scanNewUsers();

        // //check if any user scanner for breach more than 30 days ago
        $this->scanOldUsers();

        //analyse users and their risk and add them to smart groups
        $this->handleSmartGroups();
    }

    private function scanNewUsers()
    {
        //scan new employees
        $employees = Users::whereNull('breach_scan_date')
            ->get()
            ->unique('user_email')
            ->values()
            ->take(7);
        if ($employees->isEmpty()) {
            return;
        }

        foreach ($employees as $employee) {

            echo "email: " . $employee->user_email . "\n";
            continue;

            setCompanyTimezone($employee->company_id);

            //scan employee
            $response = Http::withHeaders([
                'hibp-api-key' => env('HIBP_API_KEY')
            ])->withoutVerifying()->get('https://haveibeenpwned.com/api/v3/breachedaccount/' . $employee->user_email, [
                'truncateResponse' => 'false'
            ]);
            if ($response->successful()) {
                // Process the response
                $breachData = $response->json();
                // Update the employee's breach scan date and other relevant information
                // $employee->breach_scan_date = now();
                Users::where('company_id', $employee->company_id)
                    ->where('user_email', $employee->user_email)
                    ->update(['breach_scan_date' => now()]);

                BreachedEmail::create([
                    'email' => $employee->user_email,
                    'data' => json_encode($breachData),
                    'company_id' => $employee->company_id
                ]);
                // $employee->save();
                echo "Breach found for " . $employee->user_email . "\n";

                $company = Company::where('company_id', $employee->company_id)->first();


                // send Email Notification
                try {
                    $isWhitelabeled = new CheckWhitelabelService($company->company_id);
                    if ($isWhitelabeled->isCompanyWhitelabeled()) {
                        $isWhitelabeled->updateSmtpConfig();
                    }
                    Mail::to($employee->user_email)->send(new BreachAlertEmployeeMail($employee, $breachData));
                    echo "Breach Alert sent successfully to " . $employee->user_email . "\n";

                    Mail::to($company->email)->send(new BreachAlertAdminMail($company, $employee,  $breachData));
                    echo "Breach Alert sent successfully to " . $company->email . "\n";
                } catch (\Exception $e) {
                    echo "Failed to send email. Error: " . $e->getMessage() . "\n";
                }
            } else {
                // $employee->breach_scan_date = now();
                // $employee->save();
                Users::where('company_id', $employee->company_id)
                    ->where('user_email', $employee->user_email)
                    ->update(['breach_scan_date' => now()]);
                echo "No breach found for " . $employee->user_email . "\n";
            }
        }
    }

    private function scanOldUsers()
    {
        //scan old employees
        $employees = Users::where('breach_scan_date', '<', now()->subDays(7))
            ->get()
            ->unique('user_email')
            ->values()
            ->take(7);
        if ($employees->isEmpty()) {
            return;
        }
        foreach ($employees as $employee) {

            echo "email: " . $employee->user_email . "\n";
            continue;

            setCompanyTimezone($employee->company_id);

            //scan employee
            $response = Http::withHeaders([
                'hibp-api-key' => env('HIBP_API_KEY')
            ])->get('https://haveibeenpwned.com/api/v3/breachedaccount/' . $employee->user_email, [
                'truncateResponse' => 'false'
            ]);

            if ($response->successful()) {
                // Process the response
                $breachData = $response->json();
                // Update the employee's breach scan date and other relevant information
                Users::where('company_id', $employee->company_id)
                    ->where('user_email', $employee->user_email)
                    ->update(['breach_scan_date' => now()]);

                $breachedEmail = BreachedEmail::where('email', $employee->user_email)->first();
                if ($breachedEmail) {
                    $breachedEmail->update([
                        'data' => json_encode($breachData),
                        'company_id' => $employee->company_id
                    ]);
                    echo "Breach data updated for " . $employee->user_email . "\n";
                } else {
                    BreachedEmail::create([
                        'email' => $employee->user_email,
                        'data' => json_encode($breachData),
                        'company_id' => $employee->company_id
                    ]);
                    echo "Breach found for " . $employee->user_email . "\n";
                }
            } else {
                Users::where('company_id', $employee->company_id)
                    ->where('user_email', $employee->user_email)
                    ->update(['breach_scan_date' => now()]);
                echo "No breach found for " . $employee->user_email . "\n";
            }
        }
    }

    private function handleSmartGroups()
    {
        try {

            $companies = Company::where('approved', 1)
                ->where('role', null)
                ->where('service_status', 1)
                ->get();

            if ($companies->isEmpty()) {
                return;
            }
            foreach ($companies as $company) {

                //check if the company is setuped smart grouping
                $smartGrouping = SmartGroup::where('company_id', $company->company_id)->get();

                if ($smartGrouping->isEmpty()) {
                    continue;
                }

                //analyse risk
                foreach ($smartGrouping as $group) {
                    $this->analyseRisk($company, $group->risk_type, $group->group_name);
                }


                // add all found users into the smart group 
            }
        } catch (\Exception $e) {
            echo "Error in handling smart groups: " . $e->getMessage() . "\n";
            return;
        }
    }

    private function analyseRisk($company, $riskType, $groupName)
    {
        //get all the users of the company
        $employees = Users::where('company_id', $company->company_id)
            ->get()
            ->unique('user_email')
            ->values();
        if ($employees->isEmpty()) {
            return;
        }

        // if risk type is low
        if ($riskType === 'low') {

            foreach ($employees as $em) {
                $payloadClicks = $this->payloadClickCounts($em, $company->company_id);
                if ($payloadClicks > 2 && $payloadClicks < 5) {
                    $this->addToSmartGroup($em, $groupName);
                }
            }
        }

        // if risk type is medium
        if ($riskType === 'medium') {

            foreach ($employees as $em) {
                $payloadClicks = $this->payloadClickCounts($em, $company->company_id);
                $compromised = $this->compromisedCount($em, $company->company_id);
                if ($payloadClicks >= 5 && $payloadClicks < 10 && $compromised >= 5 && $compromised < 10) {
                    $this->addToSmartGroup($em, $groupName);
                }
            }
        }

        //if risk type is high
        if ($riskType === 'high') {

            foreach ($employees as $em) {
                $payloadClicks = $this->payloadClickCounts($em, $company->company_id);
                $compromised = $this->compromisedCount($em, $company->company_id);
                if ($payloadClicks >= 10 && $compromised >= 10) {
                    $this->addToSmartGroup($em, $groupName);
                }
            }
        }
    }

    private function payloadClickCounts($employee, $companyId)
    {

        $counts = CampaignLive::where('company_id', $companyId)
            ->where('payload_clicked', 1)
            ->where('user_email', $employee->user_email)
            ->count() +
            QuishingLiveCamp::where('qr_scanned', '1')
            ->where('company_id', $companyId)
            ->where('user_email', $employee->user_email)
            ->count() +
            WaLiveCampaign::where('company_id', $companyId)
            ->where('payload_clicked', 1)
            ->where('user_email', $employee->user_email)
            ->count();

        return $counts;
    }

    private function compromisedCount($employee, $companyId)
    {
        $counts = CampaignLive::where('company_id', $companyId)
            ->where('emp_compromised', 1)
            ->where('user_email', $employee->user_email)
            ->count() +
            QuishingLiveCamp::where('compromised', '1')
            ->where('company_id', $companyId)
            ->where('user_email', $employee->user_email)
            ->count() +
            WaLiveCampaign::where('company_id', $companyId)
            ->where('compromised', 1)
            ->where('user_email', $employee->user_email)
            ->count() +
            AiCallCampLive::where('company_id', $companyId)
            ->where('compromised', 1)
            ->where('user_email', $employee->user_email)
            ->count();

        return $counts;
    }

    private function addToSmartGroup($employee, $groupName)
    {

        //check if this group with this name already exists

        $userGroup = UsersGroup::where('company_id', $employee->company_id)
            ->where('group_name', $groupName)
            ->first();
        if (!$userGroup) {
            $userGroup = UsersGroup::create([
                'group_id' => Str::random(6),
                'group_name' => $groupName,
                'users' => null,
                'company_id' => $employee->company_id
            ]);
        }

        $emService = new EmployeeService($employee->company_id);
        // Check if the user is already in the group
        $emailExists = $emService->emailExistsInGroup(
            $userGroup->group_id,
            $employee->user_email
        );
        if (!$emailExists) {
            $user = $emService->addEmployee(
                $employee->user_name,
                $employee->user_email,
                $employee->user_company,
                $employee->user_job_title,
                $employee->whatsapp,
                true
            );
            $emService->addEmployeeInGroup($userGroup->group_id, $user['user_id']);
        }
    }
}
