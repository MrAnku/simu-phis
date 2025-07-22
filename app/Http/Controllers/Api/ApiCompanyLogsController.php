<?php

namespace App\Http\Controllers\Api;

use App\Models\Log;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ApiCompanyLogsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');
            if($search) {
                $logs = Log::where('role_id', Auth::user()->company_id)
                    ->where('msg', 'like', '%' . $search . '%')
                    ->orderBy('created_at', 'desc')
                    ->select('msg', 'ip_address', 'user_agent', 'created_at')
                    ->paginate(10);
            } else {
                $logs = Log::where('role_id', Auth::user()->company_id)
                    ->orderBy('created_at', 'desc')
                    ->select('msg', 'ip_address', 'user_agent', 'created_at')
                    ->paginate(10);
            }
           

            return response()->json([
                'success' => true,
                'data' => $logs
            ], 200); // ✅ 200 OK

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    
}
