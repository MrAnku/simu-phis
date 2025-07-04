<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhiteLabelledSmtp;
use App\Models\WhiteLabelledCompany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class WhiteLabelController extends Controller
{
    public function check(Request $request){
        $host = $request->query('host');

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
            $companyLogoLight = $cloudFrontUrl . "/assets/images/simu-logo.png";
            $companyLogoDark =  $cloudFrontUrl . "/assets/images/simu-logo-dark.png";
            $companyFavicon = $cloudFrontUrl ."/assets/images/simu-icon.png";
            $companyName = env('APP_NAME');
            $companyDomain = env('NEXT_APP_URL');
            $companyLearnDomain = env('SIMUPHISH_LEARNING_URL');
        }

        // Share branding information with all views
        return response()->json([
            'companyLogoDark' => $companyLogoDark,
            'companyLogoLight' => $companyLogoLight,
            'companyFavicon' => $companyFavicon,
            'companyName' => $companyName,
            'companyDomain' => $companyDomain,
            'companyLearnDomain' => $companyLearnDomain
        ]);

    }
    public function saveWhiteLabel(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_email' => 'required|email',
            'domain' => 'required|string',
            'learn_domain' => 'required|string',
            'dark_logo' => 'required|mimes:png',
            'light_logo' => 'required|mimes:png',
            'favicon' => 'required|mimes:png',
            'smtp_host' => 'required|string|max:255',
            'smtp_port' => 'required|integer',
            'smtp_username' => 'required|string|max:255',
            'smtp_password' => 'required|string|max:255',
            'smtp_encryption' => 'required|string',
            'from_address' => 'required',
            'from_name' => 'required|string|max:255',
        ]);

        $domainExists = WhiteLabelledCompany::where('domain', $request->domain)->exists();
        if($domainExists) {
            return redirect()->back()->with('error', 'Domain already added by another company.');
        }

        $learnDomainExists = WhiteLabelledCompany::where('learn_domain', $request->learn_domain)->exists();
        if($learnDomainExists) {
            return redirect()->back()->with('error', 'Learn domain already added by another company.');
        }

        $smtpUsernameExists = WhiteLabelledSmtp::where('smtp_username', $request->smtp_username)->exists();
        if($smtpUsernameExists) {
            return redirect()->back()->with('error', 'SMTP username already added by another company.');
        }

        $companyId = Auth::user()->company_id;

        $randomName = generateRandom(10);
        $extension = $request->file('dark_logo')->getClientOriginalExtension();
        $darkLogoFilename = $randomName . '.' . $extension;


        $darkLogoPath = $request->file('dark_logo')->storeAs("whiteLabel/{$companyId}", $darkLogoFilename, 's3');

        $randomName = generateRandom(10);
        $extension = $request->file('light_logo')->getClientOriginalExtension();
        $lightLogoFilename = $randomName . '.' . $extension;

        $lightLogoPath = $request->file('light_logo')->storeAs("whiteLabel/{$companyId}", $lightLogoFilename, 's3');

         $randomName = generateRandom(10);
        $extension = $request->file('favicon')->getClientOriginalExtension();
        $faviconLogoFilename = $randomName . '.' . $extension;

        $faviconLogoPath = $request->file('favicon')->storeAs("whiteLabel/{$companyId}", $faviconLogoFilename, 's3');

        $isCreatedWhitLabel = WhiteLabelledCompany::create([
            'company_id' => Auth::user()->company_id,
            'partner_id' => Auth::user()->partner_id,
            'company_email' => $request->company_email,
            'domain' => $request->domain,
            'learn_domain' => $request->learn_domain,
            'dark_logo' => "/".$darkLogoPath,
            'light_logo' => "/".$lightLogoPath,
            'favicon' => "/".$faviconLogoPath,
            'company_name' => $request->company_name,
        ]);

        if ($isCreatedWhitLabel) {
            WhiteLabelledSmtp::create([
                'smtp_host' => $request->smtp_host,
                'smtp_port' => $request->smtp_port,
                'smtp_username' => $request->smtp_username,
                'smtp_password' => $request->smtp_password,
                'smtp_encryption' => $request->smtp_encryption,
                'from_address' => $request->from_address,
                'from_name' => $request->from_name,
                'company_id' => Auth::user()->company_id,
            ]);
            return redirect()->back()->with('success', 'White label created successfully.');
        }
    }
}
