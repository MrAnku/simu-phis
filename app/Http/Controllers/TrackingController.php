<?php

namespace App\Http\Controllers;

use App\Models\CampaignLive;
use App\Models\TprmActivity;
use Illuminate\Http\Request;
use App\Models\CampaignReport;
use App\Models\QuishingActivity;
use App\Models\QuishingLiveCamp;
use App\Models\TprmCampaignLive;
use App\Models\EmailCampActivity;
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
                if (clickedByBot($campaignLive->company_id, $campaignLive->id, 'email')) {
                    return;
                }
                $campaignLive->mail_open = 1;
                $campaignLive->save();

                setCompanyTimezone($campaignLive->company_id);
                

                EmailCampActivity::where('campaign_live_id', $campid)->update(['email_viewed_at' => now()]);

                log_action("Phishing email opened by {$campaignLive->user_email} in email simulation", 'company', $campaignLive->company_id);
                
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
                if (clickedByBot($campaignLive->company_id, $campaignLive->id, 'tprm')) {
                    return;
                }
                $campaignLive->mail_open = 1;
                $campaignLive->save();

                setCompanyTimezone($campaignLive->company_id);

                TprmActivity::where('campaign_live_id', $campid)->update(['email_viewed_at' => now()]);

                log_action("Phishing email opened by {$campaignLive->user_email} in TPRM simulation", 'company', $campaignLive->company_id);
                
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

    public function googleReport(Request $request)
    {
        // Log the details to a specific log file
        Log::channel('google_report')->info('Google Phish Report', [
           
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'timestamp' => now()->toDateTimeString(),
        ]);

        return response()->json(['status' => 'success'], 200);
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
                    if (clickedByBot($quishingLive->company_id, $quishingLive->id, 'quishing')) {
                        return;
                    }
                    $quishingLive->mail_open = '1';
                    $quishingLive->save();

                    setCompanyTimezone($quishingLive->company_id);
                    
                    log_action("Quishing QR code scanned by {$quishingLive->user_email}", 'company', $quishingLive->company_id);
                    QuishingActivity::where('campaign_live_id', $employeeid)->update(['email_viewed_at' => now()]);
                }
            }

            return response()->file($path);
        } else {
            return abort(404);
        }
    }
}
