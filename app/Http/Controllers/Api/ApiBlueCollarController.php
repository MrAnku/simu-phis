<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlueCollarEmployee;
use App\Models\BlueCollarGroup;
use App\Models\OutlookAdToken;
use App\Models\WhatsAppCampaignUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiBlueCollarController extends Controller
{
    public function index(){
        try{
            $companyId = Auth::user()->company_id;

            $groups = BlueCollarGroup::withCount('bluecollarusers')
                ->where('company_id', $companyId)
                ->get();
    
            $totalEmployeeCount = BlueCollarEmployee::where('company_id', $companyId)->get()->count();
            $totalActiveEmployees = WhatsAppCampaignUser::where("employee_type", "Bluecollar")
                ->where('company_id', $companyId)
                ->get()
                ->count();
            $totalCompromisedEmployees = WhatsAppCampaignUser::where("employee_type", "Bluecollar")
                ->where("emp_compromised", 1)
                ->where('company_id', $companyId)
                ->get()
                ->count();
    
            $totalEmps = $groups->sum('bluecollarusers_count');
    
            $hasOutlookAdToken = OutlookAdToken::where('company_id', $companyId)->exists();
    
            return response()->json([
                'success' => true,
                'data' => [
                    'total_employee_count' => $totalEmployeeCount,
                    'totalEmps' => $totalEmps,
                    'total_active_employees' => $totalActiveEmployees,
                    'total_compromised_employees' => $totalCompromisedEmployees,
                    'groups' => $groups,
                    'has_outlook_ad_token' => $hasOutlookAdToken
                ],
                'message' => 'Employee data retrieved successfully'
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
        
      
    }
}
