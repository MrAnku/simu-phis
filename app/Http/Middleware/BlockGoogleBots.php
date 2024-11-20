<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockGoogleBots
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
       // Get the client's IP address
       $clientIp = $request->ip();

       // Perform reverse DNS lookup
       $clientHost = gethostbyaddr($clientIp);

       // Check if the rDNS contains google.com or googleusercontent.com
       if (strpos($clientHost, 'google.com') !== false || strpos($clientHost, 'googleusercontent.com') !== false) {
           abort(404, 'Not Found');
       }

       // Proceed with the request
       return $next($request);
    }
}
