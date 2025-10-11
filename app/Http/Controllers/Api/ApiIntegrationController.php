<?php

namespace App\Http\Controllers\Api;

use App\Models\SiemProvider;
use Illuminate\Http\Request;
use App\Models\OutlookAdToken;
use App\Models\OutlookDmiToken;
use App\Services\OutlookAdService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\AutoSyncEmployee;
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
        if (!$hasOutlookAdToken) {
            $authenticateUrl = OutlookAdService::authenticateUrl();
        } else {
            $authenticateUrl = null;
        }

        $hasOutlookDmiToken = OutlookDmiToken::where('company_id', $companyId)->exists();
        if (!$hasOutlookDmiToken) {
            $params = [
                'client_id' => env('MS_DMI_CLIENT_ID'),
                'response_type' => 'code',
                'redirect_uri' => env('MS_DMI_REDIRECT_URI'),
                'response_mode' => 'query',
                'scope' => env('MS_DMI_SCOPE'),
            ];

            $auth_url = env('MS_DMI_AUTHORITY_URL') . '/authorize?' . http_build_query($params);

            $dmiAuthUrl = $auth_url;
        } else {
            $dmiAuthUrl = null;
        }
        return response()->json([
            'success' => true,
            'message' => __('Integration configurations retrieved successfully.'),
            'data' => [
                'whatsapp_config' => $whatsappConfig,
                'siem_config' => $siemConfig,
                'ldap_config' => $ldapConfig,
                'outlook_report_button_xml_url' => 'https://365button.simuphish.com/button.xml.zip',
                'has_outlook_token' => $hasOutlookAdToken,
                'outlook_authenticate_url' => $authenticateUrl,
                'outlook_dmi_url' => $dmiAuthUrl,
            ]
        ]);
    }

    public function disableOutlookDirectorySync(){
        $companyId = Auth::user()->company_id;

        // Check if auto sync is enabled
        $autoSync = AutoSyncEmployee::where('company_id', $companyId)
            ->where('provider', 'outlook')
            ->exists();
        if($autoSync){
            return response()->json([
                'success' => false,
                'message' => __('Auto-sync is enabled for Outlook, please delete it first.')
            ], 400);
        }
        OutlookAdToken::where('company_id', $companyId)->delete();

        return response()->json([
            'success' => true,
            'message' => __('Outlook directory sync has been disabled successfully.')
        ]);
    }

}
