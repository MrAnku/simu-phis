<?php

namespace App\Http\Controllers;

use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SingleEmpController extends Controller
{
    public function employeeDetail($base_encode_id)
    {
        $companyId = Auth::user()->company_id;
        $id = base64_decode($base_encode_id);
        $employee = Users::with(['campaigns', 'assignedTrainings', 'whatsappCamps', 'aiCalls'])->where('id', $id)->where('company_id', $companyId)->first();

        if(!$employee) {
            return redirect()->back()->with('error', 'Employee not found');
        }

        // return $employee->campaigns?->sum('payload_clicked');

        return view('employee-detail', compact('employee'));
        
    }
}
