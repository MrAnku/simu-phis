<?php

namespace App\Services;

use App\Models\MonthlyPpp;
use App\Models\Company;
use App\Models\CampaignLive;
use App\Models\QuishingLiveCamp;
use App\Models\WaLiveCampaign;
use App\Models\AiCallCampLive;
use Carbon\Carbon;

class PppCalculationService
{
    /**
     * Calculate PPP for a specific month and company
     */
    public function calculateMonthlyPpp($companyId, $month, $year)
    {
        // Calculate PPP using your formula: (employees who clicked / total employees who received) * 100
        $totalSimulations = $this->getTotalSimulations($companyId, $month, $year);
        $totalClicked = $this->getTotalClicked($companyId, $month, $year);

        // Your PPP Formula: (clicked / total received) * 100
        $pppPercentage = $totalSimulations > 0 ? 
            round(($totalClicked / $totalSimulations) * 100, 2) : 0;

        // Ensure PPP percentage never exceeds 100%
        $pppPercentage = min($pppPercentage, 100.00);

        // Format month_year as "May 2025"
        $monthYear = Carbon::create($year, $month, 1)->format('F Y');

        // Check if record already exists - if so, return existing record without updating
        $existingPpp = MonthlyPpp::where('company_id', $companyId)
            ->where('month_year', $monthYear)
            ->first();
            
        if ($existingPpp) {
            // Return existing record without any updates to preserve historical data
            return $existingPpp;
        }

        // Only create new record if it doesn't exist
        return MonthlyPpp::create([
            'company_id' => $companyId,
            'month_year' => $monthYear,
            'ppp_percentage' => $pppPercentage
        ]);
    }

    /**
     * Calculate historical PPP data from company creation to current month for a specific company
     */
    public function calculateHistoricalPppForCompany($companyId)
    {
        $company = Company::where('company_id', $companyId)->first();
        if (!$company) {
            return false;
        }

        $startDate = Carbon::parse($company->created_at)->startOfMonth();
        $currentDate = Carbon::now()->startOfMonth();
        
        $results = [];
        
        while ($startDate <= $currentDate) {
            $month = $startDate->month;
            $year = $startDate->year;
            $monthYear = Carbon::create($year, $month, 1)->format('F Y');
            
            // Check if PPP already exists for this month to preserve historical data
            $existingPpp = MonthlyPpp::where('company_id', $companyId)
                ->where('month_year', $monthYear)
                ->first();
            
            if ($existingPpp) {
                // PPP already exists for this month, skip calculation to preserve historical data
                $results[] = $existingPpp;
            } else {
                // Calculate PPP for new month only
                $result = $this->calculateMonthlyPpp($companyId, $month, $year);
                $results[] = $result;
            }
            
            $startDate->addMonth();
        }

        return $results;
    }

    /**
     * Get total simulations for a month (using your existing logic)
     */
    private function getTotalSimulations($companyId, $month, $year)
    {
        $email = CampaignLive::where('company_id', $companyId)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        $quishing = QuishingLiveCamp::where('company_id', $companyId)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        $whatsapp = WaLiveCampaign::where('company_id', $companyId)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        $ai = AiCallCampLive::where('company_id', $companyId)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        return $email + $quishing + $whatsapp + $ai;
    }

    /**
     * Get total clicked for a month (using your existing logic)
     */
    private function getTotalClicked($companyId, $month, $year)
    {
        $emailClicked = CampaignLive::where('company_id', $companyId)
            ->where('payload_clicked', 1)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        $quishingClicked = QuishingLiveCamp::where('company_id', $companyId)
            ->where('qr_scanned', '1')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        $whatsappClicked = WaLiveCampaign::where('company_id', $companyId)
            ->where('payload_clicked', 1)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        return $emailClicked + $quishingClicked + $whatsappClicked;
    }
}