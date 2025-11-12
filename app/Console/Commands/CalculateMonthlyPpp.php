<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PppCalculationService;
use App\Models\Company;

class CalculateMonthlyPpp extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ppp:calculate-monthly';

    /**
     * The console command description.
     */
    protected $description = 'Calculate monthly PPP for companies from their creation date to current month';

    /**
     * The PPP calculation service.
     */
    protected $pppService;

    /**
     * Create a new command instance.
     */
    public function __construct(PppCalculationService $pppService)
    {
        parent::__construct();
        $this->pppService = $pppService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Run for all companies (cron behavior)
        $this->calculatePppForAllCompanies();

        return 0;
    }

    /**
     * Calculate PPP for all approved companies
     */
    private function calculatePppForAllCompanies()
    {        
        $companies = Company::where('approved', 1)
            ->where('role', null)
            ->where('service_status', 1)
            ->get();

        if ($companies->isEmpty()) {
            echo 'No companies found for PPP calculation.';
            return;
        }        
        $totalCompanies = count($companies);
        $successCount = 0;
        $failedCount = 0;
        $totalNewCalculations = 0;

        foreach ($companies as $company) {
            try {
                echo "Processing company: {$company->company_name} (ID: {$company->company_id})\n";                
                $results = $this->pppService->calculateHistoricalPppForCompany($company->company_id);
                
                if ($results !== false && !empty($results)) {
                    $newCalculations = 0;
                    $skippedCalculations = 0;
                    
                    // Check each result to see if it was newly calculated or already existed
                    foreach ($results as $result) {
                        if ($result->wasRecentlyCreated) {
                            $newCalculations++;
                            $totalNewCalculations++;
                            echo "PPP calculated for {$company->company_name} - {$result->month_year}: {$result->ppp_percentage}%\n";
                        } else {
                            $skippedCalculations++;
                        }
                    }
                    
                    if ($newCalculations > 0) {
                        echo "Success: {$company->company_name} - {$newCalculations} new months calculated, {$skippedCalculations} months already existed\n";
                    } else {
                        echo "{$company->company_name} - All PPP data already exists\n";
                    }
                    $successCount++;
                } else {
                    echo "{$company->company_name} - No data to calculate\n";
                    $failedCount++;
                }
            } catch (\Exception $e) {
                echo "{$company->company_name} - Error: " . $e->getMessage() . "\n";
                $failedCount++;
                continue;
            }
        }
    }
}