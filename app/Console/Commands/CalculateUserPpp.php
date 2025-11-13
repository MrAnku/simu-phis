<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UserPppCalculationService;
use App\Models\Company;

class CalculateUserPpp extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ppp:calculate-users';

    /**
     * The console command description.
     */
    protected $description = 'Calculate monthly PPP for individual users (0% if no simulations in a month)';

    /**
     * The User PPP calculation service.
     */
    protected $userPppService;

    /**
     * Create a new command instance.
     */
    public function __construct(UserPppCalculationService $userPppService)
    {
        parent::__construct();
        $this->userPppService = $userPppService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->calculateUserPppForAllUsers();
        return 0;
    }

    /**
     * Calculate User PPP for ALL users across ALL approved companies in batches (efficient approach)
     */
    private function calculateUserPppForAllUsers()
    {
        try {
            // Process ALL users in batches of 50 to avoid high load
            $batchResults = $this->userPppService->calculatePppForAllUsersInBatches(50);
            
            if ($batchResults['total_users'] > 0) {
                $message = "✓ User PPP: {$batchResults['processed']}/{$batchResults['total_users']} users processed across all companies";
                
                if ($batchResults['new_calculations'] > 0) {
                    $message .= ", {$batchResults['new_calculations']} new calculations";
                }
                
                if ($batchResults['errors'] > 0) {
                    $message .= ", {$batchResults['errors']} errors";
                }
                
                $this->info($message);
            } else {
                $this->line("No users found for PPP calculation across all approved companies");
            }
            
        } catch (\Exception $e) {
            $this->error("✗ Error processing users: " . $e->getMessage());
            $batchResults = ['processed' => 0, 'new_calculations' => 0];
        }
    }
}