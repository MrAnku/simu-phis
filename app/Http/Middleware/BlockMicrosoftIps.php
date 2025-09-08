<?php

namespace App\Http\Middleware;

use Closure;
use IPLib\Factory;
use IPLib\Address\IPv4;
use IPLib\Range\Subnet;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockMicrosoftIps
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $clientIp = getClientIp();

        // Parse IPv4 or IPv6 automatically
        $ip = Factory::parseAddressString($clientIp);

        if ($ip) {
            foreach (config('blockedips.ranges') as $range) {
                $subnet = Subnet::parseString($range);
                if ($subnet && $subnet->contains($ip)) {
                    return response()->json([
                        'message' => 'Access denied from your IP address.',
                    ], 403);
                }
            }
        }

        return $next($request);
    }
}
