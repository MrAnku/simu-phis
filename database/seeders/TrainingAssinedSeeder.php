<?php

namespace Database\Seeders;

use App\Models\AiCallCampaign;
use App\Models\AiCallCampLive;
use App\Models\Campaign;
use App\Models\CampaignLive;
use App\Models\QuishingCamp;
use App\Models\QuishingLiveCamp;
use App\Models\WaCampaign;
use App\Models\WaLiveCampaign;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TrainingAssinedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $campaign = Campaign::first();
        $campaigns = CampaignLive::where('campaign_id', $campaign->campaign_id)
        ->get();

        foreach ($campaigns as $campaigns) {
            DB::table('training_assigned_users')->insert([
            'campaign_id' => $campaigns->campaign_id,
            'user_id' => $campaigns->user_id,
            'user_name' => $campaigns->user_name,
            'user_email' => $campaigns->user_email,
            'training' => '34',
            'training_lang' => 'en',
            'training_type' => 'static_training',
            'personal_best' => rand(30, 70),
            'completed' => rand(0, 1),
            'assigned_date' => now(),
            'training_due_date' => now()->addDays(rand(1, 30)),
            'completion_date' => now()->addDays(rand(1, 30)),
            'company_id' => 'bc2d3bf2-4eb0-47db-8f1e-6d2a76b94607',
            'certificate_id' => null,
            'last_reminder_date' => null,
            'game_time' => rand(60, 300)
        ]);
        }

        
    }
}
