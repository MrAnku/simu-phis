<?php

namespace App\Http\Controllers\Api;

use App\Models\SiemProvider;
use Illuminate\Http\Request;
use App\Models\OutlookAdToken;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\CompanyWhatsappConfig;

class ApiIntegrationController extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id;

        $whatsappConfig = CompanyWhatsappConfig::where('company_id', $companyId)
            ->first();
        $siemConfig = SiemProvider::where('company_id', $companyId)
            ->first();
        $ldapConfig = DB::table('ldap_ad_config')
            ->where('company_id', $companyId)
            ->first();
        $hasOutlookAdToken = OutlookAdToken::where('company_id', $companyId)->exists();
        if(!$hasOutlookAdToken) {
            $authenticateUrl = env('OUTLOOK_AUTH_URL');
        }else{
            $authenticateUrl = null;
        }
        return response()->json([
            'success' => true,
            'message' => 'Integration configurations retrieved successfully.',
            'data' => [
                'whatsapp_config' => $whatsappConfig,
                'siem_config' => $siemConfig,
                'ldap_config' => $ldapConfig,
                'outlook_report_button_xml_url' => 'https://365button.simuphish.com/button.xml',
                'has_outlook_token' => $hasOutlookAdToken,
                'outlook_authenticate_url' => $authenticateUrl
            ]
        ]);
    }
}
