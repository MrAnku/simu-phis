<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Users;
use App\Models\UsersGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixUserDuplicates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-user-duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix duplicate user records and consolidate them to a single user ID across all groups';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companies = Company::where('approved', 1)
            ->where('role', null)
            ->where('service_status', 1)
            ->get();

        if ($companies->isEmpty()) {
            return;
        }



        // $companies = Users::select('company_id')->distinct()->pluck('company_id');

        foreach ($companies as $company) {
            $this->processDuplicates($company->company_id);
        }

        $this->info("\n✅ Duplicate user fix completed for all companies!");
    }

    private function processDuplicates($companyId)
    {
        // First, find all duplicate emails in the users table
        $duplicateEmails = Users::select('user_email', DB::raw('COUNT(*) as count'))
            ->where('company_id', $companyId)
            ->groupBy('user_email')
            ->having('count', '>', 1)
            ->pluck('user_email');

        if ($duplicateEmails->isEmpty()) {
            return;
        }

        $totalRemoved = 0;

        // Process each duplicate email
        foreach ($duplicateEmails as $email) {
            // Get all users with this email
            $duplicateUsers = Users::where('user_email', $email)
                ->where('company_id', $companyId)
                ->orderBy('id', 'asc')
                ->get();

            // Keep the first user (lowest ID) as canonical
            $canonicalUser = $duplicateUsers->first();
            $canonicalUserId = $canonicalUser->id;

            $this->info("\n Email: {$email}");

            // Get all duplicate IDs (excluding canonical)
            $duplicateIds = $duplicateUsers->pluck('id')
                ->filter(function ($id) use ($canonicalUserId) {
                    return $id !== $canonicalUserId;
                })
                ->toArray();

            if (empty($duplicateIds)) {
                continue;
            }

            // Replace all duplicate IDs with canonical ID in ALL groups
            $this->replaceUserIdsInAllGroups($companyId, $duplicateIds, $canonicalUserId);

            // Replace user IDs in campaign and training tables
            $this->replaceUserIdsInTables($companyId, $email, $canonicalUserId);

            // Delete duplicate records from users table
            $deleted = Users::whereIn('id', $duplicateIds)
                ->where('company_id', $companyId)
                ->delete();

            $totalRemoved += $deleted;

            $this->info("   ✓ Deleted {$deleted} duplicate record(s)");
        }

        // After processing all duplicates, fix selected_users columns
        $this->fixSelectedUsersColumns($companyId);

        $this->info("\n Company {$companyId} Summary:");
        $this->info("   - Total duplicate records removed: {$totalRemoved}");
    }

    private function replaceUserIdsInAllGroups($companyId, $duplicateIds, $canonicalUserId)
    {
        $allGroups = UsersGroup::where('company_id', $companyId)->get();

        foreach ($allGroups as $group) {
            if ($group->users === null) {
                continue;
            }

            $usersArray = json_decode($group->users, true);
            if (!is_array($usersArray) || empty($usersArray)) {
                continue;
            }

            $updated = false;
            $newUsersArray = [];

            foreach ($usersArray as $userId) {
                // If this is a duplicate ID, replace with canonical ID
                if (in_array($userId, $duplicateIds)) {
                    // Only add canonical ID if not already in array (avoid duplicates)
                    if (!in_array($canonicalUserId, $newUsersArray)) {
                        $newUsersArray[] = $canonicalUserId;
                        $updated = true;
                        $this->info("     Group {$group->group_id}: Replaced user ID {$userId} with {$canonicalUserId}");
                    } else {
                        $this->info("     Group {$group->group_id}: Removed duplicate user ID {$userId} (canonical ID already exists)");
                        $updated = true;
                    }
                } else {
                    // Keep existing ID (not a duplicate)
                    if (!in_array($userId, $newUsersArray)) {
                        $newUsersArray[] = $userId;
                    }
                }
            }

            // Update group if changes were made
            if ($updated) {
                $group->users = json_encode(array_values($newUsersArray));
                $group->save();
            }
        }
    }

    private function replaceUserIdsInTables($companyId, $email, $canonicalUserId)
    {
        // List of tables with user_id and user_email columns
        $tables = [
            'ai_call_camp_live',
            'campaign_live',
            'quishing_live_camps',
            'wa_live_campaigns',
            'comic_assigned_users',
            'company_trigger_trainings',
            'info_graphic_live_campaigns',
            'scorm_assigned_users',
            'tprm_campaign_live',
            'training_assigned_users'
        ];

        foreach ($tables as $table) {
            try {
                // Update records where user_email matches
                $updated = DB::table($table)
                    ->where('user_email', $email)
                    ->where('company_id', $companyId)
                    ->update(['user_id' => $canonicalUserId]);

                if ($updated > 0) {
                    $this->info("     Table {$table}: Updated {$updated} record(s) to user ID {$canonicalUserId}");
                }
            } catch (\Exception $e) {
                // Skip tables that don't exist or have different structure
                $this->warn("     Skipped table {$table}: " . $e->getMessage());
            }
        }
    }

    private function fixSelectedUsersColumns($companyId)
    {
        $this->info("\n🔄 Fixing selected_users columns...");

        // Define campaign tables and their live table relationships
        $campaignTables = [
            'ai_call_campaigns' => 'ai_call_camp_live',
            'all_campaigns' => 'campaign_live',
            'quishing_camps' => 'quishing_live_camps',
            'wa_campaigns' => 'wa_live_campaigns'
        ];

        foreach ($campaignTables as $campaignTable => $liveTable) {
            try {
                // Get campaigns with non-null selected_users
                $campaigns = DB::table($campaignTable)
                    ->where('company_id', $companyId)
                    ->whereNotNull('selected_users')
                    ->get();

                foreach ($campaigns as $campaign) {
                    $selectedUsers = json_decode($campaign->selected_users, true);
                    
                    if (!is_array($selectedUsers) || empty($selectedUsers)) {
                        continue;
                    }

                    $updatedSelectedUsers = [];

                    // Get all emails from live table for this campaign_id
                    $liveRecords = DB::table($liveTable)
                        ->where('campaign_id', $campaign->campaign_id)
                        ->where('company_id', $companyId)
                        ->select('user_email')
                        ->distinct()
                        ->get();

                    foreach ($liveRecords as $liveRecord) {
                        if (isset($liveRecord->user_email)) {
                            // Get the correct user ID from users table by email
                            $correctUser = Users::where('user_email', $liveRecord->user_email)
                                ->where('company_id', $companyId)
                                ->first();

                            if ($correctUser && !in_array($correctUser->id, $updatedSelectedUsers)) {
                                $updatedSelectedUsers[] = $correctUser->id;
                            }
                        }
                    }

                    // Only update if we found valid user IDs
                    if (!empty($updatedSelectedUsers)) {
                        DB::table($campaignTable)
                            ->where('id', $campaign->id)
                            ->update(['selected_users' => json_encode(array_values($updatedSelectedUsers))]);
                        
                        $this->info("     {$campaignTable} (Campaign ID {$campaign->id}): Updated selected_users with " . count($updatedSelectedUsers) . " user(s)");
                    }
                }

            } catch (\Exception $e) {
                $this->warn("     Skipped {$campaignTable}: " . $e->getMessage());
            }
        }
    }
}
