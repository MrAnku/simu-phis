<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScormAssignedUser;
use App\Models\TrainingAssignedUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiAssignedTrainingsController extends Controller
{
    public function fetchEmpWithTrainings(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $allAssignedTrainings = [];
        $allTrainings =  TrainingAssignedUser::where('company_id', $companyId)
            ->with('trainingData')
            ->get();

        $allScormTrainings = ScormAssignedUser::where('company_id', $companyId)
            ->with('scormTrainingData')
            ->get();

        return $allAssignedTrainings = $allTrainings->merge($allScormTrainings);
    }
}
