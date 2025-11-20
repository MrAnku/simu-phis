<?php

namespace App\Services;

use App\Models\CompanyBranding;

class BrandingService
{
    public $companyId;
    public function __construct($companyId)
    {
        $this->companyId = $companyId;
    }
    public function companyName(): string
    {
       return CompanyBranding::where('company_id', $this->companyId)
            ->value('company_name') ?? env('APP_NAME');
    }

    public function companyDarkLogo(): string
    {
        $darkLogo = CompanyBranding::where('company_id', $this->companyId)
            ->value('dark_logo');
        return $darkLogo ? env('CLOUDFRONT_URL') . $darkLogo : env('CLOUDFRONT_URL') . "/assets/images/simu-logo-dark.png";
    }

    public function companyLightLogo(): string
    {
        $lightLogo = CompanyBranding::where('company_id', $this->companyId)
            ->value('light_logo');
        return $lightLogo ? env('CLOUDFRONT_URL') . $lightLogo : env('CLOUDFRONT_URL') . "/assets/images/simu-logo.png";
    }

    public function companyFavicon(): string
    {
        $favicon = CompanyBranding::where('company_id', $this->companyId)
            ->value('favicon');
        return $favicon ? env('CLOUDFRONT_URL') . $favicon : env('CLOUDFRONT_URL') . "/assets/images/simu-icon.png";
    }

}
