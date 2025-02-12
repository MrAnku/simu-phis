<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckWhiteLabelDomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        $companyBranding = DB::table('white_labelled_partner')
            ->where('domain', $host)
            ->where('approved_by_admin', 1)
            ->first();

        if ($companyBranding) {
            $companyLogoDark = "/storage/uploads/whitelabeled/" . $companyBranding->dark_logo;
            $companyLogoLight = "/storage/uploads/whitelabeled/" . $companyBranding->light_logo;
            $companyFavicon = "/storage/uploads/whitelabeled/" . $companyBranding->favicon;
            $companyName = $companyBranding->company_name;
            $companyDomain = "https://" . $companyBranding->domain . "/";
            $companyLearnDomain = "https://" . $companyBranding->learn_domain . "/";
        } else {
            $companyLogoLight = "/assets/images/simu-logo.png";
            $companyLogoDark = "/assets/images/simu-logo-dark.png";
            $companyFavicon = "/assets/images/simu-icon.png";
            $companyName = 'simUphish';
            $companyDomain = env('SIMUPHISH_URL');
            $companyLearnDomain = env('SIMUPHISH_LEARNING_URL');
        }

        // Share branding information with all views
        view()->share([
            'companyLogoDark' => $companyLogoDark,
            'companyLogoLight' => $companyLogoLight,
            'companyFavicon' => $companyFavicon,
            'companyName' => $companyName,
            'companyDomain' => $companyDomain,
            'companyLearnDomain' => $companyLearnDomain
        ]);

        
        return $next($request);
    }
}
