<?php

namespace App\Http\Controllers;

use App\Models\PhishingWebsite;
use Illuminate\Http\Request;
use App\Models\SmishingCampaign;
use App\Models\SmishingLiveCampaign;
use App\Models\SmishingTemplate;
use App\Models\TrainingModule;
use Illuminate\Support\Facades\Auth;

class SmishingController extends Controller
{
    public function index()
    {
        $campaigns = SmishingCampaign::where('company_id', Auth::user()->company_id)
            ->get();
        $templates = SmishingTemplate::where('company_id', Auth::user()->company_id)
            ->orWhere('company_id', 'default')
            ->get();
        $totalSentCampaigns = SmishingLiveCampaign::where('company_id', Auth::user()->company_id)
            ->where('sent', 1)
            ->count();
        $totalCompromised = SmishingLiveCampaign::where('company_id', Auth::user()->company_id)
            ->where('compromised', 1)
            ->count();

        $trainingModules = TrainingModule::where('company_id', Auth::user()->company_id)
            ->orWhere('company_id', 'default')
            ->get();
        $phishingWebsites = PhishingWebsite::where('company_id', Auth::user()->company_id)
            ->orWhere('company_id', 'default')
            ->get();

        return view('smishing', compact(
            'campaigns',
            'templates',
            'totalSentCampaigns',
            'totalCompromised',
            'phishingWebsites',
            'trainingModules'
        ));
    }
}
