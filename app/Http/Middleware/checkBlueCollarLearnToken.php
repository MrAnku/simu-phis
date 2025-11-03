<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class checkBlueCollarLearnToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('Authorization'); // Get token from header

        $session = DB::table('blue_collar_learner_login_sessions')
            ->where('token', $token)
            ->where('whatsapp_number', $request->query('user_whatsapp'))
            ->orderBy('created_at', 'desc')
            ->first();

        // Check if session exists and if the token is expired
        if (!$session || now()->greaterThan(Carbon::parse($session->expiry))) {
            return response()->json([
                'success' => false,
                'message' => 'Your training session has expired!'
            ], 401);
        }

        return $next($request);
    }
}
