<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DomainVerified;
use App\Models\OutlookAdToken;
use App\Models\Users;
use App\Models\UsersGroup;
use Illuminate\Support\Facades\Auth;

class ApiEmployeesController extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id;
        $groups = UsersGroup::where('company_id', $companyId)->get();

        $totalEmps = Users::where('company_id', $companyId)->pluck('user_email')->unique()->count();
        $verifiedDomains = DomainVerified::where('verified', 1)->where('company_id', $companyId)->get();
        $notVerifiedDomains = DomainVerified::where('verified', 0)->where('company_id', $companyId)->get();

        $allDomains = DomainVerified::where('company_id', $companyId)->get();

        $hasOutlookAdToken = OutlookAdToken::where('company_id', $companyId)->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'total_employees' => $totalEmps,
                'verified_domains' => $verifiedDomains,
                'not_verified_domains' => $notVerifiedDomains,
                'all_domains' => $allDomains,
                'has_outlook_ad_token' => $hasOutlookAdToken
            ],
            'message' => 'Employee data retrieved successfully'
        ], 200);
    }

    public function allEmployee()
    {
        $companyId = Auth::user()->company_id;

        $totalEmps = Users::where('company_id', $companyId)->pluck('user_email')->unique()->count();
        $verifiedDomains = DomainVerified::where('verified', 1)->where('company_id', $companyId)->get();
        $notVerifiedDomains = DomainVerified::where('verified', 0)->where('company_id', $companyId)->get();

        $allDomains = DomainVerified::where('company_id', $companyId)->get();
        $allEmployees = Users::where('company_id', $companyId)
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('users')
                    ->groupBy('user_email');
            })
            ->get();

        $hasOutlookAdToken = OutlookAdToken::where('company_id', $companyId)->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'total_employees' => $totalEmps,
                'verified_domains' => $verifiedDomains,
                'not_verified_domains' => $notVerifiedDomains,
                'all_domains' => $allDomains,
                'has_outlook_ad_token' => $hasOutlookAdToken,
                'all_employees' => $allEmployees
            ],
            'message' => 'All employee data retrieved successfully'
        ], 200);
    }

    public function employeeDetail($base_encode_id)
    {
        $companyId = Auth::user()->company_id;
        $id = base64_decode($base_encode_id);
        $employee = Users::with(['campaigns', 'assignedTrainings', 'whatsappCamps', 'aiCalls'])->where('id', $id)->where('company_id', $companyId)->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found'
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => $employee,
            'message' => 'Employee details retrieved successfully'
        ], 200);
    }
}
