<?php

namespace App\Http\Controllers;

use App\Models\CampaignLive;
use Illuminate\Http\Request;
use App\Models\CampaignReport;
use App\Models\TprmCampaignLive;
use App\Models\EmailCampActivity;
use App\Models\QuishingLiveCamp;
use App\Models\TprmCampaignReport;
use Illuminate\Support\Facades\Log;

class TrackingController extends Controller
{
    //
    public function trackemail($campid)
    {

        if ($campid) {

            $campaignLive = CampaignLive::where('id', $campid)->where('mail_open', 0)->first();

            if ($campaignLive) {
                $campaignLive->mail_open = 1;
                $campaignLive->save();

                EmailCampActivity::where('campaign_live_id', $campid)->update(['email_viewed_at' => now()]);

                log_action("Phishing email opened by {$campaignLive->user_email}", 'employee', 'employee');
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
    public function ttrackemail($campid)
    {

        if ($campid) {

            $campaignLive = TprmCampaignLive::where('id', $campid)->where('mail_open', 0)->first();

            if ($campaignLive) {
                $campaignLive->mail_open = 1;
                $campaignLive->save();
                log_action("Phishing email opened by {$campaignLive->user_email}", 'employee', 'employee');
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

    public function outlookPhishReport(Request $request)
    {
        if ($request->has('Website_url')) {

            // Parse the URL to get the query string
            $parsedUrl = parse_url($request->Website_url);
            $queryParams = [];
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
            }

            // Extract the values if they exist
            $token = $queryParams['token'] ?? null;
            $usrid = $queryParams['usrid'] ?? null;
            $tprm = $queryParams['tprm'] ?? null;

            if ($token) { // Ensure token is set
                if ($tprm) {
                    $campaign = TprmCampaignLive::find($token);
                    if ($campaign) {
                        $campaign->email_reported = 1; // Optional: Set initial value, if needed
                        $campaign->save();

                        log_action("Email reported using outlook phish report button by {$campaign->user_email}", 'employee', 'employee');
                        TprmCampaignReport::where('campaign_id', $campaign->campaign_id)->increment('email_reported');
                    }
                } else {
                    $campaign = CampaignLive::find($token);
                    if ($campaign) {
                        $campaign->email_reported = 1; // Optional: Set initial value, if needed
                        $campaign->save();

                        CampaignReport::where('campaign_id', $campaign->campaign_id)->increment('email_reported');
                    }
                }
            }
        }
    }

    public function trackquishing(Request $request)
    {
        $file = $request->filename;
        $employeeid = $request->query('eid');
        $path = storage_path('app/qrcodes/' . $file);

        if (file_exists($path)) {
            if ($employeeid) {
                     
                $quishingLive = QuishingLiveCamp::where('id', $employeeid)
                ->where('mail_open', '0')
                ->first();
                if ($quishingLive) {
                    $quishingLive->mail_open = '1';
                    $quishingLive->save();
                }
            }

            return response()->file($path);
        } else {
            return abort(404);
        }
    }
}
