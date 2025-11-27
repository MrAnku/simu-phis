<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ApiMsspController extends Controller
{
    public function msspCompanies(Request $request)
    {
        if(Auth::user()->mssp_admin == 0){
            return response()->json(
                [
                    'success' => false,
                    'message' => 'You are not authorized to access this resource.',
                ],
                403
            );
        }
        $msspCompanies = Company::where('mssp_id', Auth::user()->mssp_id)
        ->where('mssp_admin', 0)->get();
        return response()->json(
            [
                'success' => true,
                'data' => $msspCompanies,
            ]
        );

        
    }

    public function companyLoginSwitch($email)
    {
        if(Auth::user()->mssp_admin == 0){
            return response()->json(
                [
                    'success' => false,
                    'message' => 'You are not authorized to access this resource.',
                ],
                403
            );
        }

        $company = Company::where('email', $email)
            ->where('mssp_id', Auth::user()->mssp_id)
            ->where('mssp_admin', 0)
            ->first();

        if (!$company) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Company not found or you are not authorized to access this company.',
                ],
                404
            );
        }

        $authService = new AuthService($email);

        return $authService->loginCompany('mssp_switch');
    }
}
