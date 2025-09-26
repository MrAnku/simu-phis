<?php

namespace App\Http\Middleware;

use App\Models\WhiteLabelledCompany;
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

        $companyBranding = WhiteLabelledCompany::where(function ($query) use ($host) {
            $query->where('domain', $host)
                ->orWhere('learn_domain', $host);
        })
            ->where('approved_by_partner', 1)
            ->where('service_status', 1)
            ->first();

        $cloudFrontUrl = env('CLOUDFRONT_URL');


        if ($companyBranding) {
            $companyLogoDark = $cloudFrontUrl . $companyBranding->dark_logo;
            $companyLogoLight = $cloudFrontUrl . $companyBranding->light_logo;
            $companyFavicon = $cloudFrontUrl . $companyBranding->favicon;
            $companyName = $companyBranding->company_name;
            $companyDomain = "https://" . $companyBranding->domain . "/";
            $companyLearnDomain = "https://" . $companyBranding->learn_domain . "/";
        } else {
            $companyLogoLight = "/assets/images/simu-logo.png";
            $companyLogoDark = "/assets/images/simu-logo-dark.png";
            $companyFavicon = "/assets/images/simu-icon.png";
            $companyName = env('APP_NAME');
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
