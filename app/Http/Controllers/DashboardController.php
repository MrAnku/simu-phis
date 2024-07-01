<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {

        $data = $this->getTotalAssets();

        return view('dashboard', compact('data'));
    }

    public function getPieData()
    {
        $pieData = DB::table('campaign_reports')
            ->selectRaw('SUM(emails_delivered) AS total_emails_delivered, 
                                     SUM(emails_viewed) AS total_emails_viewed, 
                                     SUM(training_assigned) AS total_training_assigned, 
                                     SUM(training_completed) AS total_training_completed')
            ->where('company_id', Auth::user()->company_id)
            ->first();

        return response()->json($pieData);
    }

    public function getLineChartData()
    {
        $lastSixMonthsData = [];
        $currentMonthName = now()->format('F');

        for ($i = 0; $i < 6; $i++) {
            $monthName = now()->subMonths($i)->format('F');

            $noOfCampaigns = DB::table('all_campaigns')
                ->whereRaw('MONTHNAME(launch_time) = ? AND company_id = ?', [$monthName, Auth::user()->company_id])
                ->count();

            $lastSixMonthsData[] = [
                'month' => $monthName,
                'no_of_camps' => $noOfCampaigns
            ];
        }

        return response()->json(array_reverse($lastSixMonthsData));
    }

    public function getTotalAssets()
    {
        $companyId = Auth::user()->company_id;

        // Get active campaigns
        $activeCampaignsCount = DB::table('all_campaigns')
            ->where('company_id', $companyId)
            ->where('status', 'running')
            ->count();

        // Get phishing emails
        $phishingEmailsCount = DB::table('phishing_emails')
            ->where(function ($query) use ($companyId) {
                $query->where('company_id', 'default')
                    ->orWhere('company_id', $companyId);
            })
            ->count();

        // Get phishing websites
        $phishingWebsitesCount = DB::table('phishing_websites')
            ->where(function ($query) use ($companyId) {
                $query->where('company_id', 'default')
                    ->orWhere('company_id', $companyId);
            })
            ->count();

        // Get training modules
        $trainingModulesCount = DB::table('training_modules')->count();

        // Prepare data to pass to view
        return [
            'active_campaigns' => $activeCampaignsCount,
            'phishing_emails' => $phishingEmailsCount,
            'phishing_websites' => $phishingWebsitesCount,
            'training_modules' => $trainingModulesCount,
        ];
    }
}
