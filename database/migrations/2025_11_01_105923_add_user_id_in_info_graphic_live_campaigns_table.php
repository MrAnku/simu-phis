<?php

use App\Models\Users;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\InfoGraphicLiveCampaign;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Console\View\Components\Info;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('info_graphic_live_campaigns', function (Blueprint $table) {
            $table->integer('user_id')->nullable()->after('campaign_id');
        });
        $nullUserIds = InfoGraphicLiveCampaign::whereNull('user_id')->get();
        foreach ($nullUserIds as $record) {
            $userId = Users::where('user_email', $record->user_email)->value('id');
            if ($userId) {
                $record->user_id = $userId;
                $record->save();
            }
        }
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('info_graphic_live_campaigns', function (Blueprint $table) {
            //
        });
    }
};
