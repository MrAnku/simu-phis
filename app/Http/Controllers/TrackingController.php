<?php

namespace App\Http\Controllers;

use App\Models\CampaignLive;
use Illuminate\Http\Request;
use App\Models\CampaignReport;
use App\Models\TprmCampaignLive;
use App\Models\TprmCampaignReport;

class TrackingController extends Controller
{
    //
    public function trackemail($campid){

        if($campid){
            
            $campaignLive = CampaignLive::where('id', $campid)->where('mail_open', 0)->first();
            
            if ($campaignLive) {
                $campaignLive->mail_open = 1;
                $campaignLive->save();

                $report = CampaignReport::where('campaign_id', $campaignLive->campaign_id)->first();
                
                if ($report) {
                    $report->emails_viewed += 1;
                    $report->save();
                }
            }

        }

        // Serve the tracking pixel image
        $path = public_path('dot.png');

        return response()->file($path);

    }
    public function ttrackemail($campid){

        if($campid){
            
            $campaignLive = TprmCampaignLive::where('id', $campid)->where('mail_open', 0)->first();
            
            if ($campaignLive) {
                $campaignLive->mail_open = 1;
                $campaignLive->save();

                $report = TprmCampaignReport::where('campaign_id', $campaignLive->campaign_id)->first();
                
                if ($report) {
                    $report->emails_viewed += 1;
                    $report->save();
                }
            }

        }

        // Serve the tracking pixel image
        $path = public_path('dot.png');

        return response()->file($path);

    }
}
