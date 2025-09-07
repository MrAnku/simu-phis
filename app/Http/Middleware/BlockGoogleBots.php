<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class BlockGoogleBots
{
    /**
     * List of known Google bot user agent patterns.
     *
     * @var array
     */
    protected $googleBotUserAgents = [
        'Googlebot', // Main Googlebot
        'Mediapartners-Google', // AdSense
        'AdsBot-Google', // Ads
        'Googlebot-Image', // Image crawler
        'Googlebot-News', // News
        'Googlebot-Video', // Video
        'Google-InspectionTool', // Inspection tool
        'GoogleOther', // Other Google crawlers
        'APIs-Google', // API crawler
        'Storebot-Google', // Store crawler
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = $request->header('User-Agent', '');
        $clientIp = $request->ip();

        // Log the request for debugging (optional)
        // Log::debug('BlockGoogleBots Middleware', [
        //     'ip' => $clientIp,
        //     'user_agent' => $userAgent,
        // ]);

        // Check if the User-Agent matches known Google bot patterns
        foreach ($this->googleBotUserAgents as $botPattern) {
            if (stripos($userAgent, $botPattern) !== false) {
                // Perform additional verification (optional)
                if ($this->isGoogleBotIp($clientIp)) {
                    Log::info('Blocked Google Bot', ['ip' => $clientIp, 'user_agent' => $userAgent]);
                    abort(404, 'Not Found');
                }
                abort(404, 'Not Found');
            }
        }

        // Proceed with the request
        return $next($request);
    }

    /**
     * Verify if the IP belongs to a Google bot (optional).
     *
     * @param  string  $ip
     * @return bool
     */
    protected function isGoogleBotIp(string $ip): bool
    {
        try {
            // Perform reverse DNS lookup
            $clientHost = gethostbyaddr($ip);

            // Check if the rDNS contains google.com or googleusercontent.com
            if (
                $clientHost !== false &&
                (strpos($clientHost, 'google.com') !== false || strpos($clientHost, 'googleusercontent.com') !== false)
            ) {
                return true;
            }

            // Optional: Add additional IP range checks here
            // Google's bot IPs can be verified using their official documentation:
            // https://developers.google.com/search/docs/crawling-indexing/verifying-googlebot
            // You can fetch Google's IP ranges programmatically or cache them.

        } catch (\Exception $e) {
            Log::error('Error verifying Google bot IP', ['ip' => $ip, 'error' => $e->getMessage()]);
        }

        return false;
    }
}