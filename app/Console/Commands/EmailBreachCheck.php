<?php

namespace App\Console\Commands;

use App\Models\Users;
use App\Models\Company;
use App\Models\BreachedEmail;
use Illuminate\Console\Command;
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
        //check if any user not scanned for breach
        $this->scanNewUsers();

        //check if any user scanner for breach more than 30 days ago
        $this->scanOldUsers();
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
}
