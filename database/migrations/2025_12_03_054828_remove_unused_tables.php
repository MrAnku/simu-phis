<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('ai_agent_requests');
        Schema::dropIfExists('ai_call_agents');
        Schema::dropIfExists('ai_call_all_logs');
        Schema::dropIfExists('bluecollar_training_initiators');
        Schema::dropIfExists('campaign_reports');
        Schema::dropIfExists('domain_emails');
        Schema::dropIfExists('new_learner_passwords');
        Schema::dropIfExists('partner_whatsapp_api');
        Schema::dropIfExists('scrum_packages');
        Schema::dropIfExists('tprm_websites_sessions');
        Schema::dropIfExists('upgrade_req');
        Schema::dropIfExists('user_login');
        Schema::dropIfExists('whatsapp_campaigns');
        Schema::dropIfExists('whatsapp_camp_users');
        Schema::dropIfExists('whatsapp_num_change_req');
        Schema::dropIfExists('whatsapp_temp_requests');
        Schema::dropIfExists('white_labelled_partner');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
