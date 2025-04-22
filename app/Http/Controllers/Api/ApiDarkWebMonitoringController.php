<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BreachedEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiDarkWebMonitoringController extends Controller
{
    public function index()
    {
        try {
            $company_id = Auth::user()->company_id;

            $breachedEmails = BreachedEmail::with('userData')
                ->where('company_id', $company_id)
                ->get();

            if ($breachedEmails->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => __("No Breached Emails Found")
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $breachedEmails
            ], 200); // OK
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }
}
