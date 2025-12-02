<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Maintenance;

class CheckMaintenance
{
    public function handle(Request $request, Closure $next)
    {
        $maintenance = Maintenance::first();

        if ($maintenance && $maintenance->maintenance_mode == 1) {
              return response()->json([
                'success' => 'false',
                'message' => 'under-maintenance',      
            ], 503); 
        }

        return $next($request);
    }
}
