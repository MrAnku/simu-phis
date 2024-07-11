<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    //
    public function index(){
        // Fetch counts from the database
        $activeCampaignsCount = DB::table('all_campaigns')->where('status', 'running')->count();
        $phishingEmailsCount = DB::table('phishing_emails')->count();
        $phishingWebsitesCount = DB::table('phishing_websites')->count();
        $trainingModulesCount = DB::table('training_modules')->count();

        // Prepare the counts in an array or object
        $counts = (object) [
            'activeCampaigns' => $activeCampaignsCount,
            'phishingEmails' => $phishingEmailsCount,
            'phishingWebsites' => $phishingWebsitesCount,
            'trainingModules' => $trainingModulesCount,
        ];

        // Pass the counts to the view
        return view('admin.dashboard', compact('counts'));
    }

   
}
