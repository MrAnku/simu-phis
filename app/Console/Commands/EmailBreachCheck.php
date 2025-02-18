<?php

namespace App\Console\Commands;

use App\Models\Users;
use App\Models\Company;
use App\Models\BreachedEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

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
        $employees = Users::where('breach_scan_date', null)->take(7)->get();
        if($employees->isEmpty()){ 
            return;
        }
        foreach ($employees as $employee) {

            
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
                $employee->breach_scan_date = now();
                BreachedEmail::create([
                    'email' => $employee->user_email,
                    'data' => json_encode($breachData),
                    'company_id' => $employee->company_id
                ]);
                $employee->save();
                echo "Breach found for " . $employee->user_email . "\n";
            }else{
                $employee->breach_scan_date = now();
                $employee->save();
                echo "No breach found for " . $employee->user_email . "\n";
            }

        }
    }

    private function scanOldUsers(){
        //scan old employees
        $employees = Users::where('breach_scan_date', '<', now()->subDays(10))->take(7)->get();
        if($employees->isEmpty()){ 
            return;
        }
        foreach ($employees as $employee) {

           
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
                $employee->breach_scan_date = now();
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
                $employee->save();
                
            }else{
                $employee->breach_scan_date = now();
                $employee->save();
                echo "No breach found for " . $employee->user_email . "\n";
            }

        }
    }
}
