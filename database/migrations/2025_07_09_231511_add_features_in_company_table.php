<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $features = [
            "division",
            "all_employees",
            "blue_collar",
            "email_phishing",
            "quishing",
            "whatsapp_camp",
            "vishing",
            "tprm",
            "integration",
            "reporting",
            "policies",
            "send_policy",
            "phishing_emails",
            "quishing_emails",
            "phishing_websites",
            "vishing_templates",
            "sender_profiles",
            "training_modules",
            "human_risk_intelligence",
            "phish_triage",
            "support_ticket"
        ];
        DB::table('company')->update(['enabled_feature' => json_encode($features)]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('company')->update(['enabled_feature' => null]);
    }
};
