<?php

namespace App\Services;

use App\Models\UserMonthlyPpp;
use App\Models\Users;
use App\Models\CampaignLive;
use App\Models\QuishingLiveCamp;
use App\Models\WaLiveCampaign;
use App\Models\AiCallCampLive;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UserPppCalculationService
{
    /**
     * Calculate PPP for a specific user, month and company
     */
    public function calculateUserMonthlyPpp($companyId, $userEmail, $month, $year)
    {
        // Calculate PPP using same formula: (employees who clicked / total employees who received) * 100
        $totalSimulations = $this->totalSimulations($companyId, $userEmail, $month, $year);
        $totalClicked = $this->payloadClicked($companyId, $userEmail, $month, $year);

        // PPP Formula: (clicked / total received) * 100
        $pppPercentage = $totalSimulations > 0 ?
            round(($totalClicked / $totalSimulations) * 100, 2) : 0;

        // Ensure PPP percentage never exceeds 100%
        $pppPercentage = min($pppPercentage, 100.00);

        // Format month_year as "November 2025"
        $monthYear = Carbon::create($year, $month, 1)->format('F Y');

        // Check if record already exists - if so, return existing record without updating
        $existingPpp = UserMonthlyPpp::where('company_id', $companyId)
            ->where('user_email', $userEmail)
            ->where('month_year', $monthYear)
            ->first();

        if ($existingPpp) {
            // Return existing record without any updates to preserve historical data
            return $existingPpp;
        }

        // Only create new record if it doesn't exist
        return UserMonthlyPpp::create([
            'company_id' => $companyId,
            'user_email' => $userEmail,
            'month_year' => $monthYear,
            'ppp_percentage' => $pppPercentage
        ]);
    }

    /**
     * Calculate historical PPP data for a specific user from their first record date to current month
     */
    public function calculateHistoricalPppForUser($companyId, $userEmail)
    {
        // Get user's first record date from Users table to determine start date
        $userFirstRecord = $this->getUserFirstRecordDate($companyId, $userEmail);

        $startDate = null;
        if (!$userFirstRecord) {
            // No user record found - calculate from current month only with 0% PPP
            $startDate = Carbon::now()->startOfMonth();
        } else {
            $startDate = Carbon::parse($userFirstRecord)->startOfMonth();
        }

        $currentDate = Carbon::now()->startOfMonth();

        $results = [];

        while ($startDate <= $currentDate) {
            $month = $startDate->month;
            $year = $startDate->year;
            $monthYear = Carbon::create($year, $month, 1)->format('F Y');

            // Check if PPP already exists for this user/month to preserve historical data
            $existingPpp = UserMonthlyPpp::where('company_id', $companyId)
                ->where('user_email', $userEmail)
                ->where('month_year', $monthYear)
                ->first();

            if ($existingPpp) {
                // PPP already exists, skip calculation to preserve historical data
                $results[] = $existingPpp;
            } else {
                // Calculate PPP for new month only (will be 0% if no simulations)
                $result = $this->calculateUserMonthlyPpp($companyId, $userEmail, $month, $year);
                $results[] = $result;
            }

            $startDate->addMonth();
        }

        return $results;
    }

    /**
     * Process ALL users from ALL approved companies in batches (more efficient than per-company processing)
     */
    public function calculatePppForAllUsersInBatches($batchSize = 50)
    {
        $totalProcessed = 0;
        $totalErrors = 0;
        $newCalculations = 0;

        // Get ALL unique users from ALL approved companies in one query
        $allUniqueUsers = $this->getAllUniqueUsersFromApprovedCompanies();

        if (empty($allUniqueUsers)) {
            Log::info("No users found across all approved companies");
            return [
                'total_users' => 0,
                'processed' => 0,
                'errors' => 0,
                'new_calculations' => 0
            ];
        }

        // Process ALL users in batches regardless of company
        $userBatches = array_chunk($allUniqueUsers, $batchSize);

        foreach ($userBatches as $batchIndex => $userBatch) {

            foreach ($userBatch as $userData) {
                try {
                    $results = $this->calculateHistoricalPppForUser(
                        $userData['company_id'],
                        $userData['user_email']
                    );

                    // Results should always be an array now (never false)
                    if (is_array($results) && !empty($results)) {
                        foreach ($results as $result) {
                            if ($result->wasRecentlyCreated) {
                                $newCalculations++;
                            }
                        }
                        $totalProcessed++;
                    }
                } catch (\Exception $e) {
                    Log::error("Error calculating PPP for user {$userData['user_email']} (company: {$userData['company_id']}): " . $e->getMessage());
                    $totalErrors++;
                }
            }

            // Add small delay between batches to reduce load
            if ($batchIndex < count($userBatches) - 1) {
                usleep(100000); // 0.1 second delay
            }
        }

        return [
            'total_users' => count($allUniqueUsers),
            'processed' => $totalProcessed,
            'errors' => $totalErrors,
            'new_calculations' => $newCalculations
        ];
    }

    /**
     * Get ALL unique users from ALL approved companies
     */
    public function getAllUniqueUsersFromApprovedCompanies()
    {
        // Get ALL unique users from Users table across all approved companies
        return Users::whereIn('company_id', function ($query) {
            $query->select('company_id')
                ->from('company')  // Fixed table name
                ->where('approved', 1)
                ->where('role', null)
                ->where('service_status', 1);
        })
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('users')
                    ->groupBy('user_email', 'company_id');
            })
            ->get(['user_email', 'company_id'])
            ->toArray();
    }

    /**
     * Get user's first record date from Users table (when user was created)
     */
    private function getUserFirstRecordDate($companyId, $userEmail)
    {
        // Get the earliest created_at date from Users table for this email
        return Users::where('company_id', $companyId)
            ->where('user_email', $userEmail)
            ->min('created_at');
    }

    /**
     * Get total simulations for a user in a specific month
     */
    private function totalSimulations($companyId, $userEmail, $month, $year)
    {
        $email = CampaignLive::where('company_id', $companyId)
            ->where('user_email', $userEmail)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        $quishing = QuishingLiveCamp::where('company_id', $companyId)
            ->where('user_email', $userEmail)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        $whatsapp = WaLiveCampaign::where('company_id', $companyId)
            ->where('user_email', $userEmail)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        $ai = AiCallCampLive::where('company_id', $companyId)
            ->where('user_email', $userEmail)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        return $email + $quishing + $whatsapp + $ai;
    }

    /**
     * Get total clicked for a user in a specific month
     */
    private function payloadClicked($companyId, $userEmail, $month, $year)
    {
        $emailClicked = CampaignLive::where('company_id', $companyId)
            ->where('user_email', $userEmail)
            ->where('payload_clicked', 1)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        $quishingClicked = QuishingLiveCamp::where('company_id', $companyId)
            ->where('user_email', $userEmail)
            ->where('qr_scanned', '1')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        $whatsappClicked = WaLiveCampaign::where('company_id', $companyId)
            ->where('user_email', $userEmail)
            ->where('payload_clicked', 1)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        $ai = AiCallCampLive::where('user_email', $userEmail)
            ->where('company_id', $companyId)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->where('compromised', 1)
            ->count();

        return $emailClicked + $quishingClicked + $whatsappClicked + $ai;
    }
}
