<?php

namespace App\Http\Controllers;

use App\Models\QshTemplate;
use Illuminate\Http\Request;
use App\Models\TrainingModule;
use Illuminate\Support\Facades\Auth;

class QuishingController extends Controller
{
    public function index()
    {
        $company_id = Auth::user()->company_id;
        $quishingEmails = QshTemplate::where('company_id', $company_id)
            ->orWhere('company_id', 'default')
            ->get();

        $trainingModules = TrainingModule::where(function ($query) use ($company_id) {
            $query->where('company_id', $company_id)
                ->orWhere('company_id', 'default');
        })->where('training_type', 'static_training')
            ->limit(10)
            ->get();

        return view('quishing', compact('quishingEmails', 'trainingModules'));
    }

    public function showMoreTemps(Request $request)
    {
        $page = $request->input('page', 1);
        $companyId = Auth::user()->company_id;

        $phishingEmails = QshTemplate::where('company_id', $companyId)
            ->orWhere('company_id', 'default')
            ->skip(($page - 1) * 10)
            ->take(10)
            ->get();

        return response()->json(['status' => 1, 'data' => $phishingEmails]);
    }

    public function searchTemplate(Request $request)
    {
        $searchTerm = $request->input('search');
        $companyId = Auth::user()->company_id;

        $phishingEmails = QshTemplate::where(function ($query) use ($companyId) {
            $query->where('company_id', $companyId)
                ->orWhere('company_id', 'default');
        })->where(function ($query) use ($searchTerm) {
                $query->where('name', 'LIKE', "%{$searchTerm}%");
            })->get();

        return response()->json(['status' => 1, 'data' => $phishingEmails]);
    }
}
