<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Training assigned users (by email)
        $existingEmails = DB::table('users')->pluck('user_email')->toArray();
        DB::table('training_assigned_users')
            ->whereNotIn('user_email', $existingEmails)
            ->delete();

        // 2. SCORM assigned users (by email)
        DB::table('scorm_assigned_users')
            ->whereNotIn('user_email', $existingEmails)
            ->delete();

        // 3. Blue collar assigned users (by whatsapp)
        $existingWhatsapp = DB::table('blue_collar_employees')->pluck('whatsapp')->toArray();
        DB::table('blue_collar_training_users')
            ->whereNotIn('user_whatsapp', $existingWhatsapp)
            ->delete();

        // 4. Blue collar SCORM assigned users (by whatsapp)
        DB::table('blue_collar_scorm_assigned_users')
            ->whereNotIn('user_whatsapp', $existingWhatsapp)
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
