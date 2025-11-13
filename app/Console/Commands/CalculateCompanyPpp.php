<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PppCalculationService;
use App\Models\Company;

class CalculateCompanyPpp extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ppp:calculate-company';

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
            $this->warn('No companies found for PPP calculation.');
            return;
        }
        $successCount = 0;
        $failedCount = 0;
        $totalNewCalculations = 0;

        foreach ($companies as $company) {
            try {
                $this->line("Processing company: {$company->company_name} (ID: {$company->company_id})");

                $results = $this->pppService->calculateHistoricalPppForCompany($company->company_id);

                if ($results !== false && !empty($results)) {
                    $newCalculations = 0;
                    $skippedCalculations = 0;

                    // Check each result to see if it was newly calculated or already existed
                    foreach ($results as $result) {
                        if ($result->wasRecentlyCreated) {
                            $newCalculations++;
                            $totalNewCalculations++;
                            $this->info("✓ PPP calculated for {$company->company_name} - {$result->month_year}: {$result->ppp_percentage}%");
                        } else {
                            $skippedCalculations++;
                        }
                    }

                    if ($newCalculations == 0) {
                        $this->line("{$company->company_name} - All PPP data already exists");
                    }

                    $successCount++;
                } else {
                    $this->line("{$company->company_name} - No data to calculate");
                    $failedCount++;
                }
            } catch (\Exception $e) {
                $this->error("{$company->company_name} - Error: " . $e->getMessage());
                $failedCount++;
                continue;
            }
        }
    }
}
